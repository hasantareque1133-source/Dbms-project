document.addEventListener('DOMContentLoaded', () => {
    const currentPath = window.location.pathname;
    document.querySelectorAll('.navbar .nav-link').forEach(link => {
        if (link.getAttribute('href') === currentPath) {
            link.classList.add('active');
        }
    });

    const flashWrapper = document.querySelector('.flash-wrapper');
    if (flashWrapper) {
        setTimeout(() => {
            flashWrapper.querySelectorAll('.alert').forEach(alert => {
                alert.classList.add('fade');
                alert.classList.add('show');
            });
        }, 150);
        setTimeout(() => {
            flashWrapper.classList.add('d-none');
        }, 5000);
    }
});
