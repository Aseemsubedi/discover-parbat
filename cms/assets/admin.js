document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.cms-image-upload').forEach((wrap) => {
    const targetId = wrap.dataset.target;
    const input = document.getElementById(targetId);
    const fileInput = wrap.querySelector('.cms-image-file');
    const preview = wrap.querySelector('.cms-image-preview');
    const status = wrap.querySelector('.cms-image-status');
    const changeBtn = wrap.querySelector('.cms-image-change');
    const clearBtn = wrap.querySelector('.cms-image-clear');

    if (!input || !fileInput || !preview) return;

    const setStatus = (message, isError) => {
      if (!status) return;
      status.textContent = message || '';
      status.classList.toggle('is-error', Boolean(isError));
    };

    const renderPreview = (url) => {
      preview.classList.toggle('is-empty', !url);
      preview.innerHTML = url
        ? `<img src="${url}" alt="Image preview">`
        : `<div class="cms-image-placeholder">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5a2 2 0 0 1 2-2h5l2 2h7a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V5Z"/><path d="M8 13l2.5 3 2-2.5L16 16"/></svg>
            <strong>Click to upload image</strong>
            <span>JPG, PNG, WebP or GIF · max 5 MB</span>
          </div>`;
      if (clearBtn) {
        clearBtn.style.display = url ? '' : 'none';
      }
    };

    const openPicker = () => fileInput.click();

    preview.addEventListener('click', openPicker);
    changeBtn?.addEventListener('click', openPicker);

    clearBtn?.addEventListener('click', () => {
      input.value = '';
      renderPreview('');
      setStatus('Image removed. Save the form to apply.', false);
    });

    fileInput.addEventListener('change', async () => {
      const file = fileInput.files && fileInput.files[0];
      if (!file) return;

      setStatus('Uploading…', false);
      const formData = new FormData();
      formData.append('image', file);

      try {
        const res = await fetch('upload-image.php', {
          method: 'POST',
          body: formData,
          credentials: 'same-origin'
        });
        const data = await res.json();
        if (!res.ok || !data.ok) {
          throw new Error(data.error || 'Upload failed');
        }
        input.value = data.path;
        renderPreview(data.url);
        setStatus('Image uploaded.', false);
      } catch (error) {
        setStatus(error.message || 'Upload failed', true);
      } finally {
        fileInput.value = '';
      }
    });
  });
});
