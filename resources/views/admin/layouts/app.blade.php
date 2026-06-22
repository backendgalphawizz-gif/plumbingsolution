<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') - PlumbManager</title>
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
    <div class="flex min-h-screen">
        @include('admin.partials.sidebar')

        <div class="flex min-w-0 flex-1 flex-col lg:ml-[260px]">
            @include('admin.partials.header')

            <main class="flex-1 px-6 py-6 lg:px-8">
                @if(session('success'))
                    <div class="alert alert-success">
                        <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-error">
                        <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif
                @if($errors->any())
                    <div class="alert alert-error">
                        <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <ul class="list-inside list-disc space-y-0.5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
    @stack('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
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
        });
    </script>
</body>
</html>
