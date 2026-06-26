@once
    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const accountPattern = /^[0-9]{9,18}$/;
        const ifscPattern = /^[A-Z]{4}0[A-Z0-9]{6}$/;

        const showFieldError = (input, message) => {
            const field = input.closest('.form-field');
            if (!field) {
                return;
            }

            field.classList.add('has-error');
            let error = field.querySelector('.js-bank-field-error');
            if (!error) {
                error = document.createElement('p');
                error.className = 'field-error js-bank-field-error';
                field.appendChild(error);
            }
            error.textContent = message;
        };

        const clearFieldError = (input) => {
            const field = input.closest('.form-field');
            if (!field) {
                return;
            }

            field.classList.remove('has-error');
            field.querySelector('.js-bank-field-error')?.remove();
        };

        document.querySelectorAll('[data-bank-field="account"]').forEach((input) => {
            input.addEventListener('input', () => {
                input.value = input.value.replace(/\D/g, '').slice(0, 18);
                clearFieldError(input);
            });

            input.addEventListener('blur', () => {
                const value = input.value.trim();
                if (!value) {
                    clearFieldError(input);
                    return;
                }

                if (!accountPattern.test(value)) {
                    showFieldError(input, 'Account number must contain 9 to 18 digits only.');
                }
            });
        });

        document.querySelectorAll('[data-bank-field="ifsc"]').forEach((input) => {
            input.addEventListener('input', () => {
                input.value = input.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 11);
                clearFieldError(input);
            });

            input.addEventListener('blur', () => {
                const value = input.value.trim().toUpperCase();
                input.value = value;

                if (!value) {
                    clearFieldError(input);
                    return;
                }

                if (!ifscPattern.test(value)) {
                    showFieldError(input, 'Enter a valid 11-character IFSC code (e.g. SBIN0001234).');
                }
            });
        });
    });
    </script>
    @endpush
@endonce
