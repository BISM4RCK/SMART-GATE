/* BISM4RCK/KUN3H0 2026 */
// BISM4RCK/KUN3H0 2026
document.addEventListener('DOMContentLoaded', () => {
  const fileInput = document.querySelector('[data-preview-file]');
  const fileLabel = document.querySelector('[data-file-name]');
  if (fileInput && fileLabel) {
    fileInput.addEventListener('change', () => {
      fileLabel.textContent = fileInput.files?.[0]?.name || 'No file selected';
    });
  }
  document.querySelectorAll('[data-autoclose]').forEach((node) => {
    setTimeout(() => node.remove(), 4000);
  });
});
/* BISM4RCK/KUN3H0 2026 */
/* BISM4RCK-KUN3H0 2026 */
