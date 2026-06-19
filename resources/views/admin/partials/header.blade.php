<header class="sticky top-0 z-20 border-b border-slate-200/80 bg-white/95 backdrop-blur-md">
    <div class="flex h-[72px] items-center justify-between px-8">
        <div class="min-w-0">
            <h1 class="truncate text-lg font-bold tracking-tight text-slate-900">@yield('page-title', 'Dashboard')</h1>
            @hasSection('page-subtitle')
                <p class="mt-0.5 truncate text-sm text-slate-500">@yield('page-subtitle')</p>
            @endif
        </div>

        <a href="{{ route('admin.profile.edit') }}" class="flex items-center gap-3 rounded-xl border border-slate-200/80 bg-slate-50/80 py-2 pl-2 pr-4 transition hover:border-emerald-200 hover:bg-emerald-50/50">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-gradient-to-br from-emerald-500 to-emerald-600 text-sm font-bold text-white shadow-sm">
                {{ strtoupper(substr(auth('admin')->user()->name, 0, 1)) }}
            </div>
            <div class="hidden text-left sm:block">
                <div class="text-sm font-semibold leading-tight text-slate-800">{{ auth('admin')->user()->name }}</div>
                <div class="text-xs text-slate-500">{{ auth('admin')->user()->role_title ?? 'Admin' }}</div>
            </div>
        </a>
    </div>
</header>
