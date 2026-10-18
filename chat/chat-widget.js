/**
 * Shared chat widget (admin, staff, students, public site).
 *
 * Mounted into #csmsChat by includes/floating-chat-widget.php and powered by
 * chat/api.php. Public hooks:
 *   [data-csms-chat-open]   any element; clicking it opens the widget
 *   [data-csms-chat-badge]  any element; receives the unread count
 *   window.CSMSChat.open() / .close() / .openConversation(id, type, name)
 */
(function () {
    'use strict';

    const root = document.getElementById('csmsChat');
    if (!root || root.dataset.ready) return;
    root.dataset.ready = '1';

    const API = root.dataset.api;
    const TZ = 'Asia/Manila';
    const MAX_LEN = 2000;
    const CONVO_POLL_MS = 3000;
    const LIST_POLL_MS = 10000;
    const UNREAD_POLL_MS = 20000;
    const ROLE_LABEL = { admin: 'Admin', staff: 'Teacher', student: 'Student' };

    const ICON = {
        chat: '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.4 8.4 0 0 1-9 8.4 8.6 8.6 0 0 1-3.8-.9L3 21l1.9-5.1A8.4 8.4 0 0 1 12 3.1a8.4 8.4 0 0 1 9 8.4z"/></svg>',
        close: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>',
        back: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>',
        search: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>',
        send: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13"/><path d="M22 2 15 22l-4-9-9-4 20-7z"/></svg>',
        groupAdd: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></svg>',
        group: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/></svg>',
        info: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 16v-4M12 8h.01"/></svg>',
        trash: '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg>',
        check: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 5 5L20 7"/></svg>',
        x: '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>',
        inbox: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.4 8.4 0 0 1-9 8.4 8.6 8.6 0 0 1-3.8-.9L3 21l1.9-5.1A8.4 8.4 0 0 1 12 3.1a8.4 8.4 0 0 1 9 8.4z"/><path d="M8 11h8M8 14.5h5"/></svg>',
    };

    /* ------------------------------------------------------------------
     * Markup
     * ------------------------------------------------------------------ */

    const head = (inner) => `<header class="cc-head">${inner}</header>`;
    const closeBtn = `<button type="button" class="cc-icon-btn" data-act="close" aria-label="Close messages">${ICON.close}</button>`;
    const backBtn = `<button type="button" class="cc-icon-btn" data-act="back" aria-label="Back">${ICON.back}</button>`;

    root.innerHTML = `
        <button type="button" class="cc-launcher" data-act="toggle" aria-label="Open messages" aria-expanded="false">
            <span class="cc-icon-chat">${ICON.chat}</span>
            <span class="cc-icon-close">${ICON.close}</span>
            <span class="cc-count" data-el="launcher-count" hidden></span>
        </button>

        <section class="cc-panel" role="dialog" aria-label="Messages">
            <div class="cc-view is-active" data-view="list">
                ${head(`
                    <div class="cc-head-main">
                        <div class="cc-title">Messages</div>
                        <div class="cc-sub">Chat with teachers, students and staff</div>
                    </div>
                    <button type="button" class="cc-icon-btn" data-act="new-group" aria-label="New group" title="New group">${ICON.groupAdd}</button>
                    ${closeBtn}
                `)}
                <div class="cc-toolbar">
                    <div class="cc-tabs" role="tablist">
                        <button type="button" class="cc-tab is-active" role="tab" data-tab="chats" aria-selected="true">
                            Chats <span class="cc-tab-count" data-el="tab-count" hidden></span>
                        </button>
                        <button type="button" class="cc-tab" role="tab" data-tab="groups" aria-selected="false">Groups</button>
                    </div>
                    <label class="cc-search">
                        ${ICON.search}
                        <input type="search" data-el="search" placeholder="Search people…" autocomplete="off" aria-label="Search">
                    </label>
                </div>
                <div class="cc-scroll" data-el="list"></div>
            </div>

            <div class="cc-view" data-view="thread">
                ${head(`
                    ${backBtn}
                    <div class="cc-avatar" data-el="t-avatar"></div>
                    <div class="cc-head-main">
                        <div class="cc-title" data-el="t-name"></div>
                        <div class="cc-sub" data-el="t-sub"></div>
                    </div>
                    <button type="button" class="cc-icon-btn" data-act="group-info" data-el="t-info" aria-label="Group details" hidden>${ICON.info}</button>
                    ${closeBtn}
                `)}
                <div class="cc-thread" data-el="thread" aria-live="polite"></div>
                <form class="cc-composer" data-el="composer">
                    <textarea rows="1" data-el="input" placeholder="Write a message…" maxlength="${MAX_LEN}" aria-label="Message"></textarea>
                    <button type="submit" class="cc-send" data-el="send" aria-label="Send" disabled>${ICON.send}</button>
                </form>
            </div>

            <div class="cc-view" data-view="newgroup">
                ${head(`
                    ${backBtn}
                    <div class="cc-head-main">
                        <div class="cc-title">New group</div>
                        <div class="cc-sub">Name it and add people</div>
                    </div>
                    ${closeBtn}
                `)}
                <div class="cc-form">
                    <div class="cc-field">
                        <label for="ccGroupName">Group name</label>
                        <input id="ccGroupName" data-el="g-name" maxlength="100" autocomplete="off" placeholder="e.g. Grade 11 – STEM A">
                    </div>
                    <div class="cc-field">
                        <label for="ccGroupDesc">Description <span style="font-weight:400;color:var(--cc-muted)">(optional)</span></label>
                        <input id="ccGroupDesc" data-el="g-desc" maxlength="500" autocomplete="off">
                    </div>
                    <label class="cc-search">
                        ${ICON.search}
                        <input type="search" data-el="g-search" placeholder="Search people to add…" autocomplete="off" aria-label="Search people to add">
                    </label>
                </div>
                <div class="cc-chips" data-el="g-chips"></div>
                <p class="cc-error" data-el="g-error" hidden></p>
                <div class="cc-scroll" data-el="g-results"></div>
                <div class="cc-foot">
                    <button type="button" class="cc-btn cc-btn-ghost" data-act="back">Cancel</button>
                    <button type="button" class="cc-btn cc-btn-primary" data-act="create-group" data-el="g-create" disabled>Create group</button>
                </div>
            </div>

            <div class="cc-view" data-view="groupinfo">
                ${head(`
                    ${backBtn}
                    <div class="cc-avatar" data-type="group">${ICON.group}</div>
                    <div class="cc-head-main">
                        <div class="cc-title" data-el="i-name"></div>
                        <div class="cc-sub" data-el="i-sub"></div>
                    </div>
                    ${closeBtn}
                `)}
                <div class="cc-group-desc" data-el="i-desc"></div>
                <div class="cc-section-label">Members</div>
                <div class="cc-scroll" data-el="i-members"></div>
                <div class="cc-foot">
                    <button type="button" class="cc-btn cc-btn-danger" data-act="leave-group" data-el="i-leave">Leave group</button>
                </div>
            </div>
        </section>
    `;

    const $ = (name) => root.querySelector(`[data-el="${name}"]`);
    const el = {
        launcherCount: $('launcher-count'),
        tabCount: $('tab-count'),
        search: $('search'),
        list: $('list'),
        tAvatar: $('t-avatar'),
        tName: $('t-name'),
        tSub: $('t-sub'),
        tInfo: $('t-info'),
        thread: $('thread'),
        composer: $('composer'),
        input: $('input'),
        send: $('send'),
        gName: $('g-name'),
        gDesc: $('g-desc'),
        gSearch: $('g-search'),
        gChips: $('g-chips'),
        gError: $('g-error'),
        gResults: $('g-results'),
        gCreate: $('g-create'),
        iName: $('i-name'),
        iSub: $('i-sub'),
        iDesc: $('i-desc'),
        iMembers: $('i-members'),
        iLeave: $('i-leave'),
    };

    /* ------------------------------------------------------------------
     * State
     * ------------------------------------------------------------------ */

    const state = {
        open: false,
        view: 'list',
        tab: 'chats',
        conversations: [],
        groups: [],
        convo: null,            // { kind: 'dm'|'group', id, type, name, ... }
        lastId: 0,
        rendered: new Set(),
        lastRun: null,          // { day, sender, ts, el }
        sending: 0,
        locked: false,
        selected: new Map(),    // new-group members
        timers: {},
        searchSeq: 0,
    };

    /* ------------------------------------------------------------------
     * Helpers
     * ------------------------------------------------------------------ */

    class ChatError extends Error {
        constructor(message, code) {
            super(message);
            this.code = code;
        }
    }

    async function api(action, params = {}, method = 'GET') {
        const opts = { credentials: 'same-origin', headers: { 'X-Requested-With': 'CSMSChat' } };
        let url = API;
        if (method === 'GET') {
            url += '?' + new URLSearchParams({ action, ...params, _: Date.now() });
        } else {
            const body = new FormData();
            body.append('action', action);
            Object.entries(params).forEach(([k, v]) => body.append(k, v));
            opts.method = 'POST';
            opts.body = body;
        }

        let res;
        try {
            res = await fetch(url, opts);
        } catch (e) {
            throw new ChatError('Network error. Check your connection.', 'network');
        }
        let data;
        try {
            data = await res.json();
        } catch (e) {
            throw new ChatError('Unexpected response from the server.', 'bad_response');
        }
        if (!data.ok) throw new ChatError(data.error || 'Something went wrong.', data.code);
        return data;
    }

    function esc(value) {
        return String(value ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    // Escape text and turn http(s):// and www. URLs into safe links.
    const URL_RE = /\b(?:https?:\/\/|www\.)[^\s<>"']+/gi;
    function linkify(text) {
        const str = String(text ?? '');
        let html = '';
        let last = 0;
        for (const match of str.matchAll(URL_RE)) {
            let url = match[0];
            // Leave trailing punctuation (and unbalanced closing brackets) outside the link.
            while (/[.,!?;:]$/.test(url) || (/\)$/.test(url) && (url.match(/\(/g) || []).length < (url.match(/\)/g) || []).length)) {
                url = url.slice(0, -1);
            }
            const href = /^https?:\/\//i.test(url) ? url : 'https://' + url;
            html += esc(str.slice(last, match.index));
            html += `<a href="${esc(href)}" target="_blank" rel="noopener noreferrer nofollow">${esc(url)}</a>`;
            last = match.index + url.length;
        }
        return html + esc(str.slice(last));
    }

    function initials(name) {
        const parts = String(name || '?').trim().split(/\s+/).filter(Boolean);
        if (!parts.length) return '?';
        const first = parts[0][0] || '';
        const last = parts.length > 1 ? parts[parts.length - 1][0] : '';
        return (first + last).toUpperCase();
    }

    const fmtCache = {};
    function fmt(key, opts) {
        if (!fmtCache[key]) fmtCache[key] = new Intl.DateTimeFormat('en-PH', { timeZone: TZ, ...opts });
        return fmtCache[key];
    }

    const toDate = (iso) => {
        const d = iso ? new Date(iso) : null;
        return d && !isNaN(d.getTime()) ? d : null;
    };
    const dayKey = (d) => fmt('day', { year: 'numeric', month: '2-digit', day: '2-digit' }).format(d);
    const clock = (d) => fmt('clock', { hour: 'numeric', minute: '2-digit' }).format(d);

    function relativeDay(d) {
        const now = new Date();
        if (dayKey(d) === dayKey(now)) return 'today';
        if (dayKey(d) === dayKey(new Date(now.getTime() - 864e5))) return 'yesterday';
        return null;
    }

    function listTime(iso) {
        const d = toDate(iso);
        if (!d) return '';
        const rel = relativeDay(d);
        if (rel === 'today') return clock(d);
        if (rel === 'yesterday') return 'Yesterday';
        const ageDays = (Date.now() - d.getTime()) / 864e5;
        if (ageDays < 6) return fmt('wd', { weekday: 'short' }).format(d);
        const sameYear = fmt('y', { year: 'numeric' }).format(d) === fmt('y', { year: 'numeric' }).format(new Date());
        return sameYear
            ? fmt('md', { month: 'short', day: 'numeric' }).format(d)
            : fmt('mdy', { month: 'short', day: 'numeric', year: 'numeric' }).format(d);
    }

    function dayLabel(d) {
        const rel = relativeDay(d);
        if (rel === 'today') return 'Today';
        if (rel === 'yesterday') return 'Yesterday';
        return fmt('long', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' }).format(d);
    }

    const avatar = (name, type) => `<div class="cc-avatar" data-type="${esc(type)}" aria-hidden="true">${esc(initials(name))}</div>`;
    const roleChip = (type) => ROLE_LABEL[type] ? `<span class="cc-role" data-type="${esc(type)}">${ROLE_LABEL[type]}</span>` : '';

    function emptyState(title, text) {
        return `<div class="cc-empty"><div class="cc-empty-icon">${ICON.inbox}</div><strong>${esc(title)}</strong><p>${esc(text)}</p></div>`;
    }

    function skeleton(rows = 4) {
        return Array.from({ length: rows }, () =>
            '<div class="cc-skeleton"><i></i><span><i style="height:12px;width:55%"></i><i style="height:10px;width:80%"></i></span></div>'
        ).join('');
    }

    function setTimer(name, fn, ms) {
        clearInterval(state.timers[name]);
        state.timers[name] = setInterval(() => { if (!document.hidden) fn(); }, ms);
    }

    function clearTimer(name) {
        clearInterval(state.timers[name]);
        delete state.timers[name];
    }

    /* ------------------------------------------------------------------
     * Unread badges
     * ------------------------------------------------------------------ */

    function setUnread(count) {
        const n = Number(count) || 0;
        const label = n > 99 ? '99+' : String(n);
        [el.launcherCount, el.tabCount].forEach((b) => {
            b.textContent = label;
            b.hidden = n === 0;
        });
        document.querySelectorAll('[data-csms-chat-badge]').forEach((b) => {
            b.textContent = label;
            b.hidden = n === 0;
            b.style.display = n === 0 ? 'none' : '';
        });
        root.querySelector('.cc-launcher').setAttribute('aria-label', n ? `Open messages (${n} unread)` : 'Open messages');
    }

    async function refreshUnread() {
        try {
            const data = await api('unread');
            setUnread(data.unread);
        } catch (e) {
            if (e.code === 'unauthenticated') clearTimer('unread');
        }
    }

    /* ------------------------------------------------------------------
     * Views
     * ------------------------------------------------------------------ */

    function showView(name) {
        state.view = name;
        root.querySelectorAll('.cc-view').forEach((v) => v.classList.toggle('is-active', v.dataset.view === name));
        if (name !== 'thread') {
            clearTimer('convo');
        }
        if (name === 'list') {
            setTimer('list', () => state.open && loadList(false), LIST_POLL_MS);
        } else {
            clearTimer('list');
        }
    }

    function open() {
        if (state.open) return;
        state.open = true;
        root.classList.add('is-open');
        root.querySelector('.cc-launcher').setAttribute('aria-expanded', 'true');
        if (state.view === 'list') {
            loadList(true);
            setTimer('list', () => loadList(false), LIST_POLL_MS);
            setTimeout(() => el.search.focus({ preventScroll: true }), 50);
        } else if (state.view === 'thread') {
            startConvoPolling();
        }
    }

    function close() {
        if (!state.open) return;
        state.open = false;
        root.classList.remove('is-open');
        root.querySelector('.cc-launcher').setAttribute('aria-expanded', 'false');
        clearTimer('convo');
        clearTimer('list');
        refreshUnread();
    }

    function back() {
        if (state.view === 'groupinfo') {
            showView('thread');
            startConvoPolling();
            return;
        }
        state.convo = null;
        showView('list');
        loadList(true);
    }

    /* ------------------------------------------------------------------
     * List (chats / groups / search)
     * ------------------------------------------------------------------ */

    function setTab(tab) {
        state.tab = tab;
        root.querySelectorAll('.cc-tab').forEach((t) => {
            const active = t.dataset.tab === tab;
            t.classList.toggle('is-active', active);
            t.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        el.search.value = '';
        el.search.placeholder = tab === 'chats' ? 'Search people…' : 'Filter groups…';
        loadList(true);
    }

    async function loadList(showLoading) {
        const query = el.search.value.trim();
        if (state.tab === 'chats' && query.length >= 2) return; // search results are showing
        if (showLoading && !el.list.dataset.loaded) el.list.innerHTML = skeleton();

        try {
            if (state.tab === 'chats') {
                const data = await api('conversations');
                state.conversations = data.conversations;
                setUnread(data.unread);
                if (state.tab === 'chats' && el.search.value.trim().length < 2) renderConversations();
            } else {
                const data = await api('groups');
                state.groups = data.groups;
                if (state.tab === 'groups') renderGroups();
            }
            el.list.dataset.loaded = '1';
        } catch (e) {
            if (showLoading) el.list.innerHTML = emptyState('Could not load messages', e.message);
        }
    }

    function renderConversations() {
        if (!state.conversations.length) {
            el.list.innerHTML = emptyState('No conversations yet', 'Search for a teacher, student or staff member above to start chatting.');
            return;
        }
        el.list.innerHTML = state.conversations.map((c, i) => `
            <button type="button" class="cc-item ${c.unread ? 'is-unread' : ''}" data-conv="${i}">
                ${avatar(c.name, c.partner_type)}
                <div class="cc-item-body">
                    <div class="cc-item-top">
                        <span class="cc-item-name">${esc(c.name)}</span>
                        <span class="cc-item-time">${esc(listTime(c.timestamp))}</span>
                    </div>
                    <div class="cc-item-bottom">
                        <span class="cc-item-preview">${c.last_from_me ? 'You: ' : ''}${esc(c.last_message)}</span>
                        ${c.unread ? `<span class="cc-unread">${c.unread > 99 ? '99+' : c.unread}</span>` : roleChip(c.partner_type)}
                    </div>
                </div>
            </button>
        `).join('');
    }

    function renderGroups() {
        const q = el.search.value.trim().toLowerCase();
        const groups = q ? state.groups.filter((g) => g.name.toLowerCase().includes(q)) : state.groups;

        if (!state.groups.length) {
            el.list.innerHTML = `
                ${emptyState('No groups yet', 'Create a group to message several people at once.')}
                <div style="text-align:center;margin-top:-28px"><button type="button" class="cc-btn cc-btn-primary" data-act="new-group">New group</button></div>`;
            return;
        }
        if (!groups.length) {
            el.list.innerHTML = emptyState('No matching groups', 'Try a different name.');
            return;
        }
        el.list.innerHTML = groups.map((g) => `
            <button type="button" class="cc-item" data-group="${g.id}">
                <div class="cc-avatar" data-type="group" aria-hidden="true">${esc(initials(g.name))}</div>
                <div class="cc-item-body">
                    <div class="cc-item-top">
                        <span class="cc-item-name">${esc(g.name)}</span>
                        <span class="cc-item-time">${esc(listTime(g.timestamp))}</span>
                    </div>
                    <div class="cc-item-bottom">
                        <span class="cc-item-preview">${g.last_message
                            ? (g.last_from_me ? 'You: ' : '') + esc(g.last_message)
                            : `${g.member_count} member${g.member_count === 1 ? '' : 's'}`}</span>
                    </div>
                </div>
            </button>
        `).join('');
    }

    async function searchPeople(query, target, onRender) {
        const seq = ++state.searchSeq;
        target.innerHTML = skeleton(3);
        try {
            const data = await api('search', { q: query });
            if (seq !== state.searchSeq) return;
            onRender(data.users);
        } catch (e) {
            if (seq === state.searchSeq) target.innerHTML = emptyState('Search failed', e.message);
        }
    }

    function renderSearchResults(users) {
        if (!users.length) {
            el.list.innerHTML = emptyState('No one found', 'Try a different name, email or student number.');
            return;
        }
        state.searchResults = users;
        el.list.innerHTML = '<div class="cc-section-label">People</div>' + users.map((u, i) => `
            <button type="button" class="cc-item" data-user="${i}">
                ${avatar(u.name, u.type)}
                <div class="cc-item-body">
                    <div class="cc-item-top"><span class="cc-item-name">${esc(u.name)}</span></div>
                    <div class="cc-item-bottom">${roleChip(u.type)}</div>
                </div>
            </button>
        `).join('');
    }

    function onSearchInput() {
        clearTimeout(state.timers.search);
        const q = el.search.value.trim();
        if (state.tab === 'groups') {
            renderGroups();
            return;
        }
        if (q.length < 2) {
            state.searchSeq++;
            renderConversations();
            return;
        }
        state.timers.search = setTimeout(() => searchPeople(q, el.list, renderSearchResults), 250);
    }

    /* ------------------------------------------------------------------
     * Conversation (direct + group)
     * ------------------------------------------------------------------ */

    function openThread(convo) {
        state.convo = convo;
        state.lastId = 0;
        state.rendered = new Set();
        state.lastRun = null;
        state.sending = 0;
        setLocked(false);

        el.tName.textContent = convo.name;
        el.tAvatar.dataset.type = convo.kind === 'group' ? 'group' : convo.type;
        el.tAvatar.innerHTML = esc(initials(convo.name));
        el.tSub.textContent = convo.kind === 'group'
            ? `${convo.memberCount || 0} member${convo.memberCount === 1 ? '' : 's'}`
            : (ROLE_LABEL[convo.type] || '');
        el.tInfo.hidden = convo.kind !== 'group';
        el.thread.innerHTML = skeleton(3);
        el.input.value = '';
        autosize();

        showView('thread');
        open();
        loadThread(true);
        startConvoPolling();
        setTimeout(() => el.input.focus({ preventScroll: true }), 60);
    }

    function startConvoPolling() {
        setTimer('convo', () => state.open && state.view === 'thread' && loadThread(false), CONVO_POLL_MS);
    }

    async function loadThread(initial) {
        const convo = state.convo;
        if (!convo || (!initial && state.sending > 0)) return;

        try {
            const data = convo.kind === 'group'
                ? await api('group_messages', { group_id: convo.id, after: state.lastId })
                : await api('messages', { partner_id: convo.id, partner_type: convo.type, after: state.lastId });
            if (state.convo !== convo) return;

            if (initial) {
                el.thread.innerHTML = '';
                if (!data.messages.length) {
                    el.thread.innerHTML = emptyState(
                        'No messages yet',
                        convo.kind === 'group' ? 'Say hello to the group.' : `Send ${convo.name.split(' ')[0]} a message to get started.`
                    );
                }
            }
            const nearBottom = el.thread.scrollHeight - el.thread.scrollTop - el.thread.clientHeight < 80;
            data.messages.forEach(appendMessage);
            if (convo.kind === 'dm') updateReceipt(data.receipts && data.receipts.last_read_id);
            if (initial || (data.messages.length && nearBottom)) scrollToEnd();
            if (initial && convo.kind === 'dm') refreshUnread();
        } catch (e) {
            if (initial) el.thread.innerHTML = emptyState('Could not load messages', e.message);
        }
    }

    function scrollToEnd() {
        el.thread.scrollTop = el.thread.scrollHeight;
    }

    function clearThreadEmpty() {
        const empty = el.thread.querySelector('.cc-empty, .cc-skeleton');
        if (empty) el.thread.innerHTML = '';
    }

    function appendMessage(msg, opts = {}) {
        if (state.rendered.has(String(msg.id))) return null;
        clearThreadEmpty();

        const date = toDate(msg.timestamp) || new Date();
        const day = dayKey(date);
        const sender = msg.mine ? 'me' : (msg.sender_type || '') + ':' + (msg.sender_name || 'them');
        const run = state.lastRun;

        if (!run || run.day !== day) {
            const sep = document.createElement('div');
            sep.className = 'cc-day';
            sep.textContent = dayLabel(date);
            el.thread.appendChild(sep);
        }

        const continued = run && run.day === day && run.sender === sender && date - run.ts < 5 * 60 * 1000;
        if (continued && run.el) run.el.querySelector('.cc-msg-meta').hidden = true;

        const row = document.createElement('div');
        row.className = `cc-msg ${msg.mine ? 'mine' : 'theirs'}${continued ? ' is-cont' : ''}${opts.pending ? ' is-pending' : ''}`;
        row.dataset.id = msg.id;
        row.dataset.time = clock(date);
        row.title = `${dayLabel(date)}, ${clock(date)}`;

        const showSender = state.convo && state.convo.kind === 'group' && !msg.mine && !continued;
        const canDelete = msg.mine && !opts.pending && state.convo && state.convo.kind === 'dm';

        row.innerHTML = `
            ${showSender ? `<div class="cc-msg-sender">${esc(msg.sender_name)}</div>` : ''}
            <div class="cc-bubble-row">
                <div class="cc-bubble">${linkify(msg.message)}</div>
                ${canDelete ? `<button type="button" class="cc-msg-delete" data-act="delete-msg" aria-label="Delete message">${ICON.trash}</button>` : ''}
            </div>
            <div class="cc-msg-meta">${opts.pending ? 'Sending…' : esc(clock(date))}</div>
        `;
        el.thread.appendChild(row);

        state.rendered.add(String(msg.id));
        if (!opts.pending && Number(msg.id) > state.lastId) state.lastId = Number(msg.id);
        state.lastRun = { day, sender, ts: date, el: row };
        return row;
    }

    function updateReceipt(lastReadId) {
        const mine = el.thread.querySelectorAll('.cc-msg.mine:not(.is-pending):not(.is-failed)');
        mine.forEach((m) => {
            const meta = m.querySelector('.cc-msg-meta');
            meta.textContent = m.dataset.time;
        });
        const last = mine[mine.length - 1];
        if (!last) return;
        const seen = lastReadId && Number(last.dataset.id) <= Number(lastReadId);
        const meta = last.querySelector('.cc-msg-meta');
        meta.hidden = false;
        meta.textContent = `${last.dataset.time} · ${seen ? 'Seen' : 'Sent'}`;
    }

    function setLocked(locked, placeholder) {
        state.locked = locked;
        el.input.disabled = locked;
        el.input.placeholder = locked ? (placeholder || 'You can’t reply to this conversation') : 'Write a message…';
        updateSendState();
    }

    function notice(text) {
        clearThreadEmpty();
        const n = document.createElement('div');
        n.className = 'cc-notice';
        n.textContent = text;
        el.thread.appendChild(n);
        scrollToEnd();
    }

    function updateSendState() {
        el.send.disabled = state.locked || !el.input.value.trim();
    }

    function autosize() {
        el.input.style.height = 'auto';
        el.input.style.height = Math.min(el.input.scrollHeight, 120) + 'px';
    }

    async function send(text) {
        const convo = state.convo;
        if (!convo || state.locked) return;
        text = text.trim();
        if (!text) return;

        const tempId = 'tmp-' + Date.now() + Math.random().toString(16).slice(2);
        const row = appendMessage({ id: tempId, message: text, mine: true, timestamp: new Date().toISOString() }, { pending: true });
        scrollToEnd();
        state.sending++;

        try {
            const data = convo.kind === 'group'
                ? await api('group_send', { group_id: convo.id, message: text }, 'POST')
                : await api('send', { partner_id: convo.id, partner_type: convo.type, message: text }, 'POST');
            if (state.convo !== convo) return;

            const real = data.message;
            if (state.rendered.has(String(real.id))) {
                row.remove();
            } else {
                row.classList.remove('is-pending');
                row.dataset.id = real.id;
                row.querySelector('.cc-msg-meta').textContent = row.dataset.time;
                if (convo.kind === 'dm') {
                    row.querySelector('.cc-bubble-row').insertAdjacentHTML('beforeend',
                        `<button type="button" class="cc-msg-delete" data-act="delete-msg" aria-label="Delete message">${ICON.trash}</button>`);
                }
                state.rendered.add(String(real.id));
                if (Number(real.id) > state.lastId) state.lastId = Number(real.id);
            }
            if (convo.kind === 'dm') updateReceipt(null);
        } catch (e) {
            if (state.convo !== convo) return;
            row.classList.remove('is-pending');
            row.classList.add('is-failed');
            const meta = row.querySelector('.cc-msg-meta');
            meta.hidden = false;

            if (e.code === 'receiver_not_found' || e.code === 'sender_not_found') {
                meta.textContent = 'Not sent';
                notice(e.message);
                setLocked(true, e.code === 'sender_not_found' ? 'Please log in again' : 'This contact no longer exists');
                clearTimer('convo');
            } else {
                meta.innerHTML = `${esc(e.message)} · <button type="button" data-act="retry" style="text-decoration:underline;font-weight:700">Retry</button>`;
                row.dataset.text = text;
            }
        } finally {
            state.sending = Math.max(0, state.sending - 1);
        }
    }

    async function deleteMessage(btn) {
        const row = btn.closest('.cc-msg');
        if (!btn.classList.contains('is-confirm')) {
            btn.classList.add('is-confirm');
            btn.textContent = 'Delete?';
            btn.setAttribute('aria-label', 'Confirm delete');
            setTimeout(() => {
                if (btn.isConnected && btn.classList.contains('is-confirm')) {
                    btn.classList.remove('is-confirm');
                    btn.innerHTML = ICON.trash;
                    btn.setAttribute('aria-label', 'Delete message');
                }
            }, 3000);
            return;
        }
        btn.disabled = true;
        try {
            await api('delete', { id: row.dataset.id }, 'POST');
            row.remove();
            if (state.lastRun && state.lastRun.el === row) state.lastRun = null;
            if (!el.thread.querySelector('.cc-msg')) {
                el.thread.innerHTML = emptyState('No messages yet', 'This conversation is empty.');
            } else {
                updateReceipt(null);
            }
        } catch (e) {
            btn.disabled = false;
            notice(e.message);
        }
    }

    /* ------------------------------------------------------------------
     * Groups: create + info
     * ------------------------------------------------------------------ */

    function startNewGroup() {
        state.selected.clear();
        el.gName.value = '';
        el.gDesc.value = '';
        el.gSearch.value = '';
        el.gError.hidden = true;
        el.gResults.innerHTML = emptyState('Add members', 'Search for people by name, email or student number.');
        renderChips();
        showView('newgroup');
        open();
        setTimeout(() => el.gName.focus({ preventScroll: true }), 60);
    }

    function renderChips() {
        el.gChips.innerHTML = [...state.selected.values()].map((u) => `
            <button type="button" class="cc-chip" data-unselect="${esc(u.type + ':' + u.id)}" aria-label="Remove ${esc(u.name)}">
                ${esc(u.name)} ${ICON.x}
            </button>
        `).join('');
        el.gCreate.disabled = !el.gName.value.trim() || state.selected.size === 0;
        el.gCreate.textContent = state.selected.size ? `Create group (${state.selected.size + 1})` : 'Create group';
        el.gResults.querySelectorAll('[data-pick]').forEach((b) => b.classList.toggle('is-selected', state.selected.has(b.dataset.pick)));
    }

    function renderMemberResults(users) {
        state.memberResults = users;
        if (!users.length) {
            el.gResults.innerHTML = emptyState('No one found', 'Try a different name.');
            return;
        }
        el.gResults.innerHTML = users.map((u) => {
            const key = u.type + ':' + u.id;
            return `
                <button type="button" class="cc-item ${state.selected.has(key) ? 'is-selected' : ''}" data-pick="${esc(key)}">
                    ${avatar(u.name, u.type)}
                    <div class="cc-item-body">
                        <div class="cc-item-top"><span class="cc-item-name">${esc(u.name)}</span></div>
                        <div class="cc-item-bottom">${roleChip(u.type)}</div>
                    </div>
                    <span class="cc-item-check">${ICON.check}</span>
                </button>`;
        }).join('');
    }

    async function createGroup() {
        el.gError.hidden = true;
        el.gCreate.disabled = true;
        const members = [...state.selected.values()].map((u) => ({ id: u.id, type: u.type }));
        try {
            const data = await api('group_create', {
                name: el.gName.value.trim(),
                description: el.gDesc.value.trim(),
                members: JSON.stringify(members),
            }, 'POST');
            state.tab = 'groups';
            root.querySelectorAll('.cc-tab').forEach((t) => t.classList.toggle('is-active', t.dataset.tab === 'groups'));
            openThread({ kind: 'group', id: data.group_id, name: el.gName.value.trim(), memberCount: members.length + 1 });
        } catch (e) {
            el.gError.textContent = e.message;
            el.gError.hidden = false;
            el.gCreate.disabled = false;
        }
    }

    async function showGroupInfo() {
        const convo = state.convo;
        if (!convo || convo.kind !== 'group') return;
        el.iName.textContent = convo.name;
        el.iSub.textContent = '';
        el.iDesc.textContent = '';
        el.iDesc.hidden = true;
        el.iMembers.innerHTML = skeleton(3);
        el.iLeave.textContent = 'Leave group';
        el.iLeave.dataset.confirm = '';
        showView('groupinfo');

        try {
            const { group } = await api('group_info', { group_id: convo.id });
            el.iName.textContent = group.name;
            el.iSub.textContent = `${group.members.length} member${group.members.length === 1 ? '' : 's'}`;
            el.iDesc.textContent = group.description || '';
            el.iDesc.hidden = !group.description;
            el.iMembers.innerHTML = group.members.map((m) => `
                <div class="cc-item" style="cursor:default">
                    ${avatar(m.name, m.type)}
                    <div class="cc-item-body">
                        <div class="cc-item-top"><span class="cc-item-name">${esc(m.name)}${m.me ? ' <span style="color:var(--cc-muted);font-weight:400">(you)</span>' : ''}</span></div>
                        <div class="cc-item-bottom">${roleChip(m.type)}</div>
                    </div>
                </div>
            `).join('');
        } catch (e) {
            el.iMembers.innerHTML = emptyState('Could not load group', e.message);
        }
    }

    async function leaveGroup() {
        const convo = state.convo;
        if (!convo) return;
        if (!el.iLeave.dataset.confirm) {
            el.iLeave.dataset.confirm = '1';
            el.iLeave.textContent = 'Click again to leave';
            return;
        }
        el.iLeave.disabled = true;
        try {
            await api('group_leave', { group_id: convo.id }, 'POST');
            state.convo = null;
            state.tab = 'groups';
            showView('list');
            setTab('groups');
        } catch (e) {
            el.iLeave.textContent = e.message;
        } finally {
            el.iLeave.disabled = false;
        }
    }

    /* ------------------------------------------------------------------
     * Events
     * ------------------------------------------------------------------ */

    root.addEventListener('click', (event) => {
        const target = event.target;
        const act = target.closest('[data-act]');

        if (act && root.contains(act)) {
            switch (act.dataset.act) {
                case 'toggle': state.open ? close() : open(); return;
                case 'close': close(); return;
                case 'back': back(); return;
                case 'new-group': startNewGroup(); return;
                case 'create-group': createGroup(); return;
                case 'group-info': showGroupInfo(); return;
                case 'leave-group': leaveGroup(); return;
                case 'delete-msg': deleteMessage(act); return;
                case 'retry': {
                    const row = act.closest('.cc-msg');
                    const text = row.dataset.text;
                    row.remove();
                    send(text);
                    return;
                }
            }
        }

        const tab = target.closest('[data-tab]');
        if (tab) { setTab(tab.dataset.tab); return; }

        const conv = target.closest('[data-conv]');
        if (conv) {
            const c = state.conversations[Number(conv.dataset.conv)];
            if (c) openThread({ kind: 'dm', id: c.partner_id, type: c.partner_type, name: c.name });
            return;
        }

        const user = target.closest('[data-user]');
        if (user) {
            const u = state.searchResults[Number(user.dataset.user)];
            if (u) {
                el.search.value = '';
                openThread({ kind: 'dm', id: u.id, type: u.type, name: u.name });
            }
            return;
        }

        const group = target.closest('[data-group]');
        if (group) {
            const g = state.groups.find((x) => String(x.id) === group.dataset.group);
            if (g) openThread({ kind: 'group', id: g.id, name: g.name, memberCount: g.member_count });
            return;
        }

        const pick = target.closest('[data-pick]');
        if (pick) {
            const key = pick.dataset.pick;
            if (state.selected.has(key)) {
                state.selected.delete(key);
            } else {
                const u = (state.memberResults || []).find((x) => x.type + ':' + x.id === key);
                if (u) state.selected.set(key, u);
            }
            renderChips();
            return;
        }

        const unselect = target.closest('[data-unselect]');
        if (unselect) {
            state.selected.delete(unselect.dataset.unselect);
            renderChips();
        }
    });

    el.search.addEventListener('input', onSearchInput);

    el.gSearch.addEventListener('input', () => {
        clearTimeout(state.timers.gsearch);
        const q = el.gSearch.value.trim();
        if (q.length < 2) {
            state.searchSeq++;
            el.gResults.innerHTML = emptyState('Add members', 'Search for people by name, email or student number.');
            return;
        }
        state.timers.gsearch = setTimeout(() => searchPeople(q, el.gResults, renderMemberResults), 250);
    });

    el.gName.addEventListener('input', renderChips);

    el.composer.addEventListener('submit', (event) => {
        event.preventDefault();
        const text = el.input.value;
        if (!text.trim() || state.locked) return;
        el.input.value = '';
        autosize();
        updateSendState();
        send(text);
        el.input.focus();
    });

    el.input.addEventListener('input', () => {
        autosize();
        updateSendState();
    });

    el.input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
            event.preventDefault();
            el.composer.requestSubmit();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || !state.open) return;
        if (state.view === 'list') close(); else back();
    });

    // Clicking outside closes the list view (but never an open conversation).
    document.addEventListener('click', (event) => {
        if (!state.open || state.view !== 'list') return;
        if (root.contains(event.target) || event.target.closest('[data-csms-chat-open]')) return;
        close();
    });

    // External openers, e.g. the message icon in each portal's top bar.
    document.addEventListener('click', (event) => {
        const opener = event.target.closest('[data-csms-chat-open]');
        if (!opener) return;
        event.preventDefault();
        if (state.open) close(); else open();
    });

    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) refreshUnread();
    });

    window.CSMSChat = {
        open,
        close,
        openConversation: (id, type, name) => openThread({ kind: 'dm', id: Number(id), type, name: name || 'Conversation' }),
    };

    refreshUnread();
    setTimer('unread', refreshUnread, UNREAD_POLL_MS);
})();
