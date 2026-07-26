document.addEventListener('DOMContentLoaded', () => {
  const fileInput = document.querySelector('[data-preview-file]');
  const fileLabel = document.querySelector('[data-file-name]');
  if (fileInput && fileLabel) {
    fileInput.addEventListener('change', () => {
      fileLabel.textContent = fileInput.files?.[0]?.name || 'No file selected';
    });
  }
});
