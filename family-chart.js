/* Bansha chart
   A family line you can write, edit, and delete.
   Numbers are computed from position. They are not stored.
   1 founder, 1.1 first child, 1.2 next child, 1-s spouse, 1-s2 second spouse.
*/
(function () {
  const CARD_W = 220;
  const CARD_H = 124;
  const GAP_X = 36;
  const GAP_Y = 88;
  const SPOUSE_GAP = 48;
  const PAD = 80;
  const STORAGE_KEY = "bansha-chart-v1";
  const PRACTICE_KEY = "bansha-chart-practice";
  const MODE_KEY = "bansha-chart-mode";

  const PATTERN = `# bansha: Our bansha
# Start with the oldest person. Two spaces means the next generation.
# + is a wife or husband. (m) male, (f) female.
# Numbers are added for you.

Grandfather (m) | 1940-2012
+ Grandmother (f) | 1946
  Father (m) | 1970
  + Mother (f) | 1974
    Elder brother (m) | 1996
    Sister (f) | 1999
    Younger brother (m) | 2002
  Aunt (f) | 1976
`;

  function uid(prefix) {
    return prefix + Math.random().toString(36).slice(2, 10);
  }
  function clone(v) {
    return JSON.parse(JSON.stringify(v));
  }
  function nfc(s) {
    return String(s || "").normalize("NFC");
  }
  function emptyState() {
    return { version: 1, banshaName: "", focusId: null, sample: false, people: [], unions: [] };
  }
  function personName(p) {
    const name = p && nfc(p.name).trim();
    return name || "Unnamed";
  }

  function indexState(state) {
    const people = new Map(state.people.map((p) => [p.id, p]));
    const unionsByAnchor = new Map();
    const parentUnion = new Map();
    for (const u of state.unions) {
      const anchor = u.partners[0];
      if (!anchor) continue;
      if (!unionsByAnchor.has(anchor)) unionsByAnchor.set(anchor, []);
      unionsByAnchor.get(anchor).push(u);
      for (const c of u.childIds) {
        if (!parentUnion.has(c)) parentUnion.set(c, u);
      }
    }
    return { people, unionsByAnchor, parentUnion };
  }

  function ownChildren(index, id) {
    const out = [];
    for (const u of index.unionsByAnchor.get(id) || []) {
      for (const c of u.childIds) out.push(c);
    }
    return out;
  }

  function createPerson(state, fields) {
    const person = {
      id: uid("p"),
      name: nfc(fields.name || "Unnamed").trim() || "Unnamed",
      gender: ["male", "female", "other"].includes(fields.gender) ? fields.gender : "",
      birth: nfc(fields.birth || "").trim(),
      death: nfc(fields.death || "").trim(),
      notes: nfc(fields.notes || "").trim(),
      collapsed: false,
      banshaName: nfc(fields.banshaName || "").trim()
    };
    state.people.push(person);
    return person;
  }

  function anchorOf(state, id) {
    const index = indexState(state);
    if (index.parentUnion.has(id)) return id;
    if ((index.unionsByAnchor.get(id) || []).length) return id;
    const asSpouse = state.unions.find((u) => u.partners[1] === id);
    if (asSpouse) return asSpouse.partners[0];
    return id;
  }

  function wouldCycle(state, parentId, childId) {
    const index = indexState(state);
    const stack = [parentId];
    const seen = new Set();
    while (stack.length) {
      const cur = stack.pop();
      if (!cur || seen.has(cur)) continue;
      if (cur === childId) return true;
      seen.add(cur);
      const u = index.parentUnion.get(cur);
      if (u) stack.push(...u.partners);
    }
    return false;
  }

  function addChild(state, selectedId, fields, unionId) {
    const anchor = anchorOf(state, selectedId);
    const child = createPerson(state, fields || { name: "New child" });
    let union = unionId
      ? state.unions.find((u) => u.id === unionId && u.partners[0] === anchor)
      : null;
    if (!union) {
      const unions = state.unions.filter((u) => u.partners[0] === anchor);
      union = unions.find((u) => u.partners.includes(selectedId)) || unions[0] || null;
    }
    if (!union) {
      union = { id: uid("u"), partners: [anchor], childIds: [] };
      state.unions.push(union);
    }
    if (!wouldCycle(state, anchor, child.id)) union.childIds.push(child.id);
    return child.id;
  }

  function addSpouse(state, selectedId, fields) {
    const anchor = anchorOf(state, selectedId);
    const spouse = createPerson(state, fields || { name: "New spouse" });
    const unions = state.unions.filter((u) => u.partners[0] === anchor);
    const open = unions.find((u) => !u.partners[1]);
    if (open) open.partners.push(spouse.id);
    else state.unions.push({ id: uid("u"), partners: [anchor, spouse.id], childIds: [] });
    return { id: spouse.id, anchorId: anchor };
  }

  function addSibling(state, personId, fields) {
    const index = indexState(state);
    let union = index.parentUnion.get(personId);
    let createdParent = null;
    if (!union) {
      createdParent = createPerson(state, { name: "Parent" });
      union = { id: uid("u"), partners: [createdParent.id], childIds: [personId] };
      state.unions.push(union);
    }
    const sibling = createPerson(state, fields || { name: "New brother or sister" });
    union.childIds.push(sibling.id);
    return { id: sibling.id, createdParentId: createdParent && createdParent.id };
  }

  function addParent(state, childId, gender) {
    const index = indexState(state);
    const label = gender === "female" ? "Mother" : "Father";
    let union = index.parentUnion.get(childId);
    if (!union) {
      const parent = createPerson(state, { name: label, gender });
      state.unions.push({ id: uid("u"), partners: [parent.id], childIds: [childId] });
      return parent.id;
    }
    const existing = union.partners
      .map((id) => index.people.get(id))
      .find((p) => p && gender && p.gender === gender);
    if (existing) return existing.id;
    if (union.partners.length >= 2) return existing ? existing.id : union.partners[0];
    const parent = createPerson(state, { name: label, gender });
    const anchor = index.people.get(union.partners[0]);
    const anchorIsChild = state.unions.some((u) => u.childIds.includes(union.partners[0]));
    if (!anchorIsChild && gender === "male" && anchor && anchor.gender !== "male") {
      union.partners = [parent.id, union.partners[0]];
    } else {
      union.partners.push(parent.id);
    }
    return parent.id;
  }

  function deletePerson(state, id) {
    state.people = state.people.filter((p) => p.id !== id);
    if (state.focusId === id) state.focusId = null;
    state.unions = state.unions
      .map((u) => ({
        ...u,
        partners: u.partners.filter((pid) => pid !== id),
        childIds: u.childIds.filter((cid) => cid !== id)
      }))
      .filter((u) => u.partners.length > 0);
  }

  function moveSibling(state, id, dir) {
    const union = indexState(state).parentUnion.get(id);
    if (!union) return false;
    const i = union.childIds.indexOf(id);
    const j = i + dir;
    if (i < 0 || j < 0 || j >= union.childIds.length) return false;
    const next = union.childIds.slice();
    const swap = next[i];
    next[i] = next[j];
    next[j] = swap;
    union.childIds = next;
    return true;
  }

  function parsePersonLine(body) {
    let s = nfc(body).trim().replace(/^\d+(?:\.\d+)*(?:-s\d*)?\.?\s+/, "");
    let gender = "";
    const g = s.match(/\((m|f|o)\)/i);
    if (g) {
      gender = { m: "male", f: "female", o: "other" }[g[1].toLowerCase()];
      s = s.replace(g[0], " ").replace(/\s+/g, " ").trim();
    }
    const parts = s.split("|").map((x) => x.trim());
    const name = parts[0] || "Unnamed";
    let birth = "";
    let death = "";
    let notes = "";
    if (parts[1]) {
      const date = parts[1].replace(/[–—]/g, "-");
      const range = date.match(/^(\d{3,4})\s*-\s*(\d{3,4})$/);
      const one = date.match(/^(\d{3,4})$/);
      const deathOnly = date.match(/^d\.?\s*(\d{3,4})$/i);
      const birthOnly = date.match(/^b\.?\s*(\d{3,4})$/i);
      if (range) {
        birth = range[1];
        death = range[2];
      } else if (one) birth = one[1];
      else if (deathOnly) death = deathOnly[1];
      else if (birthOnly) birth = birthOnly[1];
      else birth = parts[1];
    }
    if (parts.length > 2) notes = parts.slice(2).join(" | ").trim();
    return { name, gender, birth, death, notes };
  }

  function formatPerson(p) {
    let s = personName(p);
    if (p.gender === "male") s += " (m)";
    else if (p.gender === "female") s += " (f)";
    else if (p.gender === "other") s += " (o)";
    let date = "";
    if (p.birth && p.death && /^\d{3,4}$/.test(p.birth) && /^\d{3,4}$/.test(p.death)) date = `${p.birth}-${p.death}`;
    else if (p.death && !p.birth) date = `d.${p.death}`;
    else if (p.birth) date = p.birth;
    if (date) s += ` | ${date}`;
    if (p.notes) s += date ? ` | ${p.notes}` : ` | | ${p.notes}`;
    return s;
  }

  function parseOutline(text) {
    const state = emptyState();
    const errors = [];
    const stack = [];
    let meName = "";
    String(text || "").split(/\r?\n/).forEach((raw, idx) => {
      const lineNo = idx + 1;
      const bansha = raw.match(/^\s*#\s*bansha\s*:\s*(.*?)\s*$/i);
      const me = raw.match(/^\s*#\s*me\s*:\s*(.*?)\s*$/i);
      if (bansha) {
        state.banshaName = nfc(bansha[1]).trim();
        return;
      }
      if (me) {
        meName = nfc(me[1]).trim();
        return;
      }
      if (!raw.trim() || /^\s*(#|\/\/)/.test(raw)) return;
      const expanded = raw.replace(/\t/g, "  ");
      const indent = expanded.match(/^ */)[0].length;
      const isSpouse = /^\s*\+\s*/.test(expanded);
      const body = expanded.replace(/^\s*\+\s*/, "").trim();
      if (!body) {
        errors.push({ line: lineNo, message: "This line has no name." });
        return;
      }
      const fields = parsePersonLine(body);
      if (isSpouse) {
        let host = null;
        for (let i = stack.length - 1; i >= 0; i--) {
          if (stack[i].isSpouse) continue;
          if (stack[i].indent <= indent) {
            host = stack[i];
            break;
          }
        }
        if (!host) {
          errors.push({ line: lineNo, message: "Add the person first, then a + line for the wife or husband." });
          return;
        }
        const spouse = createPerson(state, fields);
        const union = { id: uid("u"), partners: [host.personId, spouse.id], childIds: [] };
        state.unions.push(union);
        host.unionId = union.id;
        while (stack.length && stack[stack.length - 1].indent > indent) stack.pop();
        while (stack.length && stack[stack.length - 1].indent === indent && stack[stack.length - 1].isSpouse) stack.pop();
        stack.push({ indent, personId: spouse.id, unionId: union.id, isSpouse: true });
        return;
      }
      const person = createPerson(state, fields);
      while (stack.length && stack[stack.length - 1].indent >= indent) stack.pop();
      const parent = stack[stack.length - 1];
      if (parent) {
        let union = state.unions.find((u) => u.id === parent.unionId);
        if (!union) {
          union = { id: uid("u"), partners: [parent.personId], childIds: [] };
          state.unions.push(union);
          parent.unionId = union.id;
        }
        union.childIds.push(person.id);
      }
      stack.push({ indent, personId: person.id, unionId: null, isSpouse: false });
    });
    if (meName) {
      const found = state.people.find((p) => personName(p) === meName);
      if (found) state.focusId = found.id;
    }
    return { state, errors };
  }

  function serializeOutline(state) {
    const index = indexState(state);
    const secondary = new Set();
    for (const u of state.unions) if (u.partners[1]) secondary.add(u.partners[1]);
    const roots = state.people.filter((p) => !index.parentUnion.has(p.id) && !(secondary.has(p.id) && !(index.unionsByAnchor.get(p.id) || []).length));
    const lines = [];
    if (state.banshaName) lines.push(`# bansha: ${state.banshaName}`);
    const focus = state.people.find((p) => p.id === state.focusId);
    if (focus) lines.push(`# me: ${personName(focus)}`);
    function emit(id, indent) {
      const p = index.people.get(id);
      if (!p) return;
      lines.push(`${" ".repeat(indent)}${formatPerson(p)}`);
      const unions = index.unionsByAnchor.get(id) || [];
      const solo = unions.filter((u) => !u.partners[1]);
      const married = unions.filter((u) => u.partners[1]);
      for (const u of solo) for (const c of u.childIds) emit(c, indent + 2);
      for (const u of married) {
        const sp = index.people.get(u.partners[1]);
        if (sp) lines.push(`${" ".repeat(indent)}+ ${formatPerson(sp)}`);
        for (const c of u.childIds) emit(c, indent + 2);
      }
    }
    roots.forEach((r, i) => {
      if (lines.length && lines[lines.length - 1] !== "") lines.push("");
      if (i && lines[lines.length - 1] !== "") lines.push("");
      emit(r.id, 0);
    });
    return lines.join("\n").replace(/\n{3,}/g, "\n\n").trim() + (roots.length ? "\n" : "");
  }

  function banshaLabel(state, root, index, rootCount) {
    if (root && root.banshaName) return root.banshaName;
    if (rootCount === 1) return state.banshaName || "Bansha";
    if (index === 0 && state.banshaName) return state.banshaName;
    return state.banshaName ? `${state.banshaName} ${index + 1}` : `Bansha ${index + 1}`;
  }

  function assignNumbers(state) {
    const index = indexState(state);
    const secondary = new Set();
    for (const u of state.unions) if (u.partners[1]) secondary.add(u.partners[1]);
    const roots = state.people.filter((p) => {
      if (index.parentUnion.has(p.id)) return false;
      if (secondary.has(p.id) && !(index.unionsByAnchor.get(p.id) || []).length) return false;
      return true;
    });
    const info = new Map();
    function walk(id, number, generation, bansha, seen) {
      if (seen.has(id)) return;
      seen.add(id);
      info.set(id, { number, generation, bansha, role: "blood", spouseOf: null });
      let n = 0;
      for (const cid of ownChildren(index, id)) {
        n += 1;
        walk(cid, `${number}.${n}`, generation + 1, bansha, seen);
      }
    }
    roots.forEach((root, i) => {
      walk(root.id, "1", 1, banshaLabel(state, root, i, roots.length), new Set());
    });
    const spouseCount = new Map();
    for (const u of state.unions) {
      const host = info.get(u.partners[0]);
      const sid = u.partners[1];
      if (!host || !sid || info.has(sid)) continue;
      const count = (spouseCount.get(u.partners[0]) || 0) + 1;
      spouseCount.set(u.partners[0], count);
      info.set(sid, {
        number: host.number + (count === 1 ? "-s" : `-s${count}`),
        generation: host.generation,
        bansha: host.bansha,
        role: "married-in",
        spouseOf: u.partners[0]
      });
    }
    return info;
  }

  function numberKey(n) {
    const [main, suf] = String(n || "").split("-");
    const segs = main.split(".").filter(Boolean).map((x) => parseInt(x, 10) || 0);
    let s = 0;
    if (suf) s = suf === "s" ? 1 : parseInt(suf.slice(1), 10) || 1;
    return { segs, s };
  }
  function compareNumber(a, b) {
    if (!a && !b) return 0;
    if (!a) return 1;
    if (!b) return -1;
    const A = numberKey(a);
    const B = numberKey(b);
    const len = Math.max(A.segs.length, B.segs.length);
    for (let i = 0; i < len; i++) {
      if (A.segs[i] == null) return -1;
      if (B.segs[i] == null) return 1;
      if (A.segs[i] !== B.segs[i]) return A.segs[i] - B.segs[i];
    }
    return A.s - B.s;
  }

  function genderWord(person, male, female, other) {
    if (!person || person.gender === "male") return male;
    if (person.gender === "female") return female;
    return other;
  }
  function generationName(d, kind, person) {
    const male = kind === "up"
      ? ["Father", "Grandfather"]
      : ["Son", "Grandson"];
    const female = kind === "up"
      ? ["Mother", "Grandmother"]
      : ["Daughter", "Granddaughter"];
    const neutral = kind === "up"
      ? ["Parent", "Grandparent"]
      : ["Child", "Grandchild"];
    if (d === 1) return genderWord(person, male[0], female[0], neutral[0]);
    if (d === 2) return genderWord(person, male[1], female[1], neutral[1]);
    const stem = genderWord(
      person,
      kind === "up" ? "grandfather" : "grandson",
      kind === "up" ? "grandmother" : "granddaughter",
      kind === "up" ? "grandparent" : "grandchild"
    );
    if (d <= 5) return "Great-".repeat(d - 2) + stem;
    return `${d}th-generation ${stem}`;
  }

  function relation(state, focusId, otherId) {
    if (!focusId || !otherId) return "";
    if (focusId === otherId) return "You";
    const index = indexState(state);
    const other = index.people.get(otherId);
    if (!other) return "";
    const parentsOf = (id) => {
      const u = index.parentUnion.get(id);
      return u ? u.partners.filter(Boolean) : [];
    };
    const childrenOf = (id) => ownChildren(index, id);
    const spouseIds = (id) => {
      const ids = [];
      for (const u of state.unions) {
        if (u.partners.includes(id)) {
          for (const pid of u.partners) if (pid !== id) ids.push(pid);
        }
      }
      return ids;
    };
    const sameHome = (a, b) => {
      const ua = index.parentUnion.get(a);
      const ub = index.parentUnion.get(b);
      return !!(ua && ua === ub);
    };
    const ancestors = new Map();
    const aq = parentsOf(focusId).map((id) => [id, 1]);
    while (aq.length) {
      const [cur, d] = aq.shift();
      if (ancestors.has(cur)) continue;
      ancestors.set(cur, d);
      for (const p of parentsOf(cur)) aq.push([p, d + 1]);
    }
    if (ancestors.has(otherId)) return generationName(ancestors.get(otherId), "up", other);
    const descendants = new Map();
    const dq = childrenOf(focusId).map((id) => [id, 1]);
    while (dq.length) {
      const [cur, d] = dq.shift();
      if (descendants.has(cur)) continue;
      descendants.set(cur, d);
      for (const c of childrenOf(cur)) dq.push([c, d + 1]);
    }
    if (descendants.has(otherId)) return generationName(descendants.get(otherId), "down", other);
    if (sameHome(focusId, otherId)) return genderWord(other, "Brother", "Sister", "Sibling");
    const shared = parentsOf(focusId).filter((id) => parentsOf(otherId).includes(id));
    if (shared.length === 1) return genderWord(other, "Half-brother", "Half-sister", "Half-sibling");
    if (spouseIds(focusId).includes(otherId)) return genderWord(other, "Husband", "Wife", "Spouse");
    for (const p of parentsOf(focusId)) {
      if (sameHome(p, otherId)) return genderWord(other, "Uncle", "Aunt", "Parent's sibling");
      if (spouseIds(p).includes(otherId) && !parentsOf(focusId).includes(otherId)) {
        return genderWord(other, "Stepfather", "Stepmother", "Step-parent");
      }
      for (const gp of parentsOf(p)) {
        if (sameHome(gp, otherId)) return genderWord(other, "Great-uncle", "Great-aunt", "Great-uncle or aunt");
      }
    }
    for (const op of parentsOf(otherId)) {
      for (const p of parentsOf(focusId)) {
        if (sameHome(p, op)) return "Cousin";
      }
    }
    const siblings = state.people.filter((p) => p.id !== focusId && sameHome(focusId, p.id)).map((p) => p.id);
    if (siblings.some((sid) => parentsOf(otherId).includes(sid))) return genderWord(other, "Nephew", "Niece", "Sibling's child");
    if (siblings.some((sid) => spouseIds(sid).includes(otherId))) return genderWord(other, "Brother-in-law", "Sister-in-law", "Sibling-in-law");
    for (const sid of spouseIds(focusId)) {
      if (parentsOf(sid).includes(otherId)) return genderWord(other, "Father-in-law", "Mother-in-law", "Parent-in-law");
    }
    for (const [id, dist] of descendants) {
      if (dist === 1 && spouseIds(id).includes(otherId)) return genderWord(other, "Son-in-law", "Daughter-in-law", "Child-in-law");
    }
    return "";
  }

  function roleInFamily(state, id, info) {
    const rel = relation(state, state.focusId, id);
    if (rel) return rel;
    const meta = info.get(id);
    const person = state.people.find((p) => p.id === id);
    if (!meta) return "";
    if (meta.role === "married-in") return "Married in";
    if (meta.generation === 1 && meta.role === "blood") return "Starts the bansha";
    return genderWord(person, "Son", "Daughter", "Child");
  }

  function layout(state) {
    const index = indexState(state);
    const secondary = new Set();
    for (const u of state.unions) if (u.partners[1]) secondary.add(u.partners[1]);
    const roots = state.people.filter((p) => {
      if (index.parentUnion.has(p.id)) return false;
      if (secondary.has(p.id) && !(index.unionsByAnchor.get(p.id) || []).length) return false;
      return true;
    });
    const nodes = [];
    const edges = [];
    const memo = new Map();

    function spousesOf(id) {
      const out = [];
      for (const u of index.unionsByAnchor.get(id) || []) {
        const sid = u.partners[1];
        if (!sid || !index.people.has(sid)) continue;
        if (index.parentUnion.has(sid)) continue;
        out.push(sid);
      }
      return out;
    }
    function partnerBox(id) {
      const spouses = spousesOf(id);
      if (spouses.length <= 1) {
        return {
          spouses,
          mode: "row",
          width: CARD_W + spouses.length * (CARD_W + SPOUSE_GAP),
          height: CARD_H
        };
      }
      return {
        spouses,
        mode: "stack",
        width: CARD_W * 2 + SPOUSE_GAP,
        height: spouses.length * CARD_H + (spouses.length - 1) * 12
      };
    }
    function kidsOf(id, stack) {
      const person = index.people.get(id);
      if (!person || person.collapsed) return [];
      return ownChildren(index, id).filter((cid) => index.people.has(cid) && !stack.has(cid));
    }
    function widthOf(id, stack) {
      if (memo.has(id)) return memo.get(id);
      if (stack.has(id)) return CARD_W;
      stack.add(id);
      const partner = partnerBox(id);
      const kids = kidsOf(id, stack);
      let kidsW = 0;
      kids.forEach((cid, i) => {
        kidsW += widthOf(cid, stack);
        if (i) kidsW += GAP_X;
      });
      stack.delete(id);
      const w = Math.max(partner.width, kidsW, CARD_W);
      memo.set(id, w);
      return w;
    }
    function place(id, left, top, stack) {
      if (stack.has(id)) return;
      stack.add(id);
      const partner = partnerBox(id);
      const spouses = partner.spouses;
      const kids = kidsOf(id, stack);
      const w = widthOf(id, new Set());
      const rowLeft = left + (w - partner.width) / 2;
      const blockH = partner.height;
      if (partner.mode === "row") {
        nodes.push({ id, x: rowLeft, y: top });
        spouses.forEach((sid, i) => {
          nodes.push({ id: sid, x: rowLeft + (i + 1) * (CARD_W + SPOUSE_GAP), y: top });
        });
      } else {
        nodes.push({ id, x: rowLeft, y: top + (blockH - CARD_H) / 2 });
        spouses.forEach((sid, i) => {
          const y = top + i * (CARD_H + 12);
          nodes.push({ id: sid, x: rowLeft + CARD_W + SPOUSE_GAP, y });
        });
      }
      const placedPartners = [id].concat(spouses).map((pid) => nodes.find((n) => n.id === pid)).filter(Boolean);
      if (kids.length) {
        const kidWidths = kids.map((cid) => widthOf(cid, new Set()));
        const kidsW = kidWidths.reduce((a, b) => a + b, 0) + Math.max(0, kids.length - 1) * GAP_X;
        let kx = left + (w - kidsW) / 2;
        const childTop = top + blockH + GAP_Y;
        kids.forEach((cid, i) => {
          place(cid, kx, childTop, stack);
          kx += kidWidths[i] + GAP_X;
        });
        const bracketY = top + blockH + 16;
        const busY = top + blockH + GAP_Y / 2;
        const partnerCenters = placedPartners.map((n) => n.x + CARD_W / 2);
        placedPartners.forEach((n) => {
          edges.push({ x1: n.x + CARD_W / 2, y1: n.y + CARD_H, x2: n.x + CARD_W / 2, y2: bracketY, kind: "parent" });
        });
        edges.push({
          x1: Math.min(...partnerCenters),
          y1: bracketY,
          x2: Math.max(...partnerCenters),
          y2: bracketY,
          kind: "parent"
        });
        const stemX = (Math.min(...partnerCenters) + Math.max(...partnerCenters)) / 2;
        const childCenters = kids.map((cid) => {
          const node = nodes.find((n) => n.id === cid);
          return node ? node.x + CARD_W / 2 : stemX;
        });
        const busXs = childCenters.concat([stemX]);
        edges.push({ x1: stemX, y1: bracketY, x2: stemX, y2: busY, kind: "parent" });
        edges.push({ x1: Math.min(...busXs), y1: busY, x2: Math.max(...busXs), y2: busY, kind: "parent" });
        childCenters.forEach((cx) => edges.push({ x1: cx, y1: busY, x2: cx, y2: childTop, kind: "parent" }));
      }
      if (partner.mode === "row") {
        spouses.forEach((_, i) => {
          const x1 = rowLeft + CARD_W;
          const x2 = rowLeft + (i + 1) * (CARD_W + SPOUSE_GAP);
          const y = top + CARD_H / 2;
          edges.push({ x1, y1: y - 4, x2, y2: y - 4, kind: "marriage" });
          edges.push({ x1, y1: y + 4, x2, y2: y + 4, kind: "marriage" });
        });
      } else {
        spouses.forEach((_, i) => {
          const y = top + i * (CARD_H + 12) + CARD_H / 2;
          const x1 = rowLeft + CARD_W;
          const x2 = rowLeft + CARD_W + SPOUSE_GAP;
          edges.push({ x1, y1: y - 4, x2, y2: y - 4, kind: "marriage" });
          edges.push({ x1, y1: y + 4, x2, y2: y + 4, kind: "marriage" });
        });
      }
      stack.delete(id);
    }

    let x = PAD;
    const flags = [];
    const numbers = assignNumbers(state);
    roots.forEach((root) => {
      const w = widthOf(root.id, new Set());
      place(root.id, x, PAD, new Set());
      const node = nodes.find((n) => n.id === root.id);
      const meta = numbers.get(root.id);
      flags.push({ id: root.id, x: node.x, y: node.y - 26, text: meta ? meta.bansha : "Bansha" });
      x += w + 96;
    });
    let width = PAD;
    let height = PAD;
    nodes.forEach((n) => {
      width = Math.max(width, n.x + CARD_W);
      height = Math.max(height, n.y + CARD_H);
    });
    return { nodes, edges, flags, width: width + PAD, height: height + PAD };
  }

  function descendantCount(state, id) {
    const index = indexState(state);
    let n = 0;
    const stack = ownChildren(index, id).slice();
    const seen = new Set([id]);
    while (stack.length) {
      const cur = stack.pop();
      if (seen.has(cur)) continue;
      seen.add(cur);
      n += 1;
      stack.push(...ownChildren(index, cur));
    }
    return n;
  }

  function normalizeImported(data) {
    const state = emptyState();
    if (!data || typeof data !== "object") return state;
    state.banshaName = nfc(data.banshaName || "").slice(0, 120);
    state.sample = false;
    const people = Array.isArray(data.people) ? data.people : [];
    const used = new Set();
    for (const raw of people) {
      if (!raw || typeof raw !== "object") continue;
      let id = String(raw.id || uid("p"));
      if (used.has(id)) id = uid("p");
      used.add(id);
      state.people.push({
        id,
        name: nfc(raw.name || "Unnamed").slice(0, 200).trim() || "Unnamed",
        gender: ["male", "female", "other"].includes(raw.gender) ? raw.gender : "",
        birth: nfc(raw.birth || "").slice(0, 40),
        death: nfc(raw.death || "").slice(0, 40),
        notes: nfc(raw.notes || "").slice(0, 2000),
        collapsed: !!raw.collapsed,
        banshaName: nfc(raw.banshaName || "").slice(0, 120)
      });
    }
    const ids = new Set(state.people.map((p) => p.id));
    const seenChildren = new Set();
    for (const raw of Array.isArray(data.unions) ? data.unions : []) {
      if (!raw) continue;
      const partners = (Array.isArray(raw.partners) ? raw.partners : []).map(String).filter((id) => ids.has(id)).slice(0, 2);
      if (!partners.length) continue;
      const childIds = (Array.isArray(raw.childIds) ? raw.childIds : []).map(String).filter((id) => {
        if (!ids.has(id) || partners.includes(id) || seenChildren.has(id)) return false;
        seenChildren.add(id);
        return true;
      });
      state.unions.push({ id: String(raw.id || uid("u")), partners, childIds });
    }
    if (data.focusId && ids.has(String(data.focusId))) state.focusId = String(data.focusId);
    return state;
  }

  function recordsOf(state) {
    const info = assignNumbers(state);
    const index = indexState(state);
    const records = state.people.map((person) => {
      const meta = info.get(person.id) || { number: "", generation: "", bansha: "", role: "", spouseOf: null };
      const parents = (index.parentUnion.get(person.id) || { partners: [] }).partners
        .map((id) => index.people.get(id))
        .filter(Boolean);
      const spouses = [];
      for (const u of state.unions) {
        if (!u.partners.includes(person.id)) continue;
        for (const pid of u.partners) {
          if (pid !== person.id && index.people.has(pid)) spouses.push(index.people.get(pid));
        }
      }
      return { person, ...meta, parents, spouses, roleLabel: roleInFamily(state, person.id, info) };
    });
    records.sort((a, b) => compareNumber(a.number, b.number) || personName(a.person).localeCompare(personName(b.person)));
    return { info, records };
  }

  const api = {
    CARD_W,
    CARD_H,
    emptyState,
    parseOutline,
    serializeOutline,
    assignNumbers,
    compareNumber,
    layout,
    relation,
    addChild,
    addSpouse,
    addSibling,
    addParent,
    deletePerson,
    moveSibling,
    normalizeImported,
    recordsOf,
    personName,
    PATTERN
  };

  if (typeof module !== "undefined" && module.exports) module.exports = api;
  if (typeof document === "undefined") return;

  let state = emptyState();
  let mode = "practice";
  let history = [];
  let future = [];
  let selectedId = null;
  let beforeEdit = null;
  let view = { x: 32, y: 24, scale: 1 };
  let lastLayout = null;
  let toastTimer = 0;
  let previewTimer = 0;

  const $ = (id) => document.getElementById(id);

  function storageKey() {
    return mode === "entry" ? STORAGE_KEY : PRACTICE_KEY;
  }
  function seedPractice() {
    const parsed = parseOutline(PATTERN.replace("Our bansha", "Practice bansha") + "# me: Sister\n");
    state = parsed.state;
    state.sample = true;
  }
  function saveLocal() {
    try {
      localStorage.setItem(storageKey(), JSON.stringify(state));
      localStorage.setItem(MODE_KEY, mode);
    } catch (err) { /* private mode */ }
  }
  function loadStored(key) {
    const raw = localStorage.getItem(key);
    if (!raw) return null;
    return normalizeImported(JSON.parse(raw));
  }
  function loadLocal() {
    try {
      const savedMode = localStorage.getItem(MODE_KEY);
      mode = savedMode === "entry" ? "entry" : "practice";
      const stored = loadStored(storageKey());
      if (stored) state = stored;
      else if (mode === "practice") seedPractice();
      else state = emptyState();
    } catch (err) {
      mode = "practice";
      seedPractice();
    }
  }
  function switchMode(next) {
    if (next !== "entry" && next !== "practice") return;
    if (next === mode) return;
    commitPendingEdit();
    saveLocal();
    mode = next;
    history = [];
    future = [];
    selectedId = null;
    beforeEdit = null;
    try {
      const stored = loadStored(storageKey());
      if (stored) state = stored;
      else if (mode === "practice") seedPractice();
      else state = emptyState();
    } catch (err) {
      state = mode === "practice" ? emptyState() : emptyState();
      if (mode === "practice") seedPractice();
    }
    saveLocal();
    renderAll();
    requestAnimationFrame(fit);
    showToast(mode === "practice"
      ? "Practice chart. This stays on this computer and is not the real bansha."
      : "Entry chart. This is the bansha we will fill in. It is saved in this browser.");
  }
  function commitPendingEdit() {
    if (!beforeEdit) return;
    if (JSON.stringify(beforeEdit) !== JSON.stringify(state)) {
      history.push(beforeEdit);
      if (history.length > 60) history.shift();
      future = [];
    }
    beforeEdit = null;
  }
  function transact(fn, options) {
    commitPendingEdit();
    const snap = clone(state);
    fn();
    if (!options || !options.keepSample) state.sample = false;
    if (JSON.stringify(snap) !== JSON.stringify(state)) {
      history.push(snap);
      if (history.length > 60) history.shift();
      future = [];
    }
    saveLocal();
    renderAll();
  }
  function undo() {
    commitPendingEdit();
    if (!history.length) return;
    future.push(clone(state));
    state = history.pop();
    saveLocal();
    renderAll();
  }
  function redo() {
    commitPendingEdit();
    if (!future.length) return;
    history.push(clone(state));
    state = future.pop();
    saveLocal();
    renderAll();
  }

  function showToast(message, undoable) {
    const toast = $("toast");
    toast.replaceChildren();
    toast.append(document.createTextNode(message));
    if (undoable) {
      const btn = document.createElement("button");
      btn.type = "button";
      btn.textContent = "Undo";
      btn.addEventListener("click", () => { undo(); hideToast(); });
      toast.append(btn);
    }
    toast.hidden = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(hideToast, 4600);
  }
  function hideToast() { $("toast").hidden = true; }

  function yearsOf(p) {
    if (p.birth && p.death) return `${p.birth} – ${p.death}`;
    if (p.birth) return String(p.birth);
    if (p.death) return `died ${p.death}`;
    return "";
  }

  function renderAll() {
    if (selectedId && !state.people.some((p) => p.id === selectedId)) selectedId = null;
    $("bansha-name").value = state.banshaName || "";
    const { info, records } = recordsOf(state);
    const gens = records.reduce((m, r) => Math.max(m, r.generation || 0), 0);
    const blood = records.filter((r) => r.role === "blood").length;
    const lead = mode === "practice" ? "Practice" : "Entry";
    $("stats").textContent = state.people.length
      ? `${lead} · ${state.people.length} people · ${gens} generation${gens === 1 ? "" : "s"} · ${blood} in the blood line`
      : `${lead} · empty, saved in this browser`;
    $("mode-practice").setAttribute("aria-pressed", mode === "practice" ? "true" : "false");
    $("mode-entry").setAttribute("aria-pressed", mode === "entry" ? "true" : "false");
    $("btn-undo").disabled = history.length === 0;
    $("btn-redo").disabled = future.length === 0;
    renderCards(info);
    renderSide(info);
    if (!$("register-panel").hidden) renderRegister();
    let sampleFlag = $("sample-flag");
    if (!sampleFlag) {
      sampleFlag = document.createElement("p");
      sampleFlag.id = "sample-flag";
      sampleFlag.className = "sample-flag";
      $("viewport").before(sampleFlag);
    }
    sampleFlag.hidden = mode !== "practice" && !state.sample;
    sampleFlag.textContent = mode === "practice"
      ? "Practice only, saved in this browser. Try add, edit, delete, and the numbers. Entry is a separate chart and stays empty until you switch to it."
      : "This sample is not your family. Clear it before real entry.";
    const empty = $("empty");
    empty.hidden = state.people.length > 0;
    if (!state.people.length) {
      empty.replaceChildren();
      const card = document.createElement("div");
      card.className = "empty-card";
      const h = document.createElement("h2");
      h.textContent = mode === "entry" ? "Ready for entry" : "Start the bansha";
      const p = document.createElement("p");
      p.textContent = mode === "entry"
        ? "This is the real bansha, saved only in this browser. Begin with the oldest person. The first child becomes 1.1, the next 1.2, and a wife or husband becomes 1-s. Practice is separate, so trying things there will not change this chart."
        : "Begin with the oldest person. The first child becomes 1.1, the next 1.2, and a wife or husband becomes 1-s. Add, reorder, or delete someone and the numbers move with the family.";
      const row = document.createElement("div");
      row.className = "row-actions";
      const write = document.createElement("button");
      write.type = "button";
      write.className = "primary";
      write.textContent = "Write the family";
      write.addEventListener("click", openWrite);
      const add = document.createElement("button");
      add.type = "button";
      add.textContent = "Add the oldest person";
      add.addEventListener("click", () => addRoot());
      const sample = document.createElement("button");
      sample.type = "button";
      if (mode === "entry") {
        sample.textContent = "Back to practice";
        sample.addEventListener("click", () => switchMode("practice"));
      } else {
        sample.textContent = "Load a small example";
        sample.addEventListener("click", loadSample);
      }
      row.append(write, add, sample);
      card.append(h, p, row);
      empty.append(card);
    }
  }

  function renderCards(info) {
    lastLayout = layout(state);
    const world = $("world");
    world.style.width = lastLayout.width + "px";
    world.style.height = lastLayout.height + "px";
    const svg = $("lines");
    svg.setAttribute("width", lastLayout.width);
    svg.setAttribute("height", lastLayout.height);
    svg.replaceChildren();
    for (const edge of lastLayout.edges) {
      const line = document.createElementNS("http://www.w3.org/2000/svg", "line");
      line.setAttribute("x1", edge.x1);
      line.setAttribute("y1", edge.y1);
      line.setAttribute("x2", edge.x2);
      line.setAttribute("y2", edge.y2);
      line.setAttribute("stroke", edge.kind === "marriage" ? "#1f4d38" : "#b7ad9c");
      line.setAttribute("stroke-width", edge.kind === "marriage" ? "1.4" : "1.5");
      svg.append(line);
    }
    const cards = $("cards");
    cards.replaceChildren();
    const seen = new Set();
    for (const flag of lastLayout.flags) {
      const el = document.createElement("div");
      el.className = "bansha-flag";
      el.dataset.id = flag.id;
      el.style.left = flag.x + "px";
      el.style.top = flag.y + "px";
      el.textContent = flag.text;
      cards.append(el);
    }
    for (const node of lastLayout.nodes) {
      if (seen.has(node.id)) continue;
      seen.add(node.id);
      cards.append(cardElement(node, info));
    }
    applyView();
  }

  function cardElement(node, info) {
    const meta = info.get(node.id);
    const person = state.people.find((p) => p.id === node.id);
    const card = document.createElement("article");
    card.className = "card";
    card.dataset.id = person.id;
    card.tabIndex = 0;
    if (person.gender) card.classList.add(person.gender);
    if (meta && meta.role === "married-in") card.classList.add("married-in");
    if (person.id === selectedId) card.classList.add("selected");
    if (person.id === state.focusId) card.classList.add("is-me");
    card.style.left = node.x + "px";
    card.style.top = node.y + "px";
    const titleBits = [meta && meta.number, personName(person), yearsOf(person), meta && meta.bansha].filter(Boolean);
    card.title = titleBits.join(" · ");

    const top = document.createElement("div");
    top.className = "card-top";
    const num = document.createElement("span");
    num.className = "card-num";
    num.textContent = (meta && meta.number) || "—";
    const gen = document.createElement("span");
    gen.className = "card-gen";
    gen.textContent = meta && meta.generation ? `Gen ${meta.generation}` : "";
    top.append(num, gen);

    const name = document.createElement("div");
    name.className = "card-name";
    name.textContent = personName(person);
    const years = document.createElement("div");
    years.className = "card-years";
    years.textContent = yearsOf(person);
    const role = document.createElement("div");
    role.className = "card-role";
    role.textContent = roleInFamily(state, person.id, info);

    card.append(top, name, years, role);
    const kids = descendantCount(state, person.id);
    if (kids) {
      const fold = document.createElement("button");
      fold.type = "button";
      fold.className = "fold";
      fold.textContent = person.collapsed ? `Show ${kids}` : `Hide ${kids}`;
      fold.addEventListener("click", (e) => {
        e.stopPropagation();
        transact(() => { person.collapsed = !person.collapsed; });
      });
      card.append(fold);
    }
    if (person.id === selectedId) {
      const tools = document.createElement("div");
      tools.className = "tools";
      tools.append(
        toolButton("Child", () => quickAddChild(person.id)),
        toolButton("Spouse", () => quickAddSpouse(person.id)),
        toolButton("Sibling", () => quickAddSibling(person.id)),
        toolButton("Delete", () => removePerson(person.id))
      );
      card.append(tools);
    }
    card.addEventListener("click", (e) => {
      if (e.target.closest("button")) return;
      selectPerson(person.id);
    });
    card.addEventListener("keydown", (e) => {
      if (e.key === "Enter") selectPerson(person.id);
    });
    return card;
  }

  function toolButton(label, fn) {
    const btn = document.createElement("button");
    btn.type = "button";
    btn.textContent = label;
    if (label === "Delete") btn.className = "danger";
    btn.addEventListener("click", (e) => {
      e.stopPropagation();
      fn();
    });
    return btn;
  }

  function renderSide(info) {
    const body = $("side-body");
    body.replaceChildren();
    if (!selectedId) {
      const h = document.createElement("h2");
      h.textContent = "The family line";
      const p = document.createElement("p");
      p.className = "gen-line";
      p.textContent = mode === "practice"
        ? "This is practice. Select a person to edit, delete, or add a child. Numbers follow the order. Your real entry chart is untouched."
        : "This is the real bansha. Select a person to edit them, or write the whole line at once. Numbers follow the order of children.";
      const note = document.createElement("p");
      note.className = "save-note";
      note.textContent = "Saved in this browser only. Export a backup before you switch computers.";
      body.append(h, p, note);
      return;
    }
    const person = state.people.find((p) => p.id === selectedId);
    const meta = info.get(person.id) || {};
    const index = indexState(state);
    const kicker = document.createElement("p");
    kicker.className = "kicker";
    kicker.textContent = meta.bansha || "Bansha";
    const number = document.createElement("p");
    number.className = "person-number";
    number.textContent = meta.number || "—";
    const gen = document.createElement("p");
    gen.className = "gen-line";
    gen.textContent = [
      meta.generation ? `Generation ${meta.generation}` : "",
      meta.role === "married-in" ? "Married into the bansha" : "In the blood line",
      roleInFamily(state, person.id, info)
    ].filter(Boolean).join(" · ");
    const path = document.createElement("div");
    path.className = "path";
    const chain = ancestorChain(person.id);
    chain.forEach((id, i) => {
      if (i) path.append(document.createTextNode(" / "));
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "link";
      const m = info.get(id);
      const who = state.people.find((p) => p.id === id);
      btn.textContent = `${(m && m.number) || ""} ${personName(who)}`.trim();
      btn.addEventListener("click", () => selectPerson(id, true));
      path.append(btn);
    });

    const form = document.createElement("div");
    form.className = "form-grid";
    form.append(field("Name", "text", person.name, (v) => { person.name = nfc(v); paintName(person.id); }));
    const gender = document.createElement("label");
    gender.className = "stack";
    gender.append(document.createTextNode("Gender"));
    const select = document.createElement("select");
    [["", "Not set"], ["male", "Male"], ["female", "Female"], ["other", "Other"]].forEach(([value, label]) => {
      const opt = document.createElement("option");
      opt.value = value;
      opt.textContent = label;
      if (person.gender === value) opt.selected = true;
      select.append(opt);
    });
    select.addEventListener("change", () => transact(() => { person.gender = select.value; }));
    gender.append(select);
    form.append(gender);
    const dates = document.createElement("div");
    dates.className = "two";
    dates.append(
      field("Born", "text", person.birth, (v) => { person.birth = nfc(v); paintYears(person.id); }),
      field("Died", "text", person.death, (v) => { person.death = nfc(v); paintYears(person.id); })
    );
    form.append(dates);
    const notes = document.createElement("label");
    notes.className = "stack";
    notes.append(document.createTextNode("Notes"));
    const area = document.createElement("textarea");
    area.value = person.notes || "";
    area.addEventListener("focus", () => { if (!beforeEdit) beforeEdit = clone(state); });
    area.addEventListener("input", () => { person.notes = nfc(area.value); saveLocal(); });
    area.addEventListener("blur", () => { commitPendingEdit(); saveLocal(); });
    notes.append(area);
    form.append(notes);

    const isFounder = !index.parentUnion.has(person.id) && meta.role === "blood";
    if (isFounder) {
      form.append(field("Name of this line", "text", person.banshaName || "", (v) => { person.banshaName = nfc(v); }));
    }

    const actions = document.createElement("div");
    actions.className = "row-actions";
    actions.append(
      actionButton(person.id === state.focusId ? "This is you" : "Mark as me", () => {
        transact(() => { state.focusId = state.focusId === person.id ? null : person.id; });
      }),
      actionButton("Add child", () => quickAddChild(person.id)),
      actionButton("Add spouse", () => quickAddSpouse(person.id)),
      actionButton("Add brother or sister", () => quickAddSibling(person.id)),
      actionButton("Add father", () => quickAddParent(person.id, "male")),
      actionButton("Add mother", () => quickAddParent(person.id, "female"))
    );

    body.append(kicker, number, gen, path, form, actions);
    body.append(relativeList("Parents", parentsOf(person.id), info, null));
    body.append(relativeList("Wife or husband", spousesOfPerson(person.id), info, null));
    body.append(relativeList("Brothers and sisters", siblingsOf(person.id), info, person.id));
    body.append(relativeList("Children", childrenOfPerson(person.id), info, person.id));

    const danger = document.createElement("div");
    danger.className = "danger-row";
    const del = document.createElement("button");
    del.type = "button";
    del.className = "danger";
    del.textContent = "Delete this person";
    del.addEventListener("click", () => removePerson(person.id));
    danger.append(del);
    const note = document.createElement("p");
    note.className = "save-note";
    note.textContent = "Delete is immediate. Undo brings the person back. Saved in this browser only — export a backup when the bansha gets large.";
    body.append(danger, note);
  }

  function field(label, type, value, onInput) {
    const wrap = document.createElement("label");
    wrap.className = "stack";
    wrap.append(document.createTextNode(label));
    const input = document.createElement("input");
    input.type = type;
    input.value = value || "";
    if (label === "Name") input.id = "person-name";
    input.addEventListener("focus", () => { if (!beforeEdit) beforeEdit = clone(state); });
    input.addEventListener("input", () => { onInput(input.value); saveLocal(); });
    input.addEventListener("blur", () => {
      commitPendingEdit();
      saveLocal();
      paintDerived();
      if (!$("register-panel").hidden) renderRegister();
    });
    wrap.append(input);
    return wrap;
  }
  function actionButton(label, fn) {
    const btn = document.createElement("button");
    btn.type = "button";
    btn.textContent = label;
    btn.addEventListener("click", fn);
    return btn;
  }
  function paintName(id) {
    const card = document.querySelector(`.card[data-id="${cssEscape(id)}"] .card-name`);
    const person = state.people.find((p) => p.id === id);
    if (card && person) card.textContent = personName(person);
  }
  function paintYears(id) {
    const card = document.querySelector(`.card[data-id="${cssEscape(id)}"] .card-years`);
    const person = state.people.find((p) => p.id === id);
    if (card && person) card.textContent = yearsOf(person);
  }
  function paintDerived() {
    const info = assignNumbers(state);
    const gens = Math.max(0, ...Array.from(info.values()).map((m) => m.generation || 0));
    const blood = Array.from(info.values()).filter((m) => m.role === "blood").length;
    const lead = mode === "practice" ? "Practice" : "Entry";
    $("stats").textContent = state.people.length
      ? `${lead} · ${state.people.length} people · ${gens} generation${gens === 1 ? "" : "s"} · ${blood} in the blood line`
      : `${lead} · empty, saved in this browser`;
    document.querySelectorAll(".bansha-flag").forEach((el) => {
      const meta = info.get(el.dataset.id);
      if (meta) el.textContent = meta.bansha;
    });
    const selected = state.people.find((p) => p.id === selectedId);
    const kicker = document.querySelector(".kicker");
    if (selected && kicker) kicker.textContent = (info.get(selected.id) || {}).bansha || "Bansha";
  }
  function cssEscape(id) {
    return window.CSS && CSS.escape ? CSS.escape(id) : id.replace(/[^a-zA-Z0-9_-]/g, "");
  }

  function parentsOf(id) {
    const u = indexState(state).parentUnion.get(id);
    return u ? u.partners.filter(Boolean) : [];
  }
  function spousesOfPerson(id) {
    const ids = [];
    for (const u of state.unions) {
      if (!u.partners.includes(id)) continue;
      for (const pid of u.partners) if (pid && pid !== id && !ids.includes(pid)) ids.push(pid);
    }
    return ids;
  }
  function siblingsOf(id) {
    const u = indexState(state).parentUnion.get(id);
    return u ? u.childIds.filter((cid) => cid !== id) : [];
  }
  function childrenOfPerson(id) {
    const anchor = anchorOf(state, id);
    return ownChildren(indexState(state), anchor);
  }
  function ancestorChain(id) {
    const chain = [];
    let cur = id;
    const seen = new Set();
    while (cur && !seen.has(cur)) {
      seen.add(cur);
      chain.unshift(cur);
      const u = indexState(state).parentUnion.get(cur);
      cur = u ? u.partners[0] : null;
    }
    return chain;
  }

  function relativeList(title, ids, info, movableId) {
    const block = document.createElement("section");
    block.className = "list-block";
    const h = document.createElement("h3");
    h.textContent = title;
    block.append(h);
    if (!ids.length) {
      const p = document.createElement("p");
      p.className = "gen-line";
      p.textContent = "None yet";
      block.append(p);
      return block;
    }
    ids.forEach((id) => {
      const person = state.people.find((p) => p.id === id);
      if (!person) return;
      const row = document.createElement("div");
      row.className = "person-row";
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "link";
      const meta = info.get(id);
      btn.textContent = `${(meta && meta.number) || ""}  ${personName(person)}`.trim();
      btn.addEventListener("click", () => selectPerson(id, true));
      row.append(btn);
      if (movableId && id === movableId) {
        row.append(mini("Earlier", () => transact(() => moveSibling(state, id, -1))));
        row.append(mini("Later", () => transact(() => moveSibling(state, id, 1))));
      } else if (movableId && title !== "Parents" && title !== "Wife or husband") {
        row.append(mini("Earlier", () => transact(() => moveSibling(state, id, -1))));
        row.append(mini("Later", () => transact(() => moveSibling(state, id, 1))));
      }
      block.append(row);
    });
    if (movableId && (title === "Brothers and sisters" || title === "Children")) {
      const help = document.createElement("p");
      help.className = "gen-line";
      help.textContent = "Earlier and later change the bansha number.";
      block.append(help);
    }
    return block;
  }
  function mini(label, fn) {
    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = "mini";
    btn.textContent = label;
    btn.addEventListener("click", fn);
    return btn;
  }

  function selectPerson(id, pan) {
    selectedId = id;
    renderAll();
    if (pan) panTo(id);
    const input = $("person-name");
    if (pan && input) {
      input.focus();
      input.select();
    }
  }
  function addRoot() {
    let id = null;
    transact(() => {
      const person = createPerson(state, { name: "New person" });
      id = person.id;
    });
    if (id) selectPerson(id, true);
  }
  function quickAddChild(id) {
    let childId = null;
    let number = "";
    transact(() => { childId = addChild(state, id, { name: "New child" }); });
    number = (assignNumbers(state).get(childId) || {}).number || "";
    showToast(number ? `Child added as ${number}.` : "Child added.");
    selectPerson(childId, true);
  }
  function quickAddSpouse(id) {
    let result = null;
    transact(() => { result = addSpouse(state, id, { name: "New spouse" }); });
    const number = (assignNumbers(state).get(result.id) || {}).number || "";
    const anchor = state.people.find((p) => p.id === result.anchorId);
    const extra = result.anchorId !== id && anchor ? ` Added on ${personName(anchor)}'s line, which carries the bansha number.` : "";
    showToast((number ? `Spouse added as ${number}.` : "Spouse added.") + extra);
    selectPerson(result.id, true);
  }
  function quickAddSibling(id) {
    let result = null;
    transact(() => { result = addSibling(state, id, { name: "New brother or sister" }); });
    if (result.createdParentId) showToast("A parent was added so the brothers and sisters share one bansha. Rename that parent.");
    selectPerson(result.id, true);
  }
  function quickAddParent(id, gender) {
    let parentId = null;
    transact(() => { parentId = addParent(state, id, gender); });
    selectPerson(parentId, true);
  }
  function removePerson(id) {
    const person = state.people.find((p) => p.id === id);
    const number = (assignNumbers(state).get(id) || {}).number;
    const label = `${personName(person)}${number ? ` (${number})` : ""}`;
    transact(() => deletePerson(state, id));
    showToast(`${label} removed. Numbers updated.`, true);
  }

  function applyView() {
    $("world").style.transform = `translate(${view.x}px, ${view.y}px) scale(${view.scale})`;
    $("zoom-label").textContent = `${Math.round(view.scale * 100)}%`;
  }
  function panTo(id) {
    if (!lastLayout) return;
    const node = lastLayout.nodes.find((n) => n.id === id);
    if (!node) return;
    const vp = $("viewport").getBoundingClientRect();
    view.x = vp.width / 2 - (node.x + CARD_W / 2) * view.scale;
    view.y = vp.height / 2 - (node.y + CARD_H / 2) * view.scale;
    applyView();
  }
  function fit() {
    if (!lastLayout || !state.people.length) return;
    const vp = $("viewport").getBoundingClientRect();
    const scale = Math.min(1, vp.width / lastLayout.width, vp.height / lastLayout.height) * 0.92;
    view.scale = Math.max(0.25, scale || 1);
    view.x = Math.max(16, (vp.width - lastLayout.width * view.scale) / 2);
    view.y = 20;
    applyView();
  }

  function openWrite() {
    $("outline").value = state.people.length ? serializeOutline(state) : "";
    $("write-panel").hidden = false;
    previewOutline();
  }
  function previewOutline() {
    const parsed = parseOutline($("outline").value);
    const box = $("outline-preview");
    box.replaceChildren();
    const meta = $("write-meta");
    if (parsed.errors.length) {
      parsed.errors.slice(0, 8).forEach((err) => {
        const p = document.createElement("p");
        p.className = "error";
        p.textContent = `Line ${err.line}: ${err.message}`;
        box.append(p);
      });
    }
    const { records } = recordsOf(parsed.state);
    const gens = records.reduce((m, r) => Math.max(m, r.generation || 0), 0);
    meta.textContent = `${parsed.state.people.length} people · ${gens || 0} generations`;
    records.forEach((r) => {
      const line = document.createElement("div");
      line.className = "preview-line";
      const n = document.createElement("span");
      n.className = "n";
      n.textContent = r.number || "—";
      line.append(n, document.createTextNode(`${personName(r.person)}  ${r.role === "married-in" ? "married in" : "gen " + r.generation}`));
      box.append(line);
    });
    $("write-apply").disabled = parsed.errors.length > 0;
  }
  function applyOutline() {
    const parsed = parseOutline($("outline").value);
    if (parsed.errors.length) return;
    if (state.people.length && !confirm("Replace the current chart with what you wrote? You can undo this.")) return;
    const name = state.banshaName;
    transact(() => {
      state = parsed.state;
      if (!state.banshaName) state.banshaName = name;
      state.sample = false;
    });
    $("write-panel").hidden = true;
    showToast("Chart updated. Numbers were added automatically.");
    requestAnimationFrame(fit);
  }
  function loadSample() {
    if (state.people.length && !confirm("Replace the current chart with the example?")) return;
    const parsed = parseOutline(PATTERN.replace("Our bansha", "Example bansha") + "# me: Sister\n");
    transact(() => {
      state = parsed.state;
      state.sample = true;
    }, { keepSample: true });
    showToast("Example loaded. It is not your family. Clear it when you are ready to write.");
    requestAnimationFrame(fit);
  }

  function renderRegister() {
    const { records } = recordsOf(state);
    const q = ($("register-filter").value || "").trim().toLowerCase();
    const bloodOnly = $("blood-only").checked;
    const rows = records.filter((r) => {
      if (bloodOnly && r.role !== "blood") return false;
      if (!q) return true;
      const blob = [r.number, personName(r.person), r.roleLabel, r.person.notes, r.bansha].join(" ").toLowerCase();
      return blob.includes(q);
    });
    const blood = records.filter((r) => r.role === "blood").length;
    const gens = records.reduce((m, r) => Math.max(m, r.generation || 0), 0);
    $("register-title").textContent = state.banshaName || "Numbered family line";
    $("register-summary").textContent = `${rows.length} shown · ${blood} blood line · ${records.length - blood} married in · ${gens} generations. Numbers follow the order of children.`;
    const body = $("register-body");
    body.replaceChildren();
    rows.forEach((r) => {
      const tr = document.createElement("tr");
      if (r.person.id === selectedId) tr.className = "selected-row";
      const cells = [
        r.number || "—",
        r.generation || "",
        personName(r.person),
        r.roleLabel || "",
        r.person.birth || "",
        r.person.death || "",
        r.parents.map(personName).join(", "),
        r.spouses.map(personName).join(", "),
        r.bansha || ""
      ];
      cells.forEach((text, i) => {
        const td = document.createElement("td");
        if (i === 0) td.className = "mono";
        td.textContent = text;
        tr.append(td);
      });
      tr.addEventListener("click", () => {
        selectPerson(r.person.id, true);
        renderRegister();
      });
      body.append(tr);
    });
  }
  function registerText() {
    const { records } = recordsOf(state);
    const header = ["No.", "Gen", "Name", "In the family", "Born", "Died", "Parents", "Spouse", "Bansha"];
    const lines = [header.join("\t")];
    records.forEach((r) => {
      lines.push([
        r.number || "",
        r.generation || "",
        personName(r.person),
        r.roleLabel || "",
        r.person.birth || "",
        r.person.death || "",
        r.parents.map(personName).join(", "),
        r.spouses.map(personName).join(", "),
        r.bansha || ""
      ].join("\t"));
    });
    return lines.join("\n");
  }

  function searchPeople(q) {
    const query = q.trim().toLowerCase();
    if (!query) return [];
    const { records } = recordsOf(state);
    return records
      .map((r) => {
        const name = personName(r.person).toLowerCase();
        const num = (r.number || "").toLowerCase();
        let score = 0;
        if (num === query) score = 100;
        else if (num.startsWith(query)) score = 80;
        else if (name.startsWith(query)) score = 60;
        else if (name.includes(query)) score = 40;
        return { r, score };
      })
      .filter((x) => x.score)
      .sort((a, b) => b.score - a.score || compareNumber(a.r.number, b.r.number))
      .slice(0, 12);
  }

  function bind() {
    $("bansha-name").addEventListener("focus", () => { if (!beforeEdit) beforeEdit = clone(state); });
    $("bansha-name").addEventListener("input", () => { state.banshaName = nfc($("bansha-name").value); saveLocal(); });
    $("bansha-name").addEventListener("blur", () => { commitPendingEdit(); saveLocal(); paintDerived(); });
    $("mode-practice").addEventListener("click", () => switchMode("practice"));
    $("mode-entry").addEventListener("click", () => switchMode("entry"));
    $("btn-undo").addEventListener("click", undo);
    $("btn-redo").addEventListener("click", redo);
    $("btn-add").addEventListener("click", addRoot);
    $("btn-write").addEventListener("click", openWrite);
    $("btn-register").addEventListener("click", () => { $("register-panel").hidden = false; renderRegister(); });
    $("register-close").addEventListener("click", () => { $("register-panel").hidden = true; });
    $("write-close").addEventListener("click", () => { $("write-panel").hidden = true; });
    $("write-apply").addEventListener("click", applyOutline);
    $("write-pattern").addEventListener("click", () => { $("outline").value = PATTERN; previewOutline(); });
    $("write-clear").addEventListener("click", () => {
      if (!state.people.length) return;
      if (!confirm("Clear the whole chart? You can undo this.")) return;
      transact(() => { state = emptyState(); selectedId = null; });
      $("write-panel").hidden = true;
    });
    $("outline").addEventListener("input", () => {
      clearTimeout(previewTimer);
      previewTimer = setTimeout(previewOutline, 160);
    });
    $("register-filter").addEventListener("input", renderRegister);
    $("blood-only").addEventListener("change", renderRegister);
    $("register-copy").addEventListener("click", async () => {
      const text = registerText();
      try {
        await navigator.clipboard.writeText(text);
        showToast("Register copied.");
      } catch (err) {
        showToast("Could not copy. Select the register and copy it yourself.");
      }
    });
    $("register-print").addEventListener("click", () => window.print());
    $("btn-export").addEventListener("click", () => {
      const blob = new Blob([JSON.stringify(state, null, 2)], { type: "application/json" });
      const a = document.createElement("a");
      const slug = (state.banshaName || "bansha").toLowerCase().replace(/[^a-z0-9\u0900-\u097f]+/gi, "-").replace(/^-|-$/g, "");
      a.href = URL.createObjectURL(blob);
      a.download = `${mode}-${slug || "bansha"}-chart.json`;
      a.click();
      URL.revokeObjectURL(a.href);
    });
    $("btn-import").addEventListener("click", () => $("import-file").click());
    $("import-file").addEventListener("change", () => {
      const file = $("import-file").files && $("import-file").files[0];
      $("import-file").value = "";
      if (!file) return;
      const reader = new FileReader();
      reader.onload = () => {
        try {
          const next = normalizeImported(JSON.parse(String(reader.result)));
          if (!next.people.length) throw new Error("empty");
          transact(() => { state = next; selectedId = null; });
          showToast("Chart imported.");
          requestAnimationFrame(fit);
        } catch (err) {
          showToast("That file is not a bansha chart.");
        }
      };
      reader.readAsText(file);
    });
    $("zoom-in").addEventListener("click", () => { view.scale = Math.min(1.75, view.scale * 1.12); applyView(); });
    $("zoom-out").addEventListener("click", () => { view.scale = Math.max(0.25, view.scale / 1.12); applyView(); });
    $("zoom-fit").addEventListener("click", fit);
    $("btn-expand").addEventListener("click", () => transact(() => state.people.forEach((p) => { p.collapsed = false; })));

    const search = $("search");
    const results = $("search-results");
    search.addEventListener("input", () => {
      const found = searchPeople(search.value);
      results.replaceChildren();
      if (!found.length) { results.hidden = true; return; }
      found.forEach(({ r }, i) => {
        const btn = document.createElement("button");
        btn.type = "button";
        btn.setAttribute("aria-selected", i === 0 ? "true" : "false");
        const num = document.createElement("span");
        num.className = "mono";
        num.textContent = r.number || "—";
        btn.append(num, document.createTextNode(personName(r.person)));
        btn.addEventListener("click", () => {
          results.hidden = true;
          search.value = "";
          selectPerson(r.person.id, true);
        });
        results.append(btn);
      });
      results.hidden = false;
    });
    search.addEventListener("keydown", (e) => {
      if (e.key === "Enter") {
        const first = results.querySelector("button");
        if (first) first.click();
      }
      if (e.key === "Escape") results.hidden = true;
    });
    document.addEventListener("click", (e) => {
      if (!e.target.closest(".search")) results.hidden = true;
    });

    const viewport = $("viewport");
    let drag = null;
    viewport.addEventListener("pointerdown", (e) => {
      if (e.target.closest(".card, button, a, input, textarea, select")) return;
      drag = { x: e.clientX, y: e.clientY, vx: view.x, vy: view.y };
      viewport.classList.add("dragging");
      viewport.setPointerCapture(e.pointerId);
    });
    viewport.addEventListener("pointermove", (e) => {
      if (!drag) return;
      view.x = drag.vx + (e.clientX - drag.x);
      view.y = drag.vy + (e.clientY - drag.y);
      applyView();
    });
    viewport.addEventListener("pointerup", () => {
      drag = null;
      viewport.classList.remove("dragging");
    });
    viewport.addEventListener("wheel", (e) => {
      e.preventDefault();
      const delta = e.deltaY < 0 ? 1.08 : 0.92;
      const next = Math.min(1.75, Math.max(0.25, view.scale * delta));
      const rect = viewport.getBoundingClientRect();
      const mx = e.clientX - rect.left;
      const my = e.clientY - rect.top;
      const k = next / view.scale;
      view.x = mx - (mx - view.x) * k;
      view.y = my - (my - view.y) * k;
      view.scale = next;
      applyView();
    }, { passive: false });

    document.addEventListener("keydown", (e) => {
      const typing = e.target.closest("input, textarea, select");
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "z" && !typing) {
        e.preventDefault();
        if (e.shiftKey) redo(); else undo();
      } else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "y" && !typing) {
        e.preventDefault();
        redo();
      } else if ((e.key === "Delete" || e.key === "Backspace") && !typing && selectedId) {
        e.preventDefault();
        removePerson(selectedId);
      } else if (e.key === "Escape") {
        $("write-panel").hidden = true;
        $("register-panel").hidden = true;
        results.hidden = true;
      }
    });
    $("write-panel").addEventListener("click", (e) => { if (e.target === $("write-panel")) $("write-panel").hidden = true; });
    $("register-panel").addEventListener("click", (e) => { if (e.target === $("register-panel")) $("register-panel").hidden = true; });
  }

  loadLocal();
  bind();
  renderAll();
  saveLocal();
  requestAnimationFrame(fit);
})();
