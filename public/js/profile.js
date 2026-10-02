(() => {
    function hideFailedPhoto(image) {
        if (image.matches?.('[data-profile-photo]')) image.hidden = true;
    }
    document.addEventListener('error', event => hideFailedPhoto(event.target), true);
    document.querySelectorAll('[data-profile-photo]').forEach(image => {
        if (image.complete && image.naturalWidth === 0) hideFailedPhoto(image);
    });
    document.querySelectorAll('[data-password-toggle]').forEach(button => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.passwordToggle);
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.setAttribute('aria-pressed', String(show));
            button.setAttribute('aria-label', button.getAttribute('aria-label').replace(/^(Show|Hide)/, show ? 'Hide' : 'Show'));
            button.querySelector('i').className = show ? 'ti ti-eye-off' : 'ti ti-eye';
        });
    });
})();
