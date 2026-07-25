document.addEventListener('DOMContentLoaded', () => {
    const flashes = document.querySelectorAll('[data-auto-hide]');
    flashes.forEach((el) => setTimeout(() => el.remove(), 4500));
});
