<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js"></script>
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
    theme: {
        extend: {
            fontFamily: { sans: ['Plus Jakarta Sans', 'Inter', 'system-ui', 'sans-serif'] },
            colors: {
                brand: { 50:'#eef9ff',100:'#d6f0fc',200:'#b3e3f9',400:'#4cbef0',500:'#3cb4f2',600:'#2b9fd9',700:'#1e85b8',900:'#155a7a',grey:'#6b7b8c' },
                emerald: { 50:'#eef9ff',100:'#d6f0fc',200:'#b3e3f9',300:'#7fd0f5',400:'#4cbef0',500:'#3cb4f2',600:'#2b9fd9',700:'#1e85b8',800:'#1a6d96',900:'#155a7a' },
                sidebar: { DEFAULT:'#212121', light:'#2a2a2a', border:'#333333', muted:'#6d8499', text:'#a8bac9' }
            },
            boxShadow: {
                card: '0 1px 3px rgba(15,23,42,.06), 0 8px 24px rgba(15,23,42,.04)',
                soft: '0 2px 8px rgba(15,23,42,.08)',
            }
        }
    }
};
</script>
<style>
:root {
    --brand: #3cb4f2;
    --brand-dark: #2b9fd9;
    --brand-darker: #1e85b8;
    --brand-light: #eef9ff;
    --brand-grey: #465b73;
    --primary: #3cb4f2;
    --sidebar-bg: #212121;
    --sidebar-border: #333333;
    --sidebar-section: #6d8499;
    --sidebar-link: #a8bac9;
    --sidebar-link-hover: #d8e6f2;
    --sidebar-muted: #a8bac9;
    --sidebar-text: #a8bac9;
    --sidebar-hover: #d8e6f2;
    --text: #0f172a;
    --muted: #64748b;
    --border: #e2e8f0;
    --surface: #ffffff;
    --bg: #f1f5f9;
}
body { background: var(--bg); color: var(--text); font-family: 'Plus Jakarta Sans', system-ui, sans-serif; }

/* Hide scrollbars across admin — scroll still works with wheel/trackpad */
body,
body * {
    scrollbar-width: none;
    -ms-overflow-style: none;
}
body *::-webkit-scrollbar {
    display: none;
    width: 0;
    height: 0;
    background: transparent;
}

