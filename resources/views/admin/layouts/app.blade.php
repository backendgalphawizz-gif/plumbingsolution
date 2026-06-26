<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') - Plumbing Solutions</title>
    @include('admin.partials.head')
    <script>
    window.adminImageMaxBytes = {{ (int) config('admin.limits.image_kb', 20480) }} * 1024;
    window.adminLaunchDate = @json(config('admin.launch_date'));
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-field-counter]').forEach(counter => {
            const id = counter.dataset.fieldCounter;
            const max = parseInt(counter.dataset.max, 10);
            const input = document.getElementById(id);
            if (!input || !max) return;
            const update = () => {
                const len = input.value.length;
                counter.textContent = len + '/' + max;
                counter.classList.toggle('is-limit', len >= max);
            };
            input.addEventListener('input', update);
            update();
        });
    });
    </script>
</head>
<body>
    <div id="sidebar-backdrop" class="sidebar-backdrop" aria-hidden="true"></div>

    <div class="admin-shell flex min-h-screen w-full max-w-[100vw] overflow-x-hidden">
        @include('admin.partials.sidebar')

        <div class="admin-main flex min-w-0 w-full flex-1 flex-col lg:ml-[260px]">
            @include('admin.partials.header')

            <main class="admin-content flex-1 px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
                @yield('content')
            </main>
        </div>
    </div>

    @include('admin.partials.flash-toast')
    @include('admin.partials.date-filter-scripts')
    @stack('scripts')
    <script>
    window.showAdminFlashToast = function(type, message, title) {
        const existing = document.getElementById('flash-toast-backdrop');
        if (existing) {
            existing.remove();
        }

        const isSuccess = type === 'success';
        const toastTitle = title || (isSuccess ? 'Success' : 'Something went wrong');
        const iconSvg = isSuccess
            ? '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
            : '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';

        const backdrop = document.createElement('div');
        backdrop.className = 'flash-toast-backdrop';
        backdrop.id = 'flash-toast-backdrop';
        backdrop.setAttribute('role', 'presentation');
        backdrop.innerHTML = `
            <div class="flash-toast flash-toast-${isSuccess ? 'success' : 'error'}" role="alertdialog" aria-modal="true" aria-labelledby="flash-toast-title">
                <button type="button" class="flash-toast-close" data-flash-toast-close aria-label="Close">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
                <div class="flash-toast-icon">${iconSvg}</div>
                <div class="flash-toast-body">
                    <p class="flash-toast-title" id="flash-toast-title">${toastTitle}</p>
                    <p class="flash-toast-message"></p>
                </div>
                <button type="button" class="flash-toast-btn" data-flash-toast-close>OK</button>
            </div>
        `;

        backdrop.querySelector('.flash-toast-message').textContent = message;
        document.body.appendChild(backdrop);

        const closeFlashToast = () => {
            if (backdrop.classList.contains('is-hiding')) {
                return;
            }
            backdrop.classList.add('is-hiding');
            setTimeout(() => backdrop.remove(), 180);
        };

        backdrop.querySelectorAll('[data-flash-toast-close]').forEach((btn) => {
            btn.addEventListener('click', closeFlashToast);
        });

        backdrop.addEventListener('click', (event) => {
            if (event.target === backdrop) {
                closeFlashToast();
            }
        });

        const onEscape = (event) => {
            if (event.key === 'Escape') {
                closeFlashToast();
                document.removeEventListener('keydown', onEscape);
            }
        };

        document.addEventListener('keydown', onEscape);
    };

    document.addEventListener('DOMContentLoaded', () => {
        const flashBackdrop = document.getElementById('flash-toast-backdrop');
        if (flashBackdrop) {
            const closeFlashToast = () => {
                if (flashBackdrop.classList.contains('is-hiding')) return;
                flashBackdrop.classList.add('is-hiding');
                setTimeout(() => flashBackdrop.remove(), 180);
            };

            flashBackdrop.querySelectorAll('[data-flash-toast-close]').forEach((btn) => {
                btn.addEventListener('click', closeFlashToast);
            });

            flashBackdrop.addEventListener('click', (event) => {
                if (event.target === flashBackdrop) closeFlashToast();
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') closeFlashToast();
            });

            setTimeout(closeFlashToast, 4000);
        }

        document.querySelectorAll('form').forEach((form) => {
            form.addEventListener('submit', (event) => {
                const tooLarge = [];
                form.querySelectorAll('input[type="file"]').forEach((input) => {
                    Array.from(input.files || []).forEach((file) => {
                        if (file.size > window.adminImageMaxBytes) {
                            tooLarge.push(file.name);
                        }
                    });
                });

                if (tooLarge.length > 0) {
                    event.preventDefault();
                    const maxMb = Math.round(window.adminImageMaxBytes / (1024 * 1024));
                    window.showAdminFlashToast('error', 'Image is too large. Maximum upload size is ' + maxMb + ' MB.', 'Upload failed');
                }
            });
        });

        document.querySelectorAll('input[type="file"]').forEach((input) => {
            input.addEventListener('change', () => {
                const maxMb = Math.round(window.adminImageMaxBytes / (1024 * 1024));
                for (const file of input.files || []) {
                    if (file.size > window.adminImageMaxBytes) {
                        input.value = '';
                        window.showAdminFlashToast('error', 'Image is too large. Maximum upload size is ' + maxMb + ' MB.', 'Upload failed');
                        break;
                    }
                }
            });
        });

        const sidebar = document.getElementById('admin-sidebar');
        const backdrop = document.getElementById('sidebar-backdrop');
        const toggle = document.getElementById('sidebar-toggle');

        const closeSidebar = () => {
            document.body.classList.remove('sidebar-open');
            toggle?.setAttribute('aria-expanded', 'false');
        };

        const openSidebar = () => {
            document.body.classList.add('sidebar-open');
            toggle?.setAttribute('aria-expanded', 'true');
        };

        toggle?.addEventListener('click', () => {
            if (document.body.classList.contains('sidebar-open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });

        backdrop?.addEventListener('click', closeSidebar);

        document.querySelectorAll('[data-sidebar-close]').forEach((btn) => {
            btn.addEventListener('click', closeSidebar);
        });

        sidebar?.querySelectorAll('.sidebar-link').forEach((link) => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 1024) {
                    closeSidebar();
                }
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeSidebar();
            }
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) {
                closeSidebar();
            }
        });

        const bindNumericInput = (input) => {
            if (input.dataset.numericBound === '1') {
                return;
            }
            input.dataset.numericBound = '1';

            const isDecimal = input.type === 'number' && (input.step === 'any' || (input.step && parseFloat(input.step) < 1));
            const isTel = input.type === 'tel' || input.getAttribute('inputmode') === 'numeric';

            input.addEventListener('keydown', (event) => {
                const allowed = ['Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'];
                if (allowed.includes(event.key) || (event.ctrlKey || event.metaKey)) {
                    return;
                }
                if (isDecimal && event.key === '.' && !input.value.includes('.')) {
                    return;
                }
                if (isTel && /^[0-9]$/.test(event.key)) {
                    return;
                }
                if (input.type === 'number' && !isDecimal && /^[0-9]$/.test(event.key)) {
                    return;
                }
                if (isDecimal && /^[0-9]$/.test(event.key)) {
                    return;
                }
                event.preventDefault();
            });

            input.addEventListener('paste', (event) => {
                const text = (event.clipboardData || window.clipboardData).getData('text');
                const pattern = isTel || (input.type === 'number' && !isDecimal)
                    ? /^[0-9]+$/
                    : /^[0-9]*\.?[0-9]*$/;
                if (!pattern.test(text)) {
                    event.preventDefault();
                }
            });
        };

        document.querySelectorAll('input[type="number"], input[type="tel"], input[inputmode="numeric"], .admin-input-numeric').forEach(bindNumericInput);

        const bindPasswordToggle = (button) => {
            if (button.dataset.passwordToggleBound === '1') {
                return;
            }
            button.dataset.passwordToggleBound = '1';

            button.addEventListener('click', () => {
                const input = document.getElementById(button.dataset.target);
                if (!input) {
                    return;
                }

                const show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                button.querySelector('.icon-eye')?.classList.toggle('hidden', show);
                button.querySelector('.icon-eye-off')?.classList.toggle('hidden', !show);
                button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            });
        };

        document.querySelectorAll('.admin-password-toggle, .auth-password-toggle').forEach(bindPasswordToggle);

        new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType !== 1) {
                        return;
                    }
                    if (node.matches?.('input[type="number"], input[type="tel"], input[inputmode="numeric"], .admin-input-numeric')) {
                        bindNumericInput(node);
                    }
                    node.querySelectorAll?.('input[type="number"], input[type="tel"], input[inputmode="numeric"], .admin-input-numeric').forEach(bindNumericInput);
                    node.querySelectorAll?.('.admin-password-toggle, .auth-password-toggle').forEach(bindPasswordToggle);
                });
            });
        }).observe(document.body, { childList: true, subtree: true });
    });
    </script>
</body>
</html>
