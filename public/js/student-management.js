(() => {
    'use strict';
    const form = document.querySelector('.sm-student-form');
    if (!form) return;
    const birthday = form.querySelector('#birthday');
    const age = form.querySelector('#age');
    // Compare calendar parts using the server's current date, avoiding timezone shifts.
    const today = form.dataset.today.split('-').map(Number);
    function updateAge() {
        age.value = '';
        if (!birthday.value || !birthday.validity.valid) return;
        const birth = birthday.value.split('-').map(Number);
        if (birth.length !== 3 || birth.some(value => !Number.isInteger(value))) return;
        let years = today[0] - birth[0];
        if (today[1] < birth[1] || (today[1] === birth[1] && today[2] < birth[2])) years--;
        if (years >= 0) age.value = years;
    }
    birthday.addEventListener('input', updateAge);
    birthday.addEventListener('change', updateAge);
    updateAge();

    form.querySelectorAll('[data-password-target]').forEach(button => {
        button.addEventListener('click', () => {
            const input = document.getElementById(button.dataset.passwordTarget);
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.setAttribute('aria-pressed', String(show));
            button.setAttribute('aria-label', (show ? 'Hide ' : 'Show ') +
                (input.id === 'password_confirmation' ? 'confirm password' : 'password'));
            button.querySelector('i').className = show ? 'ti ti-eye-off' : 'ti ti-eye';
        });
    });
})();
