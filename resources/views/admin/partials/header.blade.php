<header class="admin-header sticky top-0 z-20 border-b border-slate-200/80 bg-white/95 backdrop-blur-md">
    @php($headerAdmin = auth('admin')->user())
    <div class="admin-header-inner">
        <button type="button" id="sidebar-toggle" class="sidebar-toggle" aria-label="Open menu" aria-expanded="false" aria-controls="admin-sidebar">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>

        <div class="admin-header-titles min-w-0 flex-1">
            <h1 class="truncate text-base font-bold tracking-tight text-slate-900 sm:text-lg">@yield('page-title', 'Dashboard')</h1>
            @hasSection('page-subtitle')
                <p class="mt-0.5 truncate text-xs text-slate-500 sm:text-sm">@yield('page-subtitle')</p>
            @endif
        </div>

        <a href="{{ route('admin.profile.edit') }}" class="admin-header-profile flex shrink-0 items-center gap-2 rounded-xl border border-slate-200/80 bg-slate-50/80 py-1.5 pl-1.5 pr-3 transition hover:border-brand-200 hover:bg-brand-50/50 sm:gap-3 sm:py-2 sm:pl-2 sm:pr-4">
            @if($headerAdmin->avatarUrl())
                <img
                    src="{{ $headerAdmin->avatarUrl() }}"
                    alt="{{ $headerAdmin->name }}"
                    class="h-8 w-8 rounded-lg object-cover shadow-sm ring-1 ring-slate-200/80 sm:h-9 sm:w-9"
                >
            @else
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-brand-500 to-brand-600 text-sm font-bold text-white shadow-sm sm:h-9 sm:w-9">
                    {{ strtoupper(substr($headerAdmin->name, 0, 1)) }}
                </div>
            @endif
            <div class="hidden text-left sm:block">
                <div class="text-sm font-semibold leading-tight text-slate-800">{{ $headerAdmin->name }}</div>
                <div class="text-xs text-slate-500">{{ $headerAdmin->role_title ?? 'Admin' }}</div>
            </div>
        </a>
    </div>
</header>
