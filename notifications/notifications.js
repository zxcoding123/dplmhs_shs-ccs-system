/**
 * Shared notification bell (admin, staff, students).
 *
 * Mounted into #csmsNotify by includes/notification-bell.php and powered by
 * notifications/api.php. Read / delete / read all / delete all happen in
 * place — no page reload. Public hooks:
 *   [data-csms-notify-badge]                 any element; receives the unread count
 *   [data-bs-target="#notificationsModal"]   any element; opens the full list
 *   window.CSMSNotify.refresh() / .openAll()
 */
(function () {
    'use strict';

    const root = document.getElementById('csmsNotify');
    if (!root || root.dataset.ready) return;
    root.dataset.ready = '1';

    const API = root.dataset.api;
    const BASE = root.dataset.base || '';
    const TZ = 'Asia/Manila';
    const POLL_MS = 30000;
    const MENU_PAGE = 8;
    const MODAL_PAGE = 20;

    const svg = (d, size = 18) => `<svg width="${size}" height="${size}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${d}</svg>`;
    const ICON = {
        bell: svg('<path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/>'),
        check: svg('<path d="m5 12 5 5L20 7"/>', 16),
        checkAll: svg('<path d="m2 12 5 5L18 6"/><path d="m12 16 1 1L22 8"/>', 16),
        x: svg('<path d="M18 6 6 18M6 6l12 12"/>', 16),
        trash: svg('<path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/>', 16),
        class: svg('<path d="M3 21h18M5 21V7l7-4 7 4v14"/><path d="M9 21v-6h6v6"/>'),
        assessment: svg('<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>'),
        grade: svg('<path d="M12 2l3 7h7l-5.5 4.5L18 21l-6-4-6 4 1.5-7.5L2 9h7z"/>'),
        user: svg('<path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="10" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/>'),
        idea: svg('<path d="M9 18h6M10 22h4"/><path d="M12 2a7 7 0 0 0-4 12.7V17h8v-2.3A7 7 0 0 0 12 2z"/>'),
        calendar: svg('<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>'),
        doc: svg('<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h5"/>'),
        alert: svg('<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/>'),
        empty: svg('<path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/><path d="m8.5 11 2.5 2.5L16 8.5"/>', 30),
    };

    // Notification `type` values used across the app -> [icon, tone, label]
    const KINDS = [
        [/class|enrol|section|subject/, 'class', 'blue', 'Class'],
        [/assess|activit|material|quiz|exam/, 'assessment', 'violet', 'Activity'],
        [/grade|score/, 'grade', 'gold', 'Grades'],
        [/student|staff|account|regist/, 'user', 'green', 'Account'],
        [/idea/, 'idea', 'gold', 'Idea'],
        [/attend|calendar|meeting|event/, 'calendar', 'blue', 'Schedule'],
        [/document|request|record/, 'doc', 'violet', 'Records'],
        [/alert|warn|risk|fail/, 'alert', 'red', 'Alert'],
    ];
    const kindOf = (type) => {
        for (const [re, icon, tone, label] of KINDS) {
            if (re.test(type)) return { icon, tone, label };
        }
        return { icon: 'bell', tone: 'navy', label: '' };
    };

    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    const rtf = typeof Intl.RelativeTimeFormat === 'function' ? new Intl.RelativeTimeFormat('en', { numeric: 'auto' }) : null;
    const fullDate = (d) => d.toLocaleString('en-PH', { timeZone: TZ, dateStyle: 'medium', timeStyle: 'short' });
    function timeAgo(iso) {
        if (!iso) return '';
        const d = new Date(iso);
        const sec = Math.round((d - Date.now()) / 1000);
        const abs = Math.abs(sec);
        if (abs < 45) return 'Just now';
        if (!rtf || abs > 7 * 86400) return d.toLocaleDateString('en-PH', { timeZone: TZ, month: 'short', day: 'numeric', year: abs > 300 * 86400 ? 'numeric' : undefined });
        if (abs < 3600) return rtf.format(Math.round(sec / 60), 'minute');
        if (abs < 86400) return rtf.format(Math.round(sec / 3600), 'hour');
        return rtf.format(Math.round(sec / 86400), 'day');
    }

    /* ------------------------------------------------------------------
     * API + shared counts
     * ------------------------------------------------------------------ */

    const counts = { unread: null, total: null };

    async function api(action, params = {}, method = 'GET') {
        let url = API;
        const opts = { method, credentials: 'same-origin', headers: { 'X-Requested-With': 'CSMSNotify' } };
        if (method === 'GET') {
            url += '?' + new URLSearchParams({ action, ...params });
        } else {
            const body = new FormData();
            body.append('action', action);
            Object.entries(params).forEach(([k, v]) => body.append(k, v));
            opts.body = body;
        }
        const res = await fetch(url, opts);
        let data;
        try { data = await res.json(); } catch (e) { data = { ok: false, error: 'Unexpected server response.' }; }
        if (typeof data.unread === 'number') setCounts(data);
        if (!data.ok && res.status !== 404) throw new Error(data.error || 'Request failed.');
        return data;
    }

    function setCounts(data) {
        counts.unread = data.unread;
        if (typeof data.total === 'number') counts.total = data.total;
        const label = counts.unread > 99 ? '99+' : String(counts.unread);
        document.querySelectorAll('[data-csms-notify-badge]').forEach((el) => {
            el.textContent = label;
            el.hidden = counts.unread === 0;
            el.style.display = counts.unread === 0 ? 'none' : '';
        });
        panels.forEach((p) => p.renderHead());
    }

    /* ------------------------------------------------------------------
     * Panel — one list view; used by the dropdown and by the modal
     * ------------------------------------------------------------------ */

    const panels = [];

    function createPanel(host, { pageSize, variant }) {
        const state = { filter: 'all', items: [], hasMore: false, loading: false, loaded: false, error: '', confirming: false, stale: true };

        host.classList.add('cn-panel', 'cn-panel--' + variant);
        host.innerHTML = `
            <div class="cn-head">
                <div class="cn-head-main">
                    <div class="cn-title">Notifications</div>
                    <div class="cn-sub" data-el="sub"></div>
                </div>
                <div class="cn-head-actions">
                    <button type="button" class="cn-btn" data-act="read-all" title="Mark all as read">${ICON.checkAll}<span>Mark all read</span></button>
                    <button type="button" class="cn-btn cn-btn--danger" data-act="ask-delete-all" title="Delete all notifications">${ICON.trash}<span>Clear all</span></button>
                    ${variant === 'modal' ? `<button type="button" class="cn-icon-btn" data-bs-dismiss="modal" aria-label="Close">${ICON.x}</button>` : ''}
                </div>
            </div>
            <div class="cn-confirm" data-el="confirm" hidden>
                <span data-el="confirm-text">Delete all notifications?</span>
                <div>
                    <button type="button" class="cn-btn" data-act="cancel-delete-all">Cancel</button>
                    <button type="button" class="cn-btn cn-btn--solid-danger" data-act="delete-all">Delete all</button>
                </div>
            </div>
            <div class="cn-tabs" role="tablist">
                <button type="button" class="cn-tab is-active" role="tab" data-filter="all" aria-selected="true">All</button>
                <button type="button" class="cn-tab" role="tab" data-filter="unread" aria-selected="false">Unread <span class="cn-tab-count" data-el="tab-count"></span></button>
            </div>
            <div class="cn-error" data-el="error" role="alert" hidden></div>
            <div class="cn-list" data-el="list" aria-live="polite"></div>
            <div class="cn-foot" data-el="foot"></div>
        `;

        const $ = (sel) => host.querySelector(`[data-el="${sel}"]`);

        function renderHead() {
            const u = counts.unread;
            $('sub').textContent = u === null ? '' : u === 0 ? "You're all caught up" : `${u} unread`;
            $('tab-count').textContent = u ? (u > 99 ? '99+' : u) : '';
            host.querySelector('[data-act="read-all"]').disabled = !u;
            host.querySelector('[data-act="ask-delete-all"]').disabled = counts.total === 0 && state.loaded;
            const total = counts.total;
            $('confirm-text').textContent = total ? `Delete all ${total} notification${total === 1 ? '' : 's'}? This can't be undone.` : 'Delete all notifications?';
        }

        function itemHtml(n) {
            const k = kindOf(n.type);
            const href = n.href ? (/^https?:\/\//i.test(n.href) ? n.href : BASE + n.href) : '';
            const tag = href ? 'a' : 'div';
            const meta = [timeAgo(n.date), k.label].filter(Boolean).join(' · ');
            return `
                <div class="cn-item${n.unread ? ' is-unread' : ''}" data-id="${n.id}" role="listitem">
                    <span class="cn-ico cn-tone-${k.tone}">${ICON[k.icon]}</span>
                    <${tag} class="cn-body"${href ? ` href="${esc(href)}"` : ''} data-act="open">
                        <span class="cn-item-title">${esc(n.title)}</span>
                        ${n.description ? `<span class="cn-desc">${esc(n.description)}</span>` : ''}
                        <span class="cn-time"${n.date ? ` title="${esc(fullDate(new Date(n.date)))}"` : ''}>${esc(meta)}</span>
                    </${tag}>
                    <span class="cn-dot" aria-label="Unread"></span>
                    <div class="cn-actions">
                        ${n.unread ? `<button type="button" class="cn-icon-btn" data-act="read" title="Mark as read" aria-label="Mark as read">${ICON.check}</button>` : ''}
                        <button type="button" class="cn-icon-btn cn-icon-btn--danger" data-act="delete" title="Delete" aria-label="Delete">${ICON.x}</button>
                    </div>
                </div>`;
        }

        function renderList() {
            const list = $('list');
            const err = $('error');
            err.hidden = !state.error;
            err.textContent = state.error;

            if (!state.loaded && state.loading) {
                list.innerHTML = '<div class="cn-skel"></div>'.repeat(3);
            } else if (!state.items.length) {
                const unreadTab = state.filter === 'unread';
                list.innerHTML = `
                    <div class="cn-empty">
                        <span class="cn-empty-ico">${ICON.empty}</span>
                        <strong>${unreadTab ? "You're all caught up" : 'No notifications yet'}</strong>
                        <span>${unreadTab ? 'No unread notifications.' : "We'll let you know when something happens."}</span>
                    </div>`;
            } else {
                list.setAttribute('role', 'list');
                list.innerHTML = state.items.map(itemHtml).join('');
            }

            const foot = $('foot');
            if (variant === 'menu') {
                foot.innerHTML = `<button type="button" class="cn-foot-link" data-act="open-all">View all notifications</button>`;
            } else {
                foot.innerHTML = state.hasMore
                    ? `<button type="button" class="cn-btn cn-btn--block" data-act="more"${state.loading ? ' disabled' : ''}>${state.loading ? 'Loading…' : 'Load more'}</button>`
                    : '';
                foot.hidden = !state.hasMore;
            }
            renderHead();
        }

        async function load(append = false) {
            if (state.loading) return;
            state.loading = true;
            if (!append) state.error = '';
            renderList();
            try {
                const params = { filter: state.filter, limit: pageSize };
                if (append && state.items.length) params.before = state.items[state.items.length - 1].id;
                const data = await api('list', params);
                state.items = append ? state.items.concat(data.items) : data.items;
                state.hasMore = data.has_more;
                state.loaded = true;
                state.stale = false;
            } catch (e) {
                state.error = "Couldn't load notifications. " + e.message;
            } finally {
                state.loading = false;
                renderList();
            }
        }

        function setFilter(filter) {
            if (state.filter === filter) return;
            state.filter = filter;
            host.querySelectorAll('.cn-tab').forEach((t) => {
                const on = t.dataset.filter === filter;
                t.classList.toggle('is-active', on);
                t.setAttribute('aria-selected', on);
            });
            state.loaded = false;
            state.items = [];
            load();
        }

        function showConfirm(on) {
            state.confirming = on;
            $('confirm').hidden = !on;
        }

        function flash(msg) {
            state.error = msg;
            renderList();
        }

        // Optimistic: update the UI first, roll back if the server says no.
        async function markRead(id) {
            const n = state.items.find((x) => x.id === id);
            if (!n || !n.unread) return;
            n.unread = false;
            if (state.filter === 'unread') state.items = state.items.filter((x) => x !== n);
            renderList();
            try {
                await api('read', { id }, 'POST');
                others(host).forEach((p) => p.patch(id, { unread: false }));
            } catch (e) {
                n.unread = true;
                load();
                flash(e.message);
            }
        }

        async function remove(id) {
            const el = host.querySelector(`.cn-item[data-id="${id}"]`);
            if (el) {
                el.style.height = el.offsetHeight + 'px';
                el.classList.add('is-leaving');
                requestAnimationFrame(() => { el.style.height = '0px'; });
            }
            const before = state.items;
            state.items = state.items.filter((x) => x.id !== id);
            try {
                await api('delete', { id }, 'POST');
                others(host).forEach((p) => p.drop(id));
                setTimeout(renderList, 180);
                // Pull the next item up so the dropdown stays full
                if (variant === 'menu' && state.hasMore && state.items.length < pageSize) load();
            } catch (e) {
                state.items = before;
                flash(e.message);
            }
        }

        async function readAll() {
            const before = state.items.map((n) => n.unread);
            state.items.forEach((n) => { n.unread = false; });
            if (state.filter === 'unread') state.items = [];
            renderList();
            try {
                await api('read_all', {}, 'POST');
                others(host).forEach((p) => p.patchAll({ unread: false }));
            } catch (e) {
                state.items.forEach((n, i) => { n.unread = before[i]; });
                load();
                flash(e.message);
            }
        }

        async function deleteAll() {
            showConfirm(false);
            const before = state.items;
            state.items = [];
            state.hasMore = false;
            renderList();
            try {
                await api('delete_all', {}, 'POST');
                others(host).forEach((p) => p.clear());
            } catch (e) {
                state.items = before;
                flash(e.message);
            }
        }

        host.addEventListener('click', (e) => {
            const tab = e.target.closest('.cn-tab');
            if (tab) return setFilter(tab.dataset.filter);

            const btn = e.target.closest('[data-act]');
            if (!btn || !host.contains(btn)) return;
            const item = btn.closest('.cn-item');
            const id = item ? Number(item.dataset.id) : 0;

            switch (btn.dataset.act) {
                case 'read': e.preventDefault(); return markRead(id);
                case 'delete': e.preventDefault(); return remove(id);
                case 'read-all': return readAll();
                case 'ask-delete-all': return showConfirm(!state.confirming);
                case 'cancel-delete-all': return showConfirm(false);
                case 'delete-all': return deleteAll();
                case 'more': return load(true);
                case 'open-all': return openAll();
                case 'open': {
                    const n = state.items.find((x) => x.id === id);
                    if (n && n.unread) {
                        // keepalive lets the request finish while the browser navigates away
                        const body = new FormData();
                        body.append('action', 'read');
                        body.append('id', id);
                        fetch(API, { method: 'POST', body, credentials: 'same-origin', keepalive: true, headers: { 'X-Requested-With': 'CSMSNotify' } })
                            .then((r) => r.json()).then(setCounts).catch(() => {});
                        n.unread = false;
                        renderList();
                    }
                }
            }
        });

        const panel = {
            host,
            renderHead,
            load,
            isStale: () => state.stale,
            markStale: () => { state.stale = true; },
            patch(id, fields) { const n = state.items.find((x) => x.id === id); if (n) { Object.assign(n, fields); if (state.filter === 'unread') state.items = state.items.filter((x) => x.unread); renderList(); } },
            patchAll(fields) { state.items.forEach((n) => Object.assign(n, fields)); if (state.filter === 'unread') state.items = []; renderList(); },
            drop(id) { state.items = state.items.filter((x) => x.id !== id); renderList(); },
            clear() { state.items = []; state.hasMore = false; renderList(); },
            resetConfirm: () => showConfirm(false),
        };
        panels.push(panel);
        renderList();
        return panel;
    }

    const others = (host) => panels.filter((p) => p.host !== host);

    /* ------------------------------------------------------------------
     * Dropdown
     * ------------------------------------------------------------------ */

    const menu = createPanel(root.querySelector('[data-el="menu"]'), { pageSize: MENU_PAGE, variant: 'menu' });
    root.addEventListener('show.bs.dropdown', () => menu.load());
    root.addEventListener('hidden.bs.dropdown', () => menu.resetConfirm());

    /* ------------------------------------------------------------------
     * "All notifications" modal (id kept for existing data-bs-target links)
     * ------------------------------------------------------------------ */

    let modalEl = document.getElementById('notificationsModal');
    if (modalEl) modalEl.remove();
    modalEl = document.createElement('div');
    modalEl.className = 'modal fade cn-modal';
    modalEl.id = 'notificationsModal';
    modalEl.tabIndex = -1;
    modalEl.setAttribute('aria-label', 'All notifications');
    modalEl.setAttribute('aria-hidden', 'true');
    modalEl.innerHTML = `
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
            <div class="modal-content" data-el="modal-panel"></div>
        </div>`;
    document.body.appendChild(modalEl);

    const modal = createPanel(modalEl.querySelector('[data-el="modal-panel"]'), { pageSize: MODAL_PAGE, variant: 'modal' });
    modalEl.addEventListener('show.bs.modal', () => modal.load());
    modalEl.addEventListener('hidden.bs.modal', () => modal.resetConfirm());

    function openAll() {
        const toggle = root.querySelector('[data-bs-toggle="dropdown"]');
        if (window.bootstrap) {
            window.bootstrap.Dropdown.getOrCreateInstance(toggle).hide();
            window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    }

    /* ------------------------------------------------------------------
     * Background refresh of the badge
     * ------------------------------------------------------------------ */

    async function refresh() {
        try {
            const before = counts.unread;
            await api('unread');
            if (before !== null && counts.unread !== before) panels.forEach((p) => p.markStale());
        } catch (e) { /* offline or logged out — try again next tick */ }
    }

    let timer = setInterval(() => { if (!document.hidden) refresh(); }, POLL_MS);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
    window.addEventListener('pagehide', () => clearInterval(timer));

    refresh();

    window.CSMSNotify = { refresh, openAll };
})();
