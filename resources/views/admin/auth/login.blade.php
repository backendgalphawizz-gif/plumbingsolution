<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - PlumbManager</title>
    @include('admin.partials.head')
</head>
<body>
    <div class="login-wrap">
        <div class="w-full max-w-md">
            <div class="mb-8 text-center">
                <div class="login-logo">P</div>
                <h1 class="text-2xl font-bold text-white">PlumbManager</h1>
                <p class="mt-1 text-sm text-slate-400">Admin Console Sign In</p>
            </div>

            <div class="login-card">
                @if($errors->any())
                    <div class="alert alert-error mb-5">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form action="{{ route('admin.login.submit') }}" method="POST" class="space-y-5">
                    @csrf
                    <div>
                        <label class="admin-label">Email Address</label>
                        <input type="email" name="email" value="{{ old('email') }}" required maxlength="{{ config('admin.limits.email') }}" class="admin-input" placeholder="admin@gmail.com">
                    </div>
                    <div>
                        <label class="admin-label">Password</label>
                        <input type="password" name="password" required maxlength="{{ config('admin.limits.password') }}" class="admin-input" placeholder="••••••••">
                    </div>
                    <p class="field-hint">{{ \App\Support\AdminValidation::emailHint() }}</p>
                    </div>
                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 text-sm text-slate-600">
                            <input type="checkbox" name="remember" class="rounded border-slate-300 text-emerald-600">
                            Remember me
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary w-full">Sign In</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
