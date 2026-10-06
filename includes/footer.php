</main>
<footer class="site-footer">
    <p>&copy; <?= date('Y') ?> Pickleball Booking System</p>
</footer>
<script>
document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
    button.addEventListener('click', function () {
        var input = document.getElementById(button.dataset.passwordToggle);
        var isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        button.textContent = isPassword ? 'Hide' : 'Show';
        button.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
    });
});
</script>
</body>
</html>
