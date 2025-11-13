document.addEventListener('DOMContentLoaded', () => {
    const togglePasswordButtons = document.querySelectorAll('.toggle-password');
    togglePasswordButtons.forEach(button => {
        button.addEventListener('click', () => {
            const input = button.parentElement?.querySelector('input[type="password"], input[type="text"]');
            if (!input) return;
            const isPassword = input.getAttribute('type') === 'password';
            input.setAttribute('type', isPassword ? 'text' : 'password');
            button.innerHTML = isPassword ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
        });
    });

    // Highlight active navigation link
    const currentPath = window.location.pathname;
    document.querySelectorAll('.navbar .nav-link').forEach(link => {
        if (link.getAttribute('href') && currentPath.endsWith(link.getAttribute('href'))) {
            link.classList.add('active');
        }
    });

    // Auto-dismiss alerts after a delay
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 6000);
    });
});