/* Inputs */
.admin-input {
    width: 100%; height: 42px; border-radius: 10px; border: 1px solid var(--border);
    background: #fff; padding: 0 14px; font-size: 0.875rem; color: var(--text);
    transition: border-color .15s, box-shadow .15s;
}
.admin-input:focus { border-color: var(--brand); outline: none; box-shadow: 0 0 0 3px rgba(60,180,242,.18); }
.admin-password-wrap { position: relative; }
.admin-input-password { padding-right: 44px; }
.admin-password-toggle {
    position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
    display: flex; align-items: center; justify-content: center;
    width: 28px; height: 28px; border: none; background: transparent;
    color: #94a3b8; cursor: pointer; border-radius: 6px;
}
.admin-password-toggle:hover { color: #475569; background: #f1f5f9; }
.admin-password-toggle svg { width: 18px; height: 18px; }
textarea.admin-input { height: auto; padding: 12px 14px; min-height: 90px; }
.admin-label { display: block; margin-bottom: 6px; font-size: 0.75rem; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: var(--muted); }

/* Buttons */
.btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 42px; padding: 0 18px; border-radius: 10px; font-size: 0.875rem; font-weight: 600; transition: all .15s; white-space: nowrap; }
.btn-primary { background: linear-gradient(135deg, #3cb4f2, #2b9fd9); color: #fff; box-shadow: 0 4px 14px rgba(60,180,242,.3); }
.btn-primary:hover { background: linear-gradient(135deg, #2b9fd9, #1e85b8); transform: translateY(-1px); }
.btn-secondary { background: #fff; color: #334155; border: 1px solid var(--border); }
.btn-secondary:hover { background: #f8fafc; border-color: #cbd5e1; }
.btn-ghost { background: transparent; color: var(--muted); border: 1px solid transparent; }
.btn-ghost:hover { background: #f1f5f9; color: var(--text); }
.btn-sm { height: 34px; padding: 0 12px; font-size: 0.8125rem; border-radius: 8px; }
.btn-icon { width: 34px; height: 34px; padding: 0; border-radius: 8px; }

/* Page toolbar */
.page-toolbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 20px; }

/* Stat tabs */
.stat-tabs { display: inline-flex; flex-wrap: wrap; gap: 8px; padding: 4px; background: #fff; border: 1px solid var(--border); border-radius: 12px; box-shadow: var(--card-shadow, 0 1px 2px rgba(0,0,0,.04)); }
.stat-tab { display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; border-radius: 8px; font-size: 0.8125rem; font-weight: 600; color: var(--muted); transition: all .15s; }
.stat-tab:hover { color: var(--text); background: #f8fafc; }
.stat-tab.active { background: var(--brand-light); color: var(--brand-dark); box-shadow: inset 0 0 0 1px rgba(60,180,242,.22); }
.stat-tab .count { display: inline-flex; min-width: 22px; align-items: center; justify-content: center; padding: 2px 7px; border-radius: 999px; font-size: 0.6875rem; font-weight: 700; background: rgba(100,116,139,.12); color: var(--muted); }
.stat-tab.active .count { background: rgba(60,180,242,.16); color: var(--brand-darker); }
.stat-tab.danger.active { background: #fef2f2; color: #dc2626; box-shadow: inset 0 0 0 1px rgba(220,38,38,.12); }
.stat-tab.danger.active .count { background: rgba(220,38,38,.12); color: #dc2626; }
.stat-tab.warning.active { background: #fffbeb; color: #d97706; box-shadow: inset 0 0 0 1px rgba(217,119,6,.12); }
.stat-tab.warning.active .count { background: rgba(217,119,6,.12); color: #d97706; }

/* Filter bar — compact horizontal */
.filter-bar { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 16px 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(15,23,42,.04); }
.filter-bar-inner { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 16px; }
.filter-field { flex: 1; min-width: 180px; max-width: 280px; }
.filter-actions { display: flex; gap: 8px; margin-left: auto; padding-bottom: 1px; align-items: flex-end; }

/* Export dropdown */
.export-dropdown { display: inline-block; }
.export-menu { position: absolute; right: 0; top: calc(100% + 6px); z-index: 40; min-width: 180px; background: #fff; border: 1px solid var(--border); border-radius: 10px; box-shadow: 0 8px 24px rgba(15,23,42,.12); overflow: hidden; }
.export-menu-item { display: flex; align-items: center; gap: 10px; padding: 10px 14px; font-size: 0.8125rem; font-weight: 600; color: #334155; text-decoration: none; transition: background .12s; }
.export-menu-item:hover { background: #f8fafc; color: #0f172a; }
.export-menu-item + .export-menu-item { border-top: 1px solid #f1f5f9; }

/* Data card */
.data-card { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; overflow: hidden; box-shadow: 0 1px 3px rgba(15,23,42,.04); }
.data-card-header { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; padding: 16px 20px; border-bottom: 1px solid #f1f5f9; background: linear-gradient(to bottom, #fafbfc, #fff); }
.data-card-title { font-size: 0.9375rem; font-weight: 700; color: var(--text); }
.data-card-meta { font-size: 0.8125rem; color: var(--muted); }

/* Table */
.admin-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
.admin-table thead th { padding: 12px 20px; text-align: left; font-size: 0.6875rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #94a3b8; background: #f8fafc; border-bottom: 1px solid var(--border); }
.admin-table tbody td { padding: 14px 20px; color: #334155; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.admin-table tbody tr:last-child td { border-bottom: none; }
.admin-table tbody tr { transition: background .12s; }
.admin-table tbody tr:hover { background: #f8fafc; }

/* User cell */
.user-cell { display: flex; align-items: center; gap: 12px; }
.user-avatar { display: flex; width: 40px; height: 40px; align-items: center; justify-content: center; border-radius: 10px; font-size: 0.875rem; font-weight: 700; background: linear-gradient(135deg, #d6f0fc, #b3e3f9); color: #1e85b8; flex-shrink: 0; }
.user-name { font-weight: 600; color: var(--text); line-height: 1.3; }
.user-sub { font-size: 0.75rem; color: var(--muted); margin-top: 2px; }

/* Badges */
.badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 999px; font-size: 0.6875rem; font-weight: 700; letter-spacing: .02em; text-transform: capitalize; }
.badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; opacity: .7; }
.badge-success { background: #eef9ff; color: #1e85b8; }
.badge-danger { background: #fef2f2; color: #dc2626; }
.badge-warning { background: #fffbeb; color: #d97706; }
.badge-info { background: #eff6ff; color: #2563eb; }
.badge-neutral { background: #f1f5f9; color: #64748b; }

/* Action pills */
.action-group { display: flex; gap: 6px; }
.action-btn { display: inline-flex; align-items: center; gap: 4px; padding: 6px 12px; border-radius: 8px; font-size: 0.8125rem; font-weight: 600; color: var(--muted); background: #f8fafc; border: 1px solid transparent; transition: all .12s; cursor: pointer; }
button.action-btn { font-family: inherit; }
.action-btn:hover { color: var(--brand-dark); background: var(--brand-light); border-color: rgba(60,180,242,.25); }
.action-btn.danger:hover { color: #dc2626; background: #fef2f2; border-color: rgba(220,38,38,.15); }

/* Alerts */
.alert { display: flex; align-items: flex-start; gap: 12px; padding: 14px 16px; border-radius: 12px; margin-bottom: 20px; font-size: 0.875rem; }
.alert-success { background: #eef9ff; border: 1px solid #b3e3f9; color: #1a6d96; }
.alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }

/* Flash toast popup */
.flash-toast-backdrop {
    position: fixed; inset: 0; z-index: 100;
    display: flex; align-items: center; justify-content: center;
    padding: 20px; background: rgba(13, 17, 23, .45); backdrop-filter: blur(4px);
    animation: flash-backdrop-in .2s ease-out;
}
.flash-toast-backdrop.is-hiding { animation: flash-backdrop-out .18s ease-in forwards; }
.flash-toast {
    position: relative; width: 100%; max-width: 400px;
    padding: 28px 24px 24px; border-radius: 18px; text-align: center;
    background: #fff; box-shadow: 0 24px 48px rgba(15, 23, 42, .18), 0 0 0 1px rgba(148, 163, 184, .15);
    animation: flash-toast-in .28s cubic-bezier(.22, 1, .36, 1);
}
.flash-toast-backdrop.is-hiding .flash-toast { animation: flash-toast-out .18s ease-in forwards; }
.flash-toast-success { border-top: 4px solid #3cb4f2; }
.flash-toast-error { border-top: 4px solid #f87171; }
.flash-toast-close {
    position: absolute; top: 12px; right: 12px;
    display: flex; width: 32px; height: 32px; align-items: center; justify-content: center;
    border: none; border-radius: 8px; background: transparent; color: #94a3b8; cursor: pointer;
    transition: background .15s, color .15s;
}
.flash-toast-close:hover { background: #f1f5f9; color: #475569; }
.flash-toast-close svg { width: 18px; height: 18px; }
.flash-toast-icon {
    display: flex; width: 56px; height: 56px; margin: 0 auto 16px;
    align-items: center; justify-content: center; border-radius: 50%;
}
.flash-toast-icon svg { width: 28px; height: 28px; }
.flash-toast-success .flash-toast-icon {
    background: linear-gradient(135deg, #eef9ff, #d6f0fc);
    color: #2b9fd9; box-shadow: 0 8px 20px rgba(60, 180, 242, .2);
}
.flash-toast-error .flash-toast-icon {
    background: linear-gradient(135deg, #fef2f2, #fee2e2);
    color: #dc2626; box-shadow: 0 8px 20px rgba(220, 38, 38, .12);
}
.flash-toast-title {
    font-size: 1.0625rem; font-weight: 700; color: #0f172a; letter-spacing: -.01em;
}
.flash-toast-message {
    margin-top: 8px; font-size: .9375rem; line-height: 1.5; color: #64748b;
}
.flash-toast-list {
    margin-top: 12px; text-align: left; font-size: .8125rem; line-height: 1.5; color: #b91c1c;
    list-style: disc; padding-left: 1.25rem;
}
.flash-toast-btn {
    margin-top: 20px; min-width: 120px; height: 42px; padding: 0 24px;
    border: none; border-radius: 10px; font-size: .875rem; font-weight: 700; cursor: pointer;
    transition: transform .15s, box-shadow .15s;
}
.flash-toast-success .flash-toast-btn {
    background: linear-gradient(135deg, #3cb4f2, #2b9fd9);
    color: #fff; box-shadow: 0 6px 16px rgba(60, 180, 242, .3);
}
.flash-toast-success .flash-toast-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 22px rgba(60, 180, 242, .38);
}
.flash-toast-error .flash-toast-btn {
    background: #fff; color: #dc2626; border: 1px solid #fecaca;
}
.flash-toast-error .flash-toast-btn:hover { background: #fef2f2; }
@keyframes flash-backdrop-in { from { opacity: 0; } to { opacity: 1; } }
@keyframes flash-backdrop-out { from { opacity: 1; } to { opacity: 0; } }
@keyframes flash-toast-in {
    from { opacity: 0; transform: scale(.92) translateY(12px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
@keyframes flash-toast-out {
    from { opacity: 1; transform: scale(1); }
    to { opacity: 0; transform: scale(.95); }
}

/* Form card */
.form-card { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 28px; box-shadow: 0 1px 3px rgba(15,23,42,.04); }
.form-section-title { font-size: 1rem; font-weight: 700; color: var(--text); margin-bottom: 4px; }
.form-section-desc { font-size: 0.8125rem; color: var(--muted); margin-bottom: 24px; }
.form-subsection { margin-top: 8px; padding-top: 20px; border-top: 1px solid #f1f5f9; }
.form-subsection-title { font-size: 0.875rem; font-weight: 700; color: #334155; margin-bottom: 14px; }
.form-file-field { margin-bottom: 0; }
.form-file-field .admin-label { display: block; margin-bottom: 6px; }
.form-file-field .field-hint { margin-top: 6px; font-size: 0.75rem; color: var(--muted); }

/* Pagination */
.pagination-wrap { padding: 16px 20px; border-top: 1px solid #f1f5f9; }
.pagination-wrap nav { display: flex; justify-content: center; }
.pagination-wrap nav span, .pagination-wrap nav a { border-radius: 8px !important; }

/* Empty state */
.empty-state { padding: 48px 24px; text-align: center; }
.empty-state-icon { width: 56px; height: 56px; margin: 0 auto 16px; border-radius: 14px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: #94a3b8; }
.empty-state p { color: var(--muted); font-size: 0.9375rem; }

/* Sidebar */
.sidebar-brand { text-decoration: none; }
.sidebar-brand-logo {
    display: block; width: 100%; max-width: 200px; height: auto;
    margin: 0 auto; object-fit: contain;
}
.admin-sidebar {
    background: var(--sidebar-bg);
    color: var(--sidebar-text);
    box-shadow: 4px 0 24px rgba(0, 0, 0, .18);
}
.sidebar-section-label {
    margin-bottom: 8px; padding: 0 12px;
    font-size: 10px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase;
    color: var(--sidebar-section);
}
.sidebar-link {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 14px; margin-bottom: 2px; border-radius: 10px;
    font-size: 0.8125rem; font-weight: 500; color: var(--sidebar-link);
    transition: all .15s; border-left: 3px solid transparent;
}
.sidebar-link:hover {
    color: var(--sidebar-link-hover);
    background: rgba(60, 180, 242, .1);
}
.sidebar-link.active {
    color: #7fd0f5;
    background: rgba(60, 180, 242, .14);
    border-left-color: #3cb4f2;
    font-weight: 600;
}
.sidebar-link svg { opacity: .92; flex-shrink: 0; color: inherit; }
.sidebar-link:hover svg { opacity: 1; }
.sidebar-link.active svg { opacity: 1; color: #4cbef0; }
.sidebar-link.active span { color: #b8e6fc; }
.sidebar-footer { border-top: 1px solid var(--sidebar-border); }
.sidebar-brand-wrap { border-bottom: 1px solid var(--sidebar-border); }

.profile-avatar-field { margin-bottom: 24px; }
.profile-avatar-row { display: flex; align-items: center; gap: 16px; margin-top: 8px; }
.profile-avatar-preview {
    display: flex; width: 72px; height: 72px; flex-shrink: 0; align-items: center; justify-content: center;
    overflow: hidden; border-radius: 14px;
    background: linear-gradient(135deg, #d6f0fc, #b3e3f9);
    color: #1e85b8; font-size: 1.5rem; font-weight: 700;
    box-shadow: inset 0 0 0 1px rgba(60,180,242,.15);
}
.profile-avatar-preview img { width: 100%; height: 100%; object-fit: cover; }
.profile-avatar-actions { min-width: 0; }
.profile-avatar-input { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); border: 0; }
.header-avatar { width: 36px; height: 36px; border-radius: 10px; object-fit: cover; }

/* Settings page tabs */
.settings-page { display: flex; flex-direction: column; gap: 20px; }
.settings-tab-bar {
    display: flex; flex-wrap: wrap; gap: 8px; padding: 6px;
    background: #fff; border: 1px solid var(--border); border-radius: 14px;
    box-shadow: 0 1px 3px rgba(15,23,42,.04);
}
.settings-tab {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 10px 14px; border: none; border-radius: 10px;
    background: transparent; color: var(--muted);
    font-size: .8125rem; font-weight: 600; cursor: pointer; transition: all .15s;
}
.settings-tab svg { width: 16px; height: 16px; flex-shrink: 0; }
.settings-tab:hover { color: var(--text); background: #f8fafc; }
.settings-tab.is-active {
    color: var(--brand-darker); background: var(--brand-light);
    box-shadow: inset 0 0 0 1px rgba(60,180,242,.2);
}
.settings-tab-panels { min-height: 320px; }
.settings-panel { margin-bottom: 0; }
.settings-panel-head {
    display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between;
    gap: 12px; margin-bottom: 24px;
}
.settings-panel-actions { margin-top: 24px; padding-top: 20px; border-top: 1px solid #f1f5f9; }
.settings-fields {
    display: grid; gap: 16px;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
}
.settings-field { display: flex; flex-direction: column; gap: 6px; }
.settings-stack { display: grid; gap: 20px; }
.settings-slug-badge {
    display: inline-flex; padding: 6px 10px; border-radius: 999px;
    font-size: .6875rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase;
    background: var(--brand-light); color: var(--brand-darker);
}
.settings-slug-badge-muted { background: #f1f5f9; color: #64748b; }
.settings-legal-panel { display: flex; flex-direction: column; gap: 16px; }
.settings-legal-head { margin-bottom: 4px; }
.settings-subtab-bar {
    display: inline-flex; flex-wrap: wrap; gap: 6px; padding: 4px;
    background: #fff; border: 1px solid var(--border); border-radius: 12px;
    box-shadow: 0 1px 2px rgba(15,23,42,.04); width: fit-content; max-width: 100%;
}
.settings-subtab {
    padding: 8px 14px; border: none; border-radius: 8px;
    background: transparent; color: var(--muted);
    font-size: .8125rem; font-weight: 600; cursor: pointer; transition: all .15s;
}
.settings-subtab:hover { color: var(--text); background: #f8fafc; }
.settings-subtab.is-active {
    color: var(--brand-darker); background: var(--brand-light);
    box-shadow: inset 0 0 0 1px rgba(60,180,242,.2);
}
.settings-faq-toolbar {
    display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between;
    gap: 16px; margin-bottom: 20px;
}
.settings-faq-question { max-width: 280px; }
.settings-faq-answer { max-width: 360px; }
.settings-faq-modal { max-width: 36rem; }
.settings-faq-meta {
    display: flex; flex-wrap: wrap; align-items: flex-end; gap: 16px; margin-top: 4px;
}
.settings-faq-meta .settings-field { flex: 1; min-width: 120px; max-width: 160px; }
.settings-check {
    display: inline-flex; align-items: center; gap: 8px;
    font-size: .875rem; color: #475569; cursor: pointer; padding-bottom: 8px;
}

.field-hint { margin-top: 6px; font-size: 0.75rem; color: #94a3b8; }
.field-error { margin-top: 6px; font-size: 0.75rem; font-weight: 600; color: #dc2626; }
.form-field.has-error .admin-input { border-color: #fca5a5; box-shadow: 0 0 0 3px rgba(220,38,38,.08); }
.form-field-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 6px; }
.form-field-head .admin-label { margin-bottom: 0; }
.field-counter { font-size: 0.6875rem; font-weight: 600; color: #94a3b8; white-space: nowrap; }
.field-counter.is-limit { color: #dc2626; }
.cell-truncate { max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
[x-cloak] { display: none !important; }

/* Dashboard */
.dash-stat { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 20px; box-shadow: 0 1px 3px rgba(15,23,42,.04); transition: box-shadow .15s, transform .15s; }
.dash-stat:hover { box-shadow: 0 4px 16px rgba(15,23,42,.06); transform: translateY(-1px); }
.dash-link { display: block; color: inherit; text-decoration: none; cursor: pointer; }
.dash-link:hover { color: inherit; }
.dash-link:focus-visible { outline: 2px solid var(--primary); outline-offset: 2px; border-radius: 14px; }
.dash-mini.dash-link:hover { border-color: #cbd5e1; box-shadow: 0 2px 8px rgba(15,23,42,.05); }
.dash-activity-link { display: block; border-radius: 10px; padding: 6px 8px; margin: -6px -8px; transition: background .15s; text-decoration: none; color: inherit; }
.dash-activity-link:hover { background: #f8fafc; }
.dash-panel-link { display: block; color: inherit; text-decoration: none; transition: box-shadow .15s; border-radius: 14px; }
.dash-panel-link:hover { box-shadow: 0 4px 16px rgba(15,23,42,.06); }
.dash-view-all { font-size: 0.8125rem; font-weight: 600; color: var(--primary); text-decoration: none; }
.dash-view-all:hover { text-decoration: underline; }
.dash-stat-label { font-size: 0.8125rem; color: var(--muted); font-weight: 500; }
.dash-stat-value { margin-top: 6px; font-size: 1.75rem; font-weight: 800; color: var(--text); letter-spacing: -.02em; }
.dash-stat-sub { margin-top: 4px; font-size: 0.75rem; font-weight: 600; }
.dash-stat-icon { display: flex; width: 44px; height: 44px; align-items: center; justify-content: center; border-radius: 12px; }
.dash-mini { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 16px; text-align: center; }
.dash-mini-label { font-size: 0.6875rem; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: var(--muted); }
.dash-mini-value { margin-top: 4px; font-size: 1.125rem; font-weight: 700; color: var(--text); }
.dash-panel { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 24px; box-shadow: 0 1px 3px rgba(15,23,42,.04); }
.dash-panel-title { font-size: 1rem; font-weight: 700; color: var(--text); }

/* Detail pages */
.detail-header { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 24px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(15,23,42,.04); }
.detail-grid { display: grid; gap: 20px; }
@media (min-width: 1024px) { .detail-grid { grid-template-columns: repeat(2, 1fr); } }
.detail-panel { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 20px; }
.detail-panel-title { font-size: 0.875rem; font-weight: 700; color: var(--text); margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; }
.detail-row { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid #f8fafc; font-size: 0.875rem; }
.detail-row:last-child { border-bottom: none; }

/* Progress stepper & activity timeline */
.progress-stepper { display: flex; align-items: flex-start; gap: 0; overflow-x: auto; padding-bottom: 4px; margin-bottom: 20px; }
.progress-step { display: flex; flex-direction: column; align-items: center; min-width: 72px; flex-shrink: 0; text-align: center; }
.progress-step-dot {
    width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
    font-size: 0.75rem; font-weight: 700; background: #f1f5f9; color: #94a3b8; border: 2px solid #e2e8f0;
}
.progress-step.is-done .progress-step-dot { background: #ecfdf5; color: #059669; border-color: #6ee7b7; }
.progress-step.is-active .progress-step-dot { background: #3cb4f2; color: #fff; border-color: #3cb4f2; box-shadow: 0 0 0 4px rgba(60,180,242,.2); }
.progress-step-label { margin-top: 8px; font-size: 0.6875rem; font-weight: 600; color: #94a3b8; line-height: 1.2; max-width: 80px; }
.progress-step.is-done .progress-step-label, .progress-step.is-active .progress-step-label { color: #334155; }
.progress-step-line { flex: 1; height: 2px; background: #e2e8f0; margin-top: 15px; min-width: 24px; }
.progress-step-line.is-done { background: #6ee7b7; }
.progress-stepper--bad .progress-step.is-active .progress-step-dot { background: #ef4444; border-color: #ef4444; box-shadow: 0 0 0 4px rgba(239,68,68,.15); }
.progress-terminal-alert { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; padding: 12px 14px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; margin-bottom: 20px; }
.activity-timeline { border-top: 1px solid #f1f5f9; padding-top: 16px; }
.activity-timeline-title { font-size: 0.8125rem; font-weight: 700; color: var(--text); margin-bottom: 14px; }
.activity-item { display: flex; gap: 12px; padding-bottom: 16px; position: relative; }
.activity-item:not(:last-child)::before { content: ''; position: absolute; left: 5px; top: 14px; bottom: 0; width: 2px; background: #e2e8f0; }
.activity-dot { width: 12px; height: 12px; border-radius: 50%; background: #3cb4f2; border: 2px solid #fff; box-shadow: 0 0 0 1px #e2e8f0; flex-shrink: 0; margin-top: 4px; z-index: 1; }
.activity-body { flex: 1; min-width: 0; }
.activity-notes { margin-top: 6px; font-size: 0.8125rem; color: #475569; }
.activity-meta { margin-top: 4px; font-size: 0.75rem; color: #94a3b8; }
.detail-dl { display: grid; gap: 16px; font-size: 0.875rem; }
@media (min-width: 640px) { .detail-dl { grid-template-columns: repeat(2, 1fr); } }
.detail-dl .span-full { grid-column: 1 / -1; }
.amount-row { display: flex; justify-content: space-between; padding: 6px 0; font-size: 0.875rem; color: #64748b; }
.amount-row.total { border-top: 1px solid #f1f5f9; margin-top: 8px; padding-top: 12px; font-size: 1rem; font-weight: 700; color: #0f172a; }

/* Modal */
.modal-backdrop { position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; background: rgba(15,23,42,.55); backdrop-filter: blur(4px); padding: 16px; }
.modal-card { width: 100%; max-width: 32rem; background: #fff; border-radius: 16px; padding: 28px; box-shadow: 0 24px 48px rgba(15,23,42,.18); }
.modal-title { font-size: 1.125rem; font-weight: 700; color: var(--text); margin-bottom: 4px; }
.modal-sub { font-size: 0.8125rem; color: var(--muted); margin-bottom: 20px; }

/* Category cards */
.category-card { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; overflow: hidden; box-shadow: 0 1px 3px rgba(15,23,42,.04); }
.category-header { display: flex; flex-wrap: wrap; align-items: center; gap: 16px; padding: 16px 20px; border-bottom: 1px solid #f1f5f9; }
.category-thumb { width: 48px; height: 48px; border-radius: 12px; background: #f1f5f9; overflow: hidden; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.subcategory-row { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; padding: 12px 20px 12px 36px; border-bottom: 1px solid #f8fafc; background: #fafbfc; }
.subcategory-row:last-child { border-bottom: none; }

/* Login */
.login-wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #0d1117 0%, #151d2e 50%, #1a4d70 100%); padding: 24px; }
.login-card { width: 100%; max-width: 420px; background: #fff; border-radius: 20px; padding: 36px; box-shadow: 0 24px 64px rgba(0,0,0,.25); }
.login-logo { width: 56px; height: 56px; margin: 0 auto 16px; border-radius: 16px; background: linear-gradient(135deg, #3cb4f2, #2b9fd9); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800; color: #fff; box-shadow: 0 8px 24px rgba(60,180,242,.35); }

/* Auth login page */
.auth-page { min-height: 100vh; background: linear-gradient(160deg, #e8eef5 0%, #f4f7fb 45%, #eef3f9 100%); }
.auth-shell { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px 16px; }
.auth-card {
    display: grid; width: 100%; max-width: 980px; min-height: 580px;
    background: #fff; border-radius: 20px; overflow: hidden;
    box-shadow: 0 20px 50px rgba(15, 23, 42, .12), 0 4px 16px rgba(15, 23, 42, .06);
    border: 1px solid rgba(148, 163, 184, .25);
}
@media (min-width: 900px) { .auth-card { grid-template-columns: 1fr 1fr; } }

.auth-brand {
    position: relative; display: none; flex-direction: column; justify-content: center;
    background: linear-gradient(145deg, #0d1117 0%, #151d2e 55%, #1a4d70 100%);
    padding: 48px 40px; overflow: hidden;
}
@media (min-width: 900px) { .auth-brand { display: flex; } }

.auth-brand-pattern {
    position: absolute; inset: 0; opacity: .14;
    background-image: radial-gradient(circle at 20% 20%, #3cb4f2 0%, transparent 45%),
        radial-gradient(circle at 80% 80%, #2b9fd9 0%, transparent 40%);
    pointer-events: none;
}
.auth-brand-inner { position: relative; z-index: 1; text-align: center; }
.auth-brand-logo {
    width: min(280px, 85%); height: auto; margin: 0 auto 28px;
    filter: drop-shadow(0 12px 28px rgba(0, 0, 0, .35));
}
.auth-brand-title {
    font-size: 1.75rem; font-weight: 800; letter-spacing: -.02em;
    background: linear-gradient(90deg, #4cbef0, #3cb4f2);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
}
.auth-brand-subtitle { margin-top: 6px; font-size: 1rem; font-weight: 600; color: #7ec8f5; }
.auth-brand-tagline {
    margin-top: 24px; max-width: 300px; margin-left: auto; margin-right: auto;
    font-size: .875rem; line-height: 1.6; color: #64748b;
}

.auth-form-panel { display: flex; align-items: center; justify-content: center; padding: 40px 32px; }
.auth-form-inner { width: 100%; max-width: 380px; }
.auth-form-header { text-align: center; margin-bottom: 28px; }
.auth-form-logo { width: 72px; height: auto; margin: 0 auto 16px; }
@media (min-width: 900px) { .auth-form-logo { display: none; } }
.auth-form-title { font-size: 1.375rem; font-weight: 700; color: #0f172a; letter-spacing: -.02em; }
.auth-form-desc { margin-top: 8px; font-size: .875rem; color: #64748b; line-height: 1.5; }

.auth-alert {
    display: flex; align-items: flex-start; gap: 10px; margin-bottom: 20px;
    padding: 12px 14px; border-radius: 10px; font-size: .875rem;
    background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c;
}
.auth-form { display: flex; flex-direction: column; gap: 18px; }
.auth-field { display: flex; flex-direction: column; gap: 8px; }
.auth-label {
    font-size: .6875rem; font-weight: 700; letter-spacing: .08em;
    text-transform: uppercase; color: #64748b;
}
.auth-input {
    width: 100%; height: 46px; padding: 0 14px; border-radius: 10px;
    border: 1px solid #cbd5e1; background: #fff; font-size: .9375rem; color: #0f172a;
    transition: border-color .15s, box-shadow .15s;
}
.auth-input::placeholder { color: #94a3b8; }
.auth-input:focus {
    outline: none; border-color: #3cb4f2;
    box-shadow: 0 0 0 3px rgba(60, 180, 242, .18);
}
.auth-input-error { border-color: #f87171; box-shadow: 0 0 0 3px rgba(248, 113, 113, .12); }
.auth-password-wrap { position: relative; }
.auth-input-password { padding-right: 44px; }
.auth-password-toggle,
.admin-password-toggle {
    position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
    display: flex; align-items: center; justify-content: center;
    width: 28px; height: 28px; border: none; background: transparent;
    color: #94a3b8; cursor: pointer; border-radius: 6px;
}
.auth-password-toggle:hover { color: #475569; background: #f1f5f9; }
.auth-password-toggle svg { width: 18px; height: 18px; }
.auth-hint { font-size: .75rem; color: #94a3b8; line-height: 1.5; margin-top: -6px; }
.auth-remember {
    display: flex; align-items: center; gap: 10px; font-size: .875rem; color: #475569; cursor: pointer;
}
.auth-checkbox {
    width: 16px; height: 16px; border-radius: 4px;
    accent-color: #3cb4f2; cursor: pointer;
}
.auth-submit {
    width: 100%; height: 48px; margin-top: 4px; border: none; border-radius: 10px;
    background: linear-gradient(135deg, #3cb4f2 0%, #2b9fd9 100%);
    color: #fff; font-size: .9375rem; font-weight: 700; letter-spacing: .01em;
    cursor: pointer; transition: transform .15s, box-shadow .15s;
    box-shadow: 0 8px 20px rgba(60, 180, 242, .3);
}
.auth-submit:hover {
    transform: translateY(-1px);
    box-shadow: 0 12px 28px rgba(60, 180, 242, .38);
    background: linear-gradient(135deg, #4cbef0 0%, #3cb4f2 100%);
}

/* ── Mobile sidebar drawer ── */
.sidebar-backdrop {
    position: fixed; inset: 0; z-index: 29;
    background: rgba(15, 23, 42, 0.45);
    opacity: 0; visibility: hidden; pointer-events: none;
    transition: opacity .25s ease, visibility .25s ease;
}
body.sidebar-open .sidebar-backdrop {
    opacity: 1; visibility: visible; pointer-events: auto;
}
body.sidebar-open { overflow: hidden; }
@media (max-width: 1023px) {
    body.sidebar-open .admin-sidebar { transform: translateX(0); }
}
.sidebar-toggle {
    display: inline-flex; align-items: center; justify-content: center;
    width: 40px; height: 40px; flex-shrink: 0;
    border-radius: 10px; border: 1px solid var(--border);
    background: #fff; color: #334155; cursor: pointer;
    transition: background .15s, border-color .15s;
}
.sidebar-toggle:hover { background: #f8fafc; border-color: #cbd5e1; }
.sidebar-close {
    display: inline-flex; align-items: center; justify-content: center;
    width: 36px; height: 36px; flex-shrink: 0;
    border-radius: 8px; border: none; background: rgba(255,255,255,.08);
    color: #a8bac9; cursor: pointer;
}
.sidebar-close:hover { background: rgba(255,255,255,.14); color: #fff; }
.admin-header-inner {
    display: flex; align-items: center; gap: 10px;
    min-height: 64px; padding: 10px 16px;
}
@media (min-width: 640px) {
    .admin-header-inner { min-height: 72px; padding: 12px 24px; gap: 12px; }
}
@media (min-width: 1024px) {
    .admin-header-inner { padding: 12px 32px; }
    .sidebar-toggle { display: none; }
}

/* ── Responsive layout (phones & tablets) ── */
@media (max-width: 1023px) {
    .filter-field { flex: 1 1 100%; min-width: 0; max-width: none; }
    .filter-actions { margin-left: 0; width: 100%; justify-content: flex-start; }
    .filter-bar { padding: 14px 16px; }
    .filter-bar-inner { gap: 12px; }
    .page-toolbar { gap: 12px; }
    .page-toolbar > * { width: 100%; }
    .page-toolbar .btn { width: 100%; justify-content: center; }
    .stat-tabs { display: flex; width: 100%; overflow-x: auto; flex-wrap: nowrap; padding: 4px; -webkit-overflow-scrolling: touch; }
    .stat-tab { flex-shrink: 0; }
    .data-card-header { flex-direction: column; align-items: stretch; padding: 14px 16px; }
    .data-card-header > div:last-child { width: 100%; }
    .data-card-header .btn { width: 100%; justify-content: center; }
    .admin-table { min-width: 640px; }
    .admin-table thead th,
    .admin-table tbody td { padding: 10px 12px; font-size: 0.8125rem; }
    .overflow-x-auto { -webkit-overflow-scrolling: touch; }
    .form-card { padding: 20px 16px; border-radius: 12px; }
    .detail-header { padding: 16px; }
    .detail-panel { padding: 16px; }
    .detail-row { flex-direction: column; align-items: flex-start; gap: 4px; }
    .dash-stat { padding: 16px; }
    .dash-stat-value { font-size: 1.5rem; }
    .dash-panel { padding: 16px; }
    .action-group { flex-wrap: wrap; }
    .category-header { padding: 14px 16px; }
    .subcategory-row { padding: 12px 16px; }
    .pagination-wrap { padding: 12px 16px; }
    .settings-fields { grid-template-columns: 1fr; }
    .settings-faq-toolbar { flex-direction: column; }
    .settings-faq-question,
    .settings-faq-answer { max-width: none; }
    .modal-card { padding: 20px 16px; max-height: calc(100vh - 32px); overflow-y: auto; }
    .export-menu { right: auto; left: 0; min-width: 100%; }
    .user-cell { gap: 8px; }
    .user-avatar { width: 36px; height: 36px; font-size: 0.75rem; }
    .cell-truncate { max-width: 120px; }
    .form-card .flex.justify-end { flex-direction: column-reverse; }
    .form-card .flex.justify-end .btn { width: 100%; justify-content: center; }
    .auth-shell { padding: 16px 12px; }
    .auth-form-panel { padding: 28px 20px 32px; }
}

@media (max-width: 639px) {
    .admin-content { padding: 12px !important; }
    .btn { height: 40px; padding: 0 14px; font-size: 0.8125rem; }
    .btn-sm { height: 32px; }
    .dash-mini-value { font-size: 1rem; }
    .chart-panel,
    .chart-panel-trend { padding: 16px !important; }
    .chart-canvas-wrap { height: 220px !important; }
}
</style>
