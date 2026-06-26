<script>
document.addEventListener('DOMContentLoaded', () => {
    const isCompleteDate = (value) => /^\d{4}-\d{2}-\d{2}$/.test(value);

    const boundsFor = (input) => {
        const today = new Date().toISOString().slice(0, 10);
        const launch = window.adminLaunchDate || input.dataset.dateMin || today;
        const isExpiry = input.classList.contains('admin-date-expiry');

        if (isExpiry) {
            const min = input.dataset.dateMin || input.getAttribute('min') || today;
            return { min, max: null };
        }

        const max = input.dataset.dateMax || input.getAttribute('max') || today;
        let min = input.dataset.dateMin || input.getAttribute('min') || launch;

        if (min > max) {
            min = max;
        }

        return { min, max };
    };

    const applyBounds = (input) => {
        const { min, max } = boundsFor(input);
        input.min = min;

        if (max) {
            input.max = max;
        } else {
            input.removeAttribute('max');
        }

        return { min, max };
    };

    const clampIfComplete = (input) => {
        if (!isCompleteDate(input.value)) {
            return;
        }

        const { min, max } = boundsFor(input);

        if (input.value < min) {
            input.value = min;
        } else if (max && input.value > max) {
            input.value = max;
        }
    };

    document.querySelectorAll('form').forEach((form) => {
        const from = form.querySelector('.admin-date-from');
        const to = form.querySelector('.admin-date-to');

        if (from) {
            applyBounds(from);
        }

        if (to) {
            applyBounds(to);
        }

        const syncToMin = () => {
            if (!from || !to) {
                return;
            }

            const { min: launch } = boundsFor(from);

            if (isCompleteDate(from.value)) {
                to.min = from.value < launch ? launch : from.value;
            } else {
                to.min = launch;
            }

            if (to.max) {
                const { max } = boundsFor(to);
                if (max) {
                    to.max = max;
                }
            }
        };

        if (from) {
            from.addEventListener('change', () => {
                clampIfComplete(from);
                syncToMin();
                if (to) {
                    clampIfComplete(to);
                }
            });
        }

        if (to) {
            to.addEventListener('change', () => {
                syncToMin();
                clampIfComplete(to);
            });
        }

        syncToMin();

        form.addEventListener('submit', () => {
            if (from) {
                clampIfComplete(from);
            }
            syncToMin();
            if (to) {
                clampIfComplete(to);
            }
        });
    });

    document.querySelectorAll('.admin-date-expiry').forEach((input) => {
        applyBounds(input);

        input.addEventListener('change', () => {
            clampIfComplete(input);
        });
    });
});
</script>
