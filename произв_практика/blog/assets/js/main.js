document.addEventListener('DOMContentLoaded', () => {
    initThemeToggle();

    document.querySelectorAll('form[data-validate]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            clearErrors(form);

            const validators = {
                username: (value) => value.length >= 3 || 'Минимум 3 символа',
                email: (value) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) || 'Некорректный email',
                password: (value) => value.length >= 6 || 'Минимум 6 символов',
                password_confirm: (value, formEl) => {
                    const password = formEl.querySelector('[name="password"]')?.value ?? '';
                    return value === password || 'Пароли не совпадают';
                },
                title: (value) => value.trim().length >= 3 || 'Минимум 3 символа',
                content: (value) => value.trim().length >= 10 || 'Минимум 10 символов',
            };

            let valid = true;

            form.querySelectorAll('[data-validate-field]').forEach((input) => {
                const rule = input.dataset.validateField;
                const validator = validators[rule];

                if (!validator) {
                    return;
                }

                const result = validator(input.value, form);

                if (result !== true) {
                    valid = false;
                    showFieldError(input, result);
                }
            });

            if (!valid) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('[data-confirm]').forEach((button) => {
        button.addEventListener('click', (event) => {
            const message = button.dataset.confirm || 'Вы уверены?';

            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });
});

function clearErrors(form) {
    form.querySelectorAll('.field-error').forEach((el) => {
        el.classList.remove('visible');
        el.textContent = '';
    });
}

function showFieldError(input, message) {
    const errorEl = input.parentElement?.querySelector('.field-error');

    if (errorEl) {
        errorEl.textContent = message;
        errorEl.classList.add('visible');
    }
}

function initThemeToggle() {
    const toggle = document.getElementById('theme-toggle');
    if (!toggle) {
        return;
    }

    toggle.addEventListener('click', () => {
        const current = document.documentElement.getAttribute('data-theme') === 'light' ? 'light' : 'dark';
        const next = current === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);
    });
}
