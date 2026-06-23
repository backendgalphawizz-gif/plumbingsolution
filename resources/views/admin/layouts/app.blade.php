<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') - Plumbing Solutions</title>
    @include('admin.partials.head')
    <script>
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
    @stack('scripts')
    <script>
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
            const from = form.querySelector('.admin-date-from');
            const to = form.querySelector('.admin-date-to');
            if (!from || !to) return;

            const sync = () => {
                if (from.value) {
                    to.min = from.value;
                    if (to.value && to.value < from.value) {
                        to.value = from.value;
                    }
                } else {
                    to.removeAttribute('min');
                }
            };

            from.addEventListener('change', sync);
            sync();
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
    });
    </script>
</body>
</html>
