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
    const FILE_URL = API.replace(/api\.php(\?.*)?$/, 'file.php');

    // Attachments: keep in step with app/helpers/ChatAttachments.php
    const MAX_FILE_BYTES = 25 * 1024 * 1024;
    const MAX_FILES = 10;
    const IMAGE_MAX_EDGE = 1920;
    const FILE_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp', 'rtf', 'txt', 'csv', 'md',
        'zip', 'rar', '7z', 'jpg', 'jpeg', 'png', 'webp', 'gif', 'heic', 'heif', 'bmp',
        'mp3', 'm4a', 'wav', 'ogg', 'mp4', 'mov', 'webm'];
    // Photos the browser re-encodes before upload (GIFs are left alone to keep animation)
    const COMPRESSIBLE = /^image\/(jpeg|png|webp|bmp|heic|heif)$/i;

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
        clip: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m21.4 11.1-9.2 9.2a6 6 0 0 1-8.5-8.5l9.2-9.2a4 4 0 0 1 5.7 5.7l-9.2 9.2a2 2 0 0 1-2.8-2.8l8.5-8.5"/></svg>',
        download: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12M7 10l5 5 5-5M5 21h14"/></svg>',
        file: '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>',
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
                <div class="cc-drop" data-el="drop" aria-hidden="true">${ICON.clip}<span>Drop files or photos to send</span></div>
                <div class="cc-tray" data-el="tray" hidden></div>
                <form class="cc-composer" data-el="composer">
                    <button type="button" class="cc-attach" data-act="attach" data-el="attach" aria-label="Attach photos or files" title="Attach photos or files">${ICON.clip}</button>
                    <input type="file" data-el="file" multiple hidden
                        accept="image/*,${FILE_EXTENSIONS.map((e) => '.' + e).join(',')}">
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

        <div class="cc-lightbox" data-el="lightbox" role="dialog" aria-label="Photo" hidden>
            <div class="cc-lightbox-bar">
                <span class="cc-lightbox-name" data-el="lb-name"></span>
                <a class="cc-icon-btn" data-el="lb-download" aria-label="Download photo" title="Download">${ICON.download}</a>
                <button type="button" class="cc-icon-btn" data-act="lightbox-close" aria-label="Close photo">${ICON.close}</button>
            </div>
            <img data-el="lb-img" alt="">
        </div>
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
        attach: $('attach'),
        file: $('file'),
        tray: $('tray'),
        drop: $('drop'),
        lightbox: $('lightbox'),
        lbName: $('lb-name'),
        lbImg: $('lb-img'),
        lbDownload: $('lb-download'),
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
        attachments: [],        // files waiting in the composer tray
        queue: Promise.resolve(), // keeps uploads in the order they were sent
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

    // POST with one file, reporting upload progress (fetch can't).
    function apiUpload(action, params, file, onProgress) {
        return new Promise((resolve, reject) => {
            const body = new FormData();
            body.append('action', action);
            Object.entries(params).forEach(([k, v]) => body.append(k, v));
            body.append('file', file, file.name);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', API);
            xhr.withCredentials = true;
            xhr.setRequestHeader('X-Requested-With', 'CSMSChat');
            xhr.upload.onprogress = (e) => { if (e.lengthComputable && onProgress) onProgress(e.loaded / e.total); };
            xhr.onerror = () => reject(new ChatError('Network error. Check your connection.', 'network'));
            xhr.onload = () => {
                let data;
                try {
                    data = JSON.parse(xhr.responseText);
                } catch (e) {
                    reject(new ChatError(xhr.status === 413 ? 'That file is too large.' : 'Unexpected response from the server.', 'bad_response'));
                    return;
                }
                if (!data.ok) reject(new ChatError(data.error || 'Something went wrong.', data.code));
                else resolve(data);
            };
            xhr.send(body);
        });
    }

    function esc(value) {
        return String(value ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    // Escape text and turn links into safe, clickable ones:
    //   emails                         -> mailto:
    //   http(s)://… and www.…          -> as typed (www. gets https://)
    //   bare domains (facebook.com/x)  -> https://facebook.com/x
    const TLDS = 'com|net|org|edu|gov|mil|ph|io|co|me|info|biz|app|dev|ai|ly|gl|gg|tv|xyz|site|online|store|tech|us|uk|ca|au|jp|sg|link|page|to|so|news|school|academy';
    const LINK_RE = new RegExp(
        '([a-z0-9._%+-]+@[a-z0-9-]+(?:\\.[a-z0-9-]+)*\\.[a-z]{2,})'                      // 1: email
        + '|((?:https?:\\/\\/|www\\.)[^\\s<>"\']+)'                                       // 2: full URL
        + '|((?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\\.)+(?:' + TLDS + ')(?![a-z0-9-])(?::\\d{2,5})?(?:[/?#][^\\s<>"\']*)?)', // 3: bare domain
        'gi'
    );
    function linkify(text) {
        const str = String(text ?? '');
        let html = '';
        let last = 0;
        for (const match of str.matchAll(LINK_RE)) {
            // A bare domain glued to a word (e.g. "abc_def.com" inside text) or to an @ is not a link
            const before = str[match.index - 1] || '';
            if (match[3] && /[@\w.\/-]/.test(before)) continue;

            let link = match[0];
            // Leave trailing punctuation (and unbalanced closing brackets) outside the link.
            while (/[.,!?;:'"]$/.test(link) || (/\)$/.test(link) && (link.match(/\(/g) || []).length < (link.match(/\)/g) || []).length)) {
                link = link.slice(0, -1);
            }
            const href = match[1] ? 'mailto:' + link : (/^https?:\/\//i.test(link) ? link : 'https://' + link);
            html += esc(str.slice(last, match.index));
            html += `<a href="${esc(href)}"${match[1] ? '' : ' target="_blank"'} rel="noopener noreferrer nofollow">${esc(link)}</a>`;
            last = match.index + link.length;
        }
        return html + esc(str.slice(last));
    }

    function fmtBytes(n) {
        if (n < 1024) return n + ' B';
        if (n < 1024 * 1024) return Math.round(n / 1024) + ' KB';
        return (n / 1024 / 1024).toFixed(n < 10 * 1024 * 1024 ? 1 : 0) + ' MB';
    }

    const extOf = (name) => (String(name).match(/\.([a-z0-9]+)$/i) || [, ''])[1].toLowerCase();
    const fileUrl = (id, extra = '') => `${FILE_URL}?id=${encodeURIComponent(id)}${extra}`;

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

    const isGone = (status) => status === 'deleted';
    const avatar = (name, type, status) => isGone(status)
        ? `<div class="cc-avatar" data-type="deleted" aria-hidden="true">?</div>`
        : `<div class="cc-avatar" data-type="${esc(type)}" aria-hidden="true">${esc(initials(name))}</div>`;
    const roleChip = (type) => ROLE_LABEL[type] ? `<span class="cc-role" data-type="${esc(type)}">${ROLE_LABEL[type]}</span>` : '';
    // Deactivated / deleted accounts get a status chip instead of their role
    const STATUS_LABEL = { deactivated: 'Deactivated', deleted: 'Deleted' };
    const personChip = (type, status) => STATUS_LABEL[status]
        ? `<span class="cc-role" data-status="${esc(status)}">${STATUS_LABEL[status]}</span>`
        : roleChip(type);
    const nameClass = (status) => STATUS_LABEL[status] ? ` is-${status}` : '';

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
                ${avatar(c.name, c.partner_type, c.account_status)}
                <div class="cc-item-body">
                    <div class="cc-item-top">
                        <span class="cc-item-name${nameClass(c.account_status)}">${esc(c.name)}</span>
                        <span class="cc-item-time">${esc(listTime(c.timestamp))}</span>
                    </div>
                    <div class="cc-item-bottom">
                        <span class="cc-item-preview">${c.last_from_me ? 'You: ' : ''}${esc(c.last_message)}</span>
                        ${c.unread ? `<span class="cc-unread">${c.unread > 99 ? '99+' : c.unread}</span>` : personChip(c.partner_type, c.account_status)}
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
                ${avatar(u.name, u.type, u.account_status)}
                <div class="cc-item-body">
                    <div class="cc-item-top"><span class="cc-item-name${nameClass(u.account_status)}">${esc(u.name)}</span></div>
                    <div class="cc-item-bottom">${personChip(u.type, u.account_status)}</div>
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

        el.tAvatar.dataset.type = convo.kind === 'group' ? 'group' : convo.type;
        el.tInfo.hidden = convo.kind !== 'group';
        applyThreadHeader(convo);
        el.thread.innerHTML = skeleton(3);
        el.input.value = '';
        clearTray();
        autosize();

        showView('thread');
        open();
        loadThread(true);
        startConvoPolling();
        setTimeout(() => el.input.focus({ preventScroll: true }), 60);
    }

    /** Name, avatar and subtitle of the open conversation; deleted contacts can't be messaged. */
    function applyThreadHeader(convo) {
        const status = convo.accountStatus || 'active';
        el.tName.textContent = convo.name;
        el.tName.className = 'cc-title' + nameClass(status);
        if (convo.kind === 'group') {
            el.tAvatar.innerHTML = esc(initials(convo.name));
            el.tSub.textContent = `${convo.memberCount || 0} member${convo.memberCount === 1 ? '' : 's'}`;
            return;
        }
        el.tAvatar.dataset.type = isGone(status) ? 'deleted' : convo.type;
        el.tAvatar.innerHTML = isGone(status) ? '?' : esc(initials(convo.name));
        el.tSub.textContent = status === 'deleted'
            ? 'This account no longer exists'
            : status === 'deactivated'
                ? `${ROLE_LABEL[convo.type] || 'Account'} · Deactivated`
                : (ROLE_LABEL[convo.type] || '');
        if (isGone(status)) {
            setLocked(true, 'This account has been deleted');
            clearTray();
        } else if (state.locked && convo.lockedFor === 'deleted') {
            setLocked(false);
        }
        convo.lockedFor = isGone(status) ? 'deleted' : null;
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
                        convo.kind === 'group' ? 'Say hello to the group.'
                            : isGone(convo.accountStatus) ? 'This account has been deleted.'
                            : `Send ${convo.name.split(' ')[0]} a message to get started.`
                    );
                }
            }
            const nearBottom = el.thread.scrollHeight - el.thread.scrollTop - el.thread.clientHeight < 80;
            data.messages.forEach(appendMessage);
            if (convo.kind === 'dm') updateReceipt(data.receipts && data.receipts.last_read_id);
            // The account may have been renamed, deactivated or deleted since the list loaded
            if (convo.kind === 'dm' && data.partner
                && (data.partner.status !== (convo.accountStatus || 'active') || data.partner.name !== convo.name)) {
                convo.accountStatus = data.partner.status;
                convo.name = data.partner.name;
                applyThreadHeader(convo);
            }
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

        const att = msg.attachment;
        const bubbleClass = att ? (att.kind === 'image' ? ' has-image' : ' has-file') : '';
        row.innerHTML = `
            ${showSender ? `<div class="cc-msg-sender">${esc(msg.sender_name)}</div>` : ''}
            <div class="cc-bubble-row">
                <div class="cc-bubble${bubbleClass}">${att ? attachmentHtml(att, opts.preview) : ''}${msg.message ? `<div class="cc-text">${linkify(msg.message)}</div>` : ''}</div>
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

    /**
     * Photo or file card inside a bubble. att comes from the server ({id, kind, name, size, width, height})
     * or, while uploading, from the composer (no id yet; preview is a local object URL).
     */
    function attachmentHtml(att, preview) {
        if (att.kind === 'image') {
            const w = att.width || 4;
            const h = att.height || 3;
            const src = preview || fileUrl(att.id, '&size=thumb');
            const ratio = `aspect-ratio:${w}/${h}`;
            const img = `<img src="${esc(src)}" alt="${esc(att.name)}" width="${w}" height="${h}" loading="lazy" decoding="async" style="${ratio}">`;
            return att.id
                ? `<a class="cc-photo" href="${esc(fileUrl(att.id))}" data-act="view-photo" data-full="${esc(fileUrl(att.id))}" data-name="${esc(att.name)}" data-download="${esc(fileUrl(att.id, '&download=1'))}">${img}</a>`
                : `<span class="cc-photo">${img}</span>`;
        }
        const ext = extOf(att.name);
        const inner = `
            <span class="cc-file-icon">${ICON.file}<b>${esc(ext.slice(0, 4) || 'file')}</b></span>
            <span class="cc-file-body">
                <span class="cc-file-name">${esc(att.name)}</span>
                <span class="cc-file-size">${esc(fmtBytes(att.size || 0))}</span>
            </span>
            ${att.id ? `<span class="cc-file-dl">${ICON.download}</span>` : ''}`;
        return att.id
            ? `<a class="cc-file" href="${esc(fileUrl(att.id, '&download=1'))}" download="${esc(att.name)}" title="Download ${esc(att.name)}">${inner}</a>`
            : `<span class="cc-file">${inner}</span>`;
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
        el.attach.disabled = locked;
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
        const files = state.attachments;
        const busy = files.some((a) => a.status === 'processing');
        const ready = files.some((a) => a.status === 'ready');
        el.send.disabled = state.locked || busy || (!el.input.value.trim() && !ready);
        el.send.setAttribute('aria-label', busy ? 'Optimizing attachments…' : 'Send');
    }

    /* ------------------------------------------------------------------
     * Attachments: pick / paste / drop -> optimise -> tray -> send
     * ------------------------------------------------------------------ */

    function loadImageElement(file) {
        return new Promise((resolve, reject) => {
            const url = URL.createObjectURL(file);
            const img = new Image();
            img.onload = () => { URL.revokeObjectURL(url); resolve(img); };
            img.onerror = () => { URL.revokeObjectURL(url); reject(new Error('decode')); };
            img.src = url;
        });
    }

    const canvasBlob = (canvas, type, quality) => new Promise((resolve) => canvas.toBlob(resolve, type, quality));

    /**
     * Shrinks photos before upload: fits them inside IMAGE_MAX_EDGE, applies the camera
     * rotation, drops metadata (GPS etc.) and re-encodes as WebP (JPEG where WebP
     * isn't supported). Keeps the original when that is already smaller.
     * Returns { file, width, height } or null when the browser can't read the image.
     */
    async function compressImage(file) {
        let source;
        try {
            source = await createImageBitmap(file, { imageOrientation: 'from-image' });
        } catch (e) {
            try {
                source = await loadImageElement(file);
            } catch (e2) {
                return null;
            }
        }
        const w0 = source.width || source.naturalWidth;
        const h0 = source.height || source.naturalHeight;
        if (!w0 || !h0) return null;

        const scale = Math.min(1, IMAGE_MAX_EDGE / Math.max(w0, h0));
        const w = Math.max(1, Math.round(w0 * scale));
        const h = Math.max(1, Math.round(h0 * scale));
        const canvas = document.createElement('canvas');
        canvas.width = w;
        canvas.height = h;
        const ctx = canvas.getContext('2d');
        ctx.imageSmoothingQuality = 'high';
        ctx.drawImage(source, 0, 0, w, h);
        if (source.close) source.close();

        let blob = await canvasBlob(canvas, 'image/webp', 0.82);
        if (!blob || blob.type !== 'image/webp') {
            // No WebP encoder (older Safari): JPEG has no transparency, so paint white behind it
            ctx.globalCompositeOperation = 'destination-over';
            ctx.fillStyle = '#fff';
            ctx.fillRect(0, 0, w, h);
            blob = await canvasBlob(canvas, 'image/jpeg', 0.85);
        }
        if (!blob) return null;

        const browserCanShow = /^image\/(jpeg|png|webp)$/i.test(file.type);
        if (blob.size >= file.size && scale === 1 && browserCanShow) {
            return { file, width: w, height: h };
        }
        const ext = blob.type === 'image/webp' ? 'webp' : 'jpg';
        const stem = file.name.replace(/\.[^.]+$/, '') || 'photo';
        return { file: new File([blob], `${stem}.${ext}`, { type: blob.type }), width: w, height: h };
    }

    async function prepareAttachment(entry) {
        const original = entry.original;
        try {
            if (COMPRESSIBLE.test(original.type)) {
                const out = await compressImage(original);
                if (out) {
                    Object.assign(entry, { file: out.file, kind: 'image', width: out.width, height: out.height });
                }
            } else if (original.type === 'image/gif') {
                const img = await loadImageElement(original).catch(() => null);
                Object.assign(entry, { kind: 'image', width: img ? img.naturalWidth : null, height: img ? img.naturalHeight : null });
            }
            if (!entry.kind) {
                entry.kind = 'file';
                if (!FILE_EXTENSIONS.includes(extOf(original.name))) {
                    throw new Error('This type of file can’t be sent.');
                }
            }
            if (entry.file.size > MAX_FILE_BYTES) {
                throw new Error(`Too large (max ${fmtBytes(MAX_FILE_BYTES)})`);
            }
            if (entry.kind === 'image') entry.preview = URL.createObjectURL(entry.file);
            entry.status = 'ready';
        } catch (e) {
            entry.status = 'error';
            entry.error = e.message || 'Could not prepare this file.';
        }
        renderTray();
        updateSendState();
    }

    function addFiles(list) {
        if (state.locked || !state.convo) return;
        const files = Array.from(list || []).filter((f) => f && f.size >= 0);
        if (!files.length) return;
        const room = MAX_FILES - state.attachments.length;
        if (room <= 0) {
            notice(`You can send up to ${MAX_FILES} files at a time.`);
            return;
        }
        if (files.length > room) notice(`Only the first ${room} file${room === 1 ? '' : 's'} were added (max ${MAX_FILES} at a time).`);

        files.slice(0, room).forEach((original) => {
            const entry = {
                key: Math.random().toString(36).slice(2),
                original,
                file: original,
                name: original.name || 'file',
                kind: null,
                status: 'processing',
            };
            state.attachments.push(entry);
            prepareAttachment(entry);
        });
        renderTray();
        updateSendState();
    }

    function removeAttachment(key, revoke = true) {
        const i = state.attachments.findIndex((a) => a.key === key);
        if (i < 0) return;
        const [entry] = state.attachments.splice(i, 1);
        if (revoke && entry.preview) URL.revokeObjectURL(entry.preview);
        renderTray();
        updateSendState();
    }

    function clearTray(revoke = true) {
        if (revoke) state.attachments.forEach((a) => a.preview && URL.revokeObjectURL(a.preview));
        state.attachments = [];
        renderTray();
        updateSendState();
    }

    function renderTray() {
        const items = state.attachments;
        el.tray.hidden = items.length === 0;
        el.tray.innerHTML = items.map((a) => {
            const thumb = a.preview
                ? `<img src="${esc(a.preview)}" alt="">`
                : `<span class="cc-file-icon">${ICON.file}<b>${esc(extOf(a.name).slice(0, 4) || 'file')}</b></span>`;
            let meta;
            if (a.status === 'processing') meta = a.kind === 'file' ? 'Preparing…' : 'Optimizing…';
            else if (a.status === 'error') meta = a.error;
            else if (a.file !== a.original && a.file.size < a.original.size) meta = `${fmtBytes(a.original.size)} → ${fmtBytes(a.file.size)}`;
            else meta = fmtBytes(a.file.size);
            return `
                <div class="cc-tray-item is-${a.status}" title="${esc(a.name)}">
                    <span class="cc-tray-thumb">${thumb}</span>
                    <span class="cc-tray-body">
                        <span class="cc-tray-name">${esc(a.file.name || a.name)}</span>
                        <span class="cc-tray-meta">${esc(meta)}</span>
                    </span>
                    <button type="button" class="cc-tray-remove" data-remove-attachment="${a.key}" aria-label="Remove ${esc(a.name)}">${ICON.x}</button>
                </div>`;
        }).join('');
    }

    function openLightbox(link) {
        el.lbImg.src = link.dataset.full;
        el.lbImg.alt = link.dataset.name || '';
        el.lbName.textContent = link.dataset.name || '';
        el.lbDownload.href = link.dataset.download;
        el.lbDownload.setAttribute('download', link.dataset.name || '');
        el.lightbox.hidden = false;
        el.lightbox.querySelector('[data-act="lightbox-close"]').focus();
    }

    function closeLightbox() {
        el.lightbox.hidden = true;
        el.lbImg.removeAttribute('src');
    }

    function autosize() {
        el.input.style.height = 'auto';
        el.input.style.height = Math.min(el.input.scrollHeight, 120) + 'px';
    }

    /**
     * Sends what's in the composer: the text on its own, or each attachment as its own
     * message with the text as the first one's caption. Uploads go one at a time, in order.
     */
    function sendComposer(text) {
        const ready = state.attachments.filter((a) => a.status === 'ready');
        if (!ready.length) {
            send(text);
            return;
        }
        // Rejected files stay in the tray so the error remains visible; sent ones leave it.
        ready.forEach((a) => removeAttachment(a.key, false));
        ready.forEach((entry, i) => {
            const caption = i === 0 ? text : '';
            const job = send(caption, entry, true);
            state.queue = state.queue.then(() => job.run()).catch(() => {});
        });
    }

    /**
     * Shows the message straight away (pending) and sends it. With deferred=true the upload
     * waits until the returned job's run() is called, so queued files keep their order.
     */
    function send(text, entry = null, deferred = false) {
        const convo = state.convo;
        if (!convo || state.locked) return { run: () => Promise.resolve() };
        text = String(text || '').trim();
        if (!text && !entry) return { run: () => Promise.resolve() };

        const tempId = 'tmp-' + Date.now() + Math.random().toString(16).slice(2);
        const localAtt = entry ? { kind: entry.kind, name: entry.file.name, size: entry.file.size, width: entry.width, height: entry.height } : null;
        const row = appendMessage(
            { id: tempId, message: text, mine: true, timestamp: new Date().toISOString(), attachment: localAtt },
            { pending: true, preview: entry && entry.preview }
        );
        scrollToEnd();
        state.sending++;
        const meta = row.querySelector('.cc-msg-meta');
        if (entry) meta.textContent = 'Waiting…';

        const run = () => deliver(convo, row, text, entry, meta);
        if (deferred) return { run };
        run();
        return { run: () => Promise.resolve() };
    }

    async function deliver(convo, row, text, entry, meta) {
        try {
            const params = convo.kind === 'group'
                ? { group_id: convo.id, message: text }
                : { partner_id: convo.id, partner_type: convo.type, message: text };
            const action = convo.kind === 'group' ? 'group_send' : 'send';
            let data;
            if (entry) {
                meta.textContent = 'Uploading… 0%';
                data = await apiUpload(action, params, entry.file, (p) => {
                    meta.textContent = p < 1 ? `Uploading… ${Math.round(p * 100)}%` : 'Processing…';
                });
            } else {
                data = await api(action, params, 'POST');
            }
            if (state.convo !== convo) return;

            const real = data.message;
            if (state.rendered.has(String(real.id))) {
                row.remove();
            } else {
                row.classList.remove('is-pending');
                row.dataset.id = real.id;
                meta.textContent = row.dataset.time;
                if (real.attachment) {
                    // Swap the local preview for the optimised copy from the server
                    const bubble = row.querySelector('.cc-bubble');
                    const textEl = bubble.querySelector('.cc-text');
                    bubble.innerHTML = attachmentHtml(real.attachment) + (textEl ? textEl.outerHTML : '');
                    if (entry && entry.preview) setTimeout(() => URL.revokeObjectURL(entry.preview), 1000);
                }
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
            meta.hidden = false;

            if (e.code === 'receiver_not_found' || e.code === 'sender_not_found') {
                meta.textContent = 'Not sent';
                notice(e.message);
                setLocked(true, e.code === 'sender_not_found' ? 'Please log in again' : 'This contact no longer exists');
                clearTimer('convo');
            } else {
                const canRetry = !['file_type', 'file_too_large', 'bad_image', 'empty_file'].includes(e.code);
                meta.innerHTML = esc(e.message) + (canRetry ? ' · <button type="button" data-act="retry" style="text-decoration:underline;font-weight:700">Retry</button>' : '');
                row.retrySend = () => send(text, entry);
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
                    ${avatar(m.name, m.type, m.account_status)}
                    <div class="cc-item-body">
                        <div class="cc-item-top"><span class="cc-item-name${nameClass(m.account_status)}">${esc(m.name)}${m.me ? ' <span style="color:var(--cc-muted);font-weight:400">(you)</span>' : ''}</span></div>
                        <div class="cc-item-bottom">${personChip(m.type, m.account_status)}</div>
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
                    const retry = row.retrySend;
                    row.remove();
                    if (retry) retry();
                    return;
                }
                case 'attach': el.file.click(); return;
                case 'view-photo':
                    event.preventDefault();
                    openLightbox(act);
                    return;
                case 'lightbox-close': closeLightbox(); return;
            }
        }

        if (target === el.lightbox) { closeLightbox(); return; }

        const removeBtn = target.closest('[data-remove-attachment]');
        if (removeBtn) { removeAttachment(removeBtn.dataset.removeAttachment); return; }

        const tab = target.closest('[data-tab]');
        if (tab) { setTab(tab.dataset.tab); return; }

        const conv = target.closest('[data-conv]');
        if (conv) {
            const c = state.conversations[Number(conv.dataset.conv)];
            if (c) openThread({ kind: 'dm', id: c.partner_id, type: c.partner_type, name: c.name, accountStatus: c.account_status });
            return;
        }

        const user = target.closest('[data-user]');
        if (user) {
            const u = state.searchResults[Number(user.dataset.user)];
            if (u) {
                el.search.value = '';
                openThread({ kind: 'dm', id: u.id, type: u.type, name: u.name, accountStatus: u.account_status });
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
        if (el.send.disabled) return;
        const text = el.input.value;
        el.input.value = '';
        autosize();
        sendComposer(text);
        updateSendState();
        el.input.focus();
    });

    el.file.addEventListener('change', () => {
        addFiles(el.file.files);
        el.file.value = '';
    });

    // Paste screenshots / copied files straight into the message box
    el.input.addEventListener('paste', (event) => {
        const files = event.clipboardData && Array.from(event.clipboardData.files || []);
        if (files && files.length) {
            event.preventDefault();
            addFiles(files);
        }
    });

    // Drag files onto the conversation
    const threadView = root.querySelector('[data-view="thread"]');
    let dragDepth = 0;
    const hasFiles = (e) => e.dataTransfer && Array.from(e.dataTransfer.types || []).includes('Files');
    threadView.addEventListener('dragenter', (e) => {
        if (!hasFiles(e) || state.locked) return;
        e.preventDefault();
        dragDepth++;
        threadView.classList.add('is-dragging');
    });
    threadView.addEventListener('dragover', (e) => {
        if (!hasFiles(e) || state.locked) return;
        e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';
    });
    threadView.addEventListener('dragleave', () => {
        dragDepth = Math.max(0, dragDepth - 1);
        if (!dragDepth) threadView.classList.remove('is-dragging');
    });
    threadView.addEventListener('drop', (e) => {
        if (!hasFiles(e)) return;
        e.preventDefault();
        dragDepth = 0;
        threadView.classList.remove('is-dragging');
        addFiles(e.dataTransfer.files);
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
        if (event.key !== 'Escape') return;
        if (!el.lightbox.hidden) { closeLightbox(); return; }
        if (!state.open) return;
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
