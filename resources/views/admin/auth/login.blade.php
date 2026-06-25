<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Plumbing Solutions</title>
    @include('admin.partials.head')
</head>
<body class="auth-page">
    <div class="auth-shell">
        <div class="auth-card">
            {{-- Brand panel --}}
            <div class="auth-brand">
                <div class="auth-brand-inner">
                    <img
                        src="{{ asset('images/plumbing-solutions-logo.png') }}"
                        alt="Plumbing Solutions"
                        class="auth-brand-logo"
                    >
                    <div class="auth-brand-copy">
                        <h1 class="auth-brand-title">Plumbing Solutions</h1>
                        <p class="auth-brand-subtitle">Admin Management System</p>
                    </div>
                    <p class="auth-brand-tagline">
                        Manage services, orders, providers, and bulk quotations from one place.
                    </p>
                </div>
                <div class="auth-brand-pattern" aria-hidden="true"></div>
            </div>

            {{-- Login panel --}}
            <div class="auth-form-panel">
                <div class="auth-form-inner">
                    <div class="auth-form-header">
                        <img
                            src="{{ asset('images/plumbing-solutions-logo.png') }}"
                            alt=""
                            class="auth-form-logo"
                        >
                        <h2 class="auth-form-title">Sign in to your account</h2>
                        <p class="auth-form-desc">Enter your credentials to access the admin dashboard.</p>
                    </div>

                    @if($errors->any())
                        <div class="auth-alert">
                            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>{{ $errors->first() }}</span>
                        </div>
                    @endif

                    <form action="{{ route('admin.login.submit') }}" method="POST" class="auth-form">
                        @csrf
                        <div class="auth-field">
                            <label class="auth-label" for="email">Email ID or Username</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                maxlength="{{ config('admin.limits.email') }}"
                                class="auth-input @error('email') auth-input-error @enderror"
                                placeholder="name@gmail.com or name@outlook.com"
                                autocomplete="username"
                            >
                        </div>

                        <div class="auth-field">
                            <label class="auth-label" for="password">Password</label>
                            <div class="auth-password-wrap">
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    required
                                    maxlength="{{ config('admin.limits.password') }}"
                                    class="auth-input auth-input-password @error('password') auth-input-error @enderror"
                                    placeholder="Enter your password"
                                    autocomplete="current-password"
                                >
                                <button type="button" class="auth-password-toggle" data-target="password" aria-label="Show password">
                                    <svg class="icon-eye" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <svg class="icon-eye-off hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                </button>
                            </div>
                        </div>

                        <p class="auth-hint">{{ \App\Support\AdminValidation::emailHint() }}</p>

                        <label class="auth-remember">
                            <input type="checkbox" name="remember" class="auth-checkbox">
                            <span>Remember me</span>
                        </label>

                        <button type="submit" class="auth-submit">Sign In</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.querySelectorAll('.auth-password-toggle').forEach((btn) => {
        btn.addEventListener('click', () => {
            const input = document.getElementById(btn.dataset.target);
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.querySelector('.icon-eye').classList.toggle('hidden', show);
            btn.querySelector('.icon-eye-off').classList.toggle('hidden', !show);
        });
    });
    </script>
</body>
</html>
