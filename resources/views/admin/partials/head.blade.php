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
                brand: { 50:'#ecfdf5',100:'#d1fae5',500:'#10b981',600:'#059669',700:'#047857',900:'#064e3b' },
                sidebar: { DEFAULT:'#0b1220', light:'#151d2e', border:'#1e293b' }
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
    --brand: #059669;
    --brand-light: #ecfdf5;
    --text: #0f172a;
    --muted: #64748b;
    --border: #e2e8f0;
    --surface: #ffffff;
    --bg: #f1f5f9;
}
body { background: var(--bg); color: var(--text); font-family: 'Plus Jakarta Sans', system-ui, sans-serif; }

/* Inputs */
.admin-input {
    width: 100%; height: 42px; border-radius: 10px; border: 1px solid var(--border);
    background: #fff; padding: 0 14px; font-size: 0.875rem; color: var(--text);
    transition: border-color .15s, box-shadow .15s;
}
.admin-input:focus { border-color: var(--brand); outline: none; box-shadow: 0 0 0 3px rgba(5,150,105,.12); }
textarea.admin-input { height: auto; padding: 12px 14px; min-height: 90px; }
.admin-label { display: block; margin-bottom: 6px; font-size: 0.75rem; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; color: var(--muted); }

/* Buttons */
.btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; height: 42px; padding: 0 18px; border-radius: 10px; font-size: 0.875rem; font-weight: 600; transition: all .15s; white-space: nowrap; }
.btn-primary { background: linear-gradient(135deg, #10b981, #059669); color: #fff; box-shadow: 0 4px 14px rgba(5,150,105,.25); }
.btn-primary:hover { background: linear-gradient(135deg, #059669, #047857); transform: translateY(-1px); }
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
.stat-tab.active { background: var(--brand-light); color: var(--brand); box-shadow: inset 0 0 0 1px rgba(5,150,105,.15); }
.stat-tab .count { display: inline-flex; min-width: 22px; align-items: center; justify-content: center; padding: 2px 7px; border-radius: 999px; font-size: 0.6875rem; font-weight: 700; background: rgba(100,116,139,.12); color: var(--muted); }
.stat-tab.active .count { background: rgba(5,150,105,.15); color: var(--brand); }
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
.user-avatar { display: flex; width: 40px; height: 40px; align-items: center; justify-content: center; border-radius: 10px; font-size: 0.875rem; font-weight: 700; background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #047857; flex-shrink: 0; }
.user-name { font-weight: 600; color: var(--text); line-height: 1.3; }
.user-sub { font-size: 0.75rem; color: var(--muted); margin-top: 2px; }

/* Badges */
.badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 999px; font-size: 0.6875rem; font-weight: 700; letter-spacing: .02em; text-transform: capitalize; }
.badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; opacity: .7; }
.badge-success { background: #ecfdf5; color: #047857; }
.badge-danger { background: #fef2f2; color: #dc2626; }
.badge-warning { background: #fffbeb; color: #d97706; }
.badge-info { background: #eff6ff; color: #2563eb; }
.badge-neutral { background: #f1f5f9; color: #64748b; }

/* Action pills */
.action-group { display: flex; gap: 6px; }
.action-btn { display: inline-flex; align-items: center; gap: 4px; padding: 6px 12px; border-radius: 8px; font-size: 0.8125rem; font-weight: 600; color: var(--muted); background: #f8fafc; border: 1px solid transparent; transition: all .12s; cursor: pointer; }
button.action-btn { font-family: inherit; }
.action-btn:hover { color: var(--brand); background: var(--brand-light); border-color: rgba(5,150,105,.2); }
.action-btn.danger:hover { color: #dc2626; background: #fef2f2; border-color: rgba(220,38,38,.15); }

/* Alerts */
.alert { display: flex; align-items: flex-start; gap: 12px; padding: 14px 16px; border-radius: 12px; margin-bottom: 20px; font-size: 0.875rem; }
.alert-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
.alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }

/* Form card */
.form-card { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 28px; box-shadow: 0 1px 3px rgba(15,23,42,.04); }
.form-section-title { font-size: 1rem; font-weight: 700; color: var(--text); margin-bottom: 4px; }
.form-section-desc { font-size: 0.8125rem; color: var(--muted); margin-bottom: 24px; }

/* Pagination */
.pagination-wrap { padding: 16px 20px; border-top: 1px solid #f1f5f9; }
.pagination-wrap nav { display: flex; justify-content: center; }
.pagination-wrap nav span, .pagination-wrap nav a { border-radius: 8px !important; }

/* Empty state */
.empty-state { padding: 48px 24px; text-align: center; }
.empty-state-icon { width: 56px; height: 56px; margin: 0 auto 16px; border-radius: 14px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: #94a3b8; }
.empty-state p { color: var(--muted); font-size: 0.9375rem; }

/* Sidebar polish */
.sidebar-link { display: flex; align-items: center; gap: 12px; padding: 10px 14px; margin-bottom: 2px; border-radius: 10px; font-size: 0.8125rem; font-weight: 500; color: #94a3b8; transition: all .15s; border-left: 3px solid transparent; }
.sidebar-link:hover { color: #e2e8f0; background: rgba(255,255,255,.05); }
.sidebar-link.active { color: #6ee7b7; background: rgba(16,185,129,.12); border-left-color: #10b981; font-weight: 600; }
.sidebar-link svg { opacity: .75; flex-shrink: 0; }
.sidebar-link.active svg { opacity: 1; color: #34d399; }

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
.login-wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #0b1220 0%, #151d2e 50%, #064e3b 100%); padding: 24px; }
.login-card { width: 100%; max-width: 420px; background: #fff; border-radius: 20px; padding: 36px; box-shadow: 0 24px 64px rgba(0,0,0,.25); }
.login-logo { width: 56px; height: 56px; margin: 0 auto 16px; border-radius: 16px; background: linear-gradient(135deg, #10b981, #059669); display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800; color: #fff; box-shadow: 0 8px 24px rgba(5,150,105,.35); }
</style>
