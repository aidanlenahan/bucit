// demo.js - Browser-only stand-in for the PHP/MySQL backend so BucIT can run as a
// static GitHub Pages demo. Data lives in localStorage (seeded from seed-data.js),
// technician "sessions" live in localStorage, and outgoing email is captured in an
// outbox instead of being sent through PHPMailer.
(function () {
    'use strict';

    const SCRIPT_SRC = document.currentScript ? document.currentScript.src : '';
    const ROOT = SCRIPT_SRC.replace(/js\/demo\.js(\?.*)?$/, '');
    const STORE_KEY = 'bucit-demo-db-v1';
    const SESSION_KEY = 'bucit-demo-session';
    const FLASH_KEY = 'bucit-demo-flash';
    const REPO_URL = 'https://github.com/aidanlenahan/bucit';
    const TABLES = ['technicians', 'students', 'inventory', 'tickets', 'ticket_parts', 'ticket_points', 'outbox'];

    // ------------------------------------------------------------ storage helpers
    function storageGet(key) {
        try { return window.localStorage.getItem(key); } catch (e) { return null; }
    }
    function storageSet(key, value) {
        try { window.localStorage.setItem(key, value); } catch (e) { /* private mode: data is per-page only */ }
    }
    function storageRemove(key) {
        try { window.localStorage.removeItem(key); } catch (e) { /* ignore */ }
    }

    // ------------------------------------------------------------------ database
    let state = null;

    function freshState() {
        const seed = window.BUCIT_SEED;
        if (!seed) {
            throw new Error('seed-data.js must be loaded before using the demo database.');
        }
        const copy = JSON.parse(JSON.stringify(seed));
        copy.outbox = [];
        return copy;
    }

    function db() {
        if (state) return state;
        const raw = storageGet(STORE_KEY);
        if (raw) {
            try {
                const parsed = JSON.parse(raw);
                if (window.BUCIT_SEED && parsed.version !== window.BUCIT_SEED.version) {
                    state = freshState();
                } else {
                    state = parsed;
                }
            } catch (e) {
                state = null;
            }
        }
        if (!state) state = freshState();
        TABLES.forEach((t) => { if (!Array.isArray(state[t])) state[t] = []; });
        return state;
    }

    function save() {
        if (state) storageSet(STORE_KEY, JSON.stringify(state));
    }

    function nextId(table) {
        return db()[table].reduce((max, row) => Math.max(max, Number(row.id) || 0), 0) + 1;
    }

    function insert(table, row) {
        const record = Object.assign({ id: nextId(table) }, row);
        db()[table].push(record);
        return record;
    }

    function reset() {
        storageRemove(STORE_KEY);
        storageRemove(SESSION_KEY);
        storageRemove(FLASH_KEY);
        state = null;
    }

    function ticketById(id) {
        const n = Number(id);
        return db().tickets.find((t) => t.id === n) || null;
    }

    function techByUsername(username) {
        return db().technicians.find((t) => t.username === username) || null;
    }

    function adminUser() {
        return (window.BUCIT_SEED && window.BUCIT_SEED.adminUser) || db().adminUser || 'admin';
    }

    function isAdmin(username) {
        return !!username && username === adminUser();
    }

    // ------------------------------------------------------------------- session
    function session() {
        const raw = storageGet(SESSION_KEY);
        if (!raw) return null;
        try { return JSON.parse(raw); } catch (e) { return null; }
    }

    function setSession(value) {
        if (value) storageSet(SESSION_KEY, JSON.stringify(value));
        else storageRemove(SESSION_KEY);
    }

    // Mirrors tech/login.php
    function login(username, password) {
        const user = (username || '').trim();
        if (user === '' || !password) return { ok: false, error: 'Enter username and password.' };
        const row = techByUsername(user);
        if (!row) return { ok: false, error: 'Invalid credentials.' };
        if (!row.active) return { ok: false, error: 'Account is not active.' };
        if (row.password !== password) return { ok: false, error: 'Invalid credentials.' };
        const s = {
            tech_user: row.username,
            tech_display: row.display_name || row.username,
            tech_id: row.id,
            must_change_password: row.must_change_password ? 1 : 0,
        };
        setSession(s);
        return { ok: true, session: s };
    }

    function logout() {
        setSession(null);
    }

    // Redirects (like the PHP header('Location: ...')) when not logged in.
    function requireTech(fallback) {
        const s = session();
        if (!s || !s.tech_user || !techByUsername(s.tech_user)) {
            setSession(null);
            window.location.replace(url(fallback || 'tech/login.html'));
            return null;
        }
        return s;
    }

    // --------------------------------------------------------------------- flash
    function setFlash(value) {
        storageSet(FLASH_KEY, JSON.stringify(value));
    }

    function takeFlash() {
        const raw = storageGet(FLASH_KEY);
        storageRemove(FLASH_KEY);
        if (!raw) return null;
        try { return JSON.parse(raw); } catch (e) { return null; }
    }

    // --------------------------------------------------------------------- utils
    function esc(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function nl2br(value) {
        return esc(value).replace(/\r?\n/g, '<br>\n');
    }

    function url(path) {
        return ROOT + path;
    }

    function param(name) {
        return new URLSearchParams(window.location.search).get(name);
    }

    const pad = (n) => String(n).padStart(2, '0');

    function nowSql() {
        const d = new Date();
        return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
    }

    function todaySql() {
        return nowSql().slice(0, 10);
    }

    function parseSqlDate(value) {
        const m = /^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2})(?::(\d{2}))?)?/.exec(value || '');
        if (!m) return null;
        return new Date(+m[1], +m[2] - 1, +m[3], +(m[4] || 0), +(m[5] || 0), +(m[6] || 0));
    }

    const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

    // Subset of PHP's date() format characters used by the original pages
    function formatDate(value, format) {
        const d = parseSqlDate(value);
        if (!d) return '';
        const h = d.getHours();
        const map = {
            d: pad(d.getDate()),
            j: String(d.getDate()),
            M: MONTHS[d.getMonth()].slice(0, 3),
            F: MONTHS[d.getMonth()],
            Y: String(d.getFullYear()),
            g: String(h % 12 || 12),
            H: pad(h),
            i: pad(d.getMinutes()),
            s: pad(d.getSeconds()),
            A: h < 12 ? 'AM' : 'PM',
            a: h < 12 ? 'am' : 'pm',
        };
        return format.replace(/[djMFYgHisAa]/g, (c) => map[c]);
    }

    // ------------------------------------------------------------- ticket points
    // Mirrors bucit_default_points_for_category() / bucit_award_points_if_needed()
    function defaultPoints(category) {
        const defaults = db().pointDefaults || {};
        const key = String(category || '').trim().toLowerCase();
        return defaults[key] !== undefined ? defaults[key] : 0;
    }

    function awardPointsIfNeeded(ticket) {
        if (!ticket || String(ticket.status || '').toLowerCase() !== 'closed') return;
        const tech = String(ticket.tech || '').trim();
        if (tech === '' || isAdmin(tech)) return;

        const base = defaultPoints(ticket.problem_category);
        const existing = db().ticket_points.find((p) => p.ticket_id === ticket.id);
        if (existing) {
            Object.assign(existing, {
                tech,
                problem_category: ticket.problem_category,
                base_points: base,
                updated_by: 'system',
                updated_at: nowSql(),
            });
            if (!existing.manual_override) existing.awarded_points = base;
            return;
        }
        insert('ticket_points', {
            ticket_id: ticket.id,
            tech,
            problem_category: ticket.problem_category,
            base_points: base,
            awarded_points: base,
            manual_override: 0,
            notes: null,
            updated_by: 'system',
            updated_at: nowSql(),
        });
    }

    // Mirrors bucit_backfill_ticket_points() in leaderboard.php
    function backfillPoints() {
        const data = db();
        data.tickets.forEach((t) => {
            const tech = String(t.tech || '').trim();
            if (t.status !== 'Closed' || tech === '' || isAdmin(tech)) return;
            if (data.ticket_points.some((p) => p.ticket_id === t.id)) return;
            const base = defaultPoints(t.problem_category);
            insert('ticket_points', {
                ticket_id: t.id,
                tech,
                problem_category: t.problem_category,
                base_points: base,
                awarded_points: base,
                manual_override: 0,
                notes: null,
                updated_by: 'backfill',
                updated_at: nowSql(),
            });
        });
    }

    // --------------------------------------------------------------------- email
    // Same templates as includes/functions.php; captured instead of sent.
    function ticketCreatedEmail(firstName, lastName, data) {
        const ticketNumber = esc(data.ticketNumber);
        const problem = esc(data.problem || 'Not specified');
        const detail = data.problemDetail ? esc(data.problemDetail) : '';
        const date = esc(data.dateReported || todaySql());
        return {
            subject: `Support Ticket #${data.ticketNumber} - Confirmation`,
            html: `<html><body style='font-family: Arial, sans-serif; color: #333;'>
<div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
<h2 style='color: #800000;'>Support Ticket Submitted</h2>
<p>Hello <strong>${esc(firstName)} ${esc(lastName)}</strong>,</p>
<p>Your support ticket has been successfully submitted and assigned to our technical support team.</p>
<div style='background: #f8f9fa; padding: 15px; border-left: 4px solid #800000; margin: 20px 0;'>
<p style='margin: 5px 0;'><strong>Ticket Number:</strong> #${ticketNumber}</p>
<p style='margin: 5px 0;'><strong>Date Submitted:</strong> ${date}</p>
<p style='margin: 5px 0;'><strong>Problem Category:</strong> ${problem}</p>
${detail ? `<p style='margin: 5px 0;'><strong>Details:</strong> ${detail}</p>` : ''}
</div>
<p>A technician will review your ticket and contact you shortly. You can check your ticket status at any time.</p>
<p style='margin-top: 30px; color: #666; font-size: 12px;'>Thank you for using BUCIT Support<br>This is an automated message, please do not reply to this email.</p>
</div></body></html>`,
        };
    }

    function ticketClosedEmail(firstName, lastName, data) {
        return {
            subject: `Support Ticket #${data.ticketNumber} - Closed`,
            html: `<html><body style='font-family: Arial, sans-serif; color: #333;'>
<div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
<h2 style='color: #800000;'>Your Support Ticket Has Been Closed</h2>
<p>Hello <strong>${esc(firstName)} ${esc(lastName)}</strong>,</p>
<p>Your support ticket has been successfully resolved and closed.</p>
<div style='background: #f8f9fa; padding: 15px; border-left: 4px solid #800000; margin: 20px 0;'>
<p style='margin: 5px 0;'><strong>Ticket Number:</strong> #${esc(data.ticketNumber)}</p>
<p style='margin: 5px 0;'><strong>Status:</strong> ${esc(data.status || 'Closed')}</p>
<p style='margin: 5px 0;'><strong>Closed Date:</strong> ${esc(data.closedDate || nowSql())}</p>
<p style='margin: 5px 0;'><strong>Resolution:</strong></p>
<p style='margin: 5px 0 5px 20px;'>${nl2br(data.resolution || 'Issue resolved')}</p>
</div>
<p>If you have any additional questions or concerns, please feel free to submit a new ticket.</p>
<p style='margin-top: 30px; color: #666; font-size: 12px;'>Thank you for using BUCIT Support<br>This is an automated message, please do not reply to this email.</p>
</div></body></html>`,
        };
    }

    // Returns the outbox entry id, standing in for sendTicketCreatedEmail()/sendTicketClosedEmail()
    function sendEmail(kind, to, firstName, lastName, data) {
        const built = kind === 'closed'
            ? ticketClosedEmail(firstName, lastName, data)
            : ticketCreatedEmail(firstName, lastName, data);
        const entry = insert('outbox', {
            kind,
            to,
            to_name: `${firstName} ${lastName}`,
            subject: built.subject,
            html: built.html,
            sent_at: nowSql(),
        });
        return entry.id;
    }

    // ---------------------------------------------------------------------- modal
    function ensureModalStyles() {
        if (document.getElementById('bucit-demo-styles')) return;
        const style = document.createElement('style');
        style.id = 'bucit-demo-styles';
        style.textContent = `
.bucit-demo-bar { background: #1f1f1f; color: #eee; font: 13px/1.4 Arial, sans-serif; padding: 8px 16px; display: flex; flex-wrap: wrap; gap: 6px 16px; align-items: center; justify-content: center; position: relative; z-index: 1001; }
.bucit-demo-bar strong { color: #ffd479; letter-spacing: .3px; }
.bucit-demo-bar a, .bucit-demo-bar button { color: #fff; background: none; border: 0; padding: 0; margin: 0; width: auto; font: inherit; cursor: pointer; text-decoration: underline; text-underline-offset: 2px; }
.bucit-demo-bar a:hover, .bucit-demo-bar button:hover { color: #ffd479; background: none; }
.bucit-demo-bar .sep { opacity: .4; }
.bucit-demo-modal { position: fixed; inset: 0; background: rgba(0,0,0,.55); z-index: 3000; display: flex; align-items: flex-start; justify-content: center; padding: 5vh 16px; overflow-y: auto; }
.bucit-demo-modal .dialog { background: #fff; color: #222; width: 100%; max-width: 680px; border-radius: 8px; box-shadow: 0 10px 40px rgba(0,0,0,.35); overflow: hidden; font-family: Arial, sans-serif; }
.bucit-demo-modal .dialog-head { background: #800000; color: #fff; padding: 14px 18px; display: flex; justify-content: space-between; align-items: center; gap: 12px; }
.bucit-demo-modal .dialog-head h3 { margin: 0; font-size: 17px; color: #fff; text-align: left; }
.bucit-demo-modal .dialog-head button { background: transparent; color: #fff; border: 2px solid #fff; border-radius: 4px; width: auto; padding: 2px 10px; font-size: 18px; line-height: 1; cursor: pointer; margin: 0; }
.bucit-demo-modal .dialog-body { padding: 16px 18px; }
.bucit-demo-modal .meta { font-size: 13px; color: #555; margin: 0 0 10px; line-height: 1.6; }
.bucit-demo-modal .note { font-size: 12px; color: #856404; background: #fff3cd; border-left: 4px solid #ffc107; padding: 8px 10px; margin: 0 0 12px; }
.bucit-demo-modal iframe { width: 100%; height: 420px; border: 1px solid #ddd; border-radius: 4px; background: #fff; }
.bucit-demo-modal ul { list-style: none; margin: 0; padding: 0; }
.bucit-demo-modal li button { display: block; width: 100%; text-align: left; background: #f8f9fa; color: #222; border: 1px solid #dee2e6; border-radius: 6px; padding: 10px 12px; margin: 0 0 8px; cursor: pointer; font: 14px Arial, sans-serif; }
.bucit-demo-modal li button:hover { border-color: #800000; background: #fff; }
.bucit-demo-modal li small { color: #666; display: block; margin-top: 2px; }
.bucit-email-link { display: inline-block; margin-left: 6px; color: inherit; font-weight: 600; text-decoration: underline; cursor: pointer; background: none; border: 0; padding: 0; width: auto; font-size: inherit; }
@media (max-width: 760px) {
  nav > div { flex-wrap: wrap; gap: 4px 8px !important; padding: 0 12px !important; }
  nav a { padding: 6px 8px !important; }
  main { width: auto !important; margin: 16px !important; }
  .management-container, .edit-container, .container, .leaderboard-container, .admin-container, .articles-container, .kiosk-container, .close-ticket-container { margin-left: 8px !important; margin-right: 8px !important; }
  .tickets-table, .tech-table, .detail-table, .table-container table { display: block; overflow-x: auto; white-space: nowrap; }
  .header-actions, .header { flex-wrap: wrap; gap: 10px; }
  .filter-bar-top { flex-wrap: wrap; }
  .hero h1 { font-size: 2.2em !important; }
}`;
        document.head.appendChild(style);
    }

    function openModal(title, bodyHtml) {
        ensureModalStyles();
        closeModal();
        const wrap = document.createElement('div');
        wrap.className = 'bucit-demo-modal';
        wrap.id = 'bucit-demo-modal';
        wrap.innerHTML = `<div class="dialog" role="dialog" aria-modal="true" aria-label="${esc(title)}">
<div class="dialog-head"><h3>${esc(title)}</h3><button type="button" aria-label="Close">&times;</button></div>
<div class="dialog-body">${bodyHtml}</div></div>`;
        wrap.addEventListener('click', (e) => { if (e.target === wrap) closeModal(); });
        wrap.querySelector('.dialog-head button').addEventListener('click', closeModal);
        document.body.appendChild(wrap);
        return wrap;
    }

    function closeModal() {
        const existing = document.getElementById('bucit-demo-modal');
        if (existing) existing.remove();
    }

    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });

    function showEmail(id) {
        const entry = db().outbox.find((m) => m.id === Number(id));
        if (!entry) return;
        const modal = openModal('Email preview', `
<p class="note">Demo mode: on the real server PHPMailer sends this over SMTP. Here it is captured in the outbox instead.</p>
<p class="meta"><strong>To:</strong> ${esc(entry.to_name)} &lt;${esc(entry.to)}&gt;<br>
<strong>Subject:</strong> ${esc(entry.subject)}<br><strong>Sent:</strong> ${esc(formatDate(entry.sent_at, 'M j, Y g:i a'))}</p>
<iframe title="Email body" sandbox=""></iframe>`);
        modal.querySelector('iframe').srcdoc = entry.html;
    }

    function showOutbox() {
        const mail = db().outbox.slice().reverse();
        const list = mail.length
            ? '<ul>' + mail.map((m) => `<li><button type="button" data-email="${m.id}"><strong>${esc(m.subject)}</strong>
<small>To ${esc(m.to)} · ${esc(formatDate(m.sent_at, 'M j, Y g:i a'))}</small></button></li>`).join('') + '</ul>'
            : '<p class="meta">No emails yet. Submit a ticket or close one from the technician dashboard and the email shows up here.</p>';
        const modal = openModal('Demo outbox', list);
        modal.querySelectorAll('[data-email]').forEach((btn) => {
            btn.addEventListener('click', () => showEmail(btn.dataset.email));
        });
    }

    // --------------------------------------------------------------------- banner
    function injectBanner() {
        ensureModalStyles();
        if (document.querySelector('.bucit-demo-bar')) return;
        const s = session();
        const bar = document.createElement('div');
        bar.className = 'bucit-demo-bar';
        bar.setAttribute('role', 'region');
        bar.setAttribute('aria-label', 'Demo controls');
        const techLink = s
            ? `<a href="${url('tech/manage_tickets.html')}">Tech dashboard (${esc(s.tech_user)})</a>`
            : `<a href="${url('tech/login.html')}">Technician login</a>`;
        bar.innerHTML = `<strong>LIVE DEMO</strong><span>Runs entirely in your browser, data is saved locally.</span>
<span class="sep">|</span><a href="${url('index.html')}">Student site</a>
<a href="${url('kiosk.html')}">Kiosk</a>${techLink}
<button type="button" data-demo="outbox">Email outbox</button>
<button type="button" data-demo="reset">Reset demo data</button>
<a href="${REPO_URL}" target="_blank" rel="noopener">Source</a>`;
        bar.querySelector('[data-demo="outbox"]').addEventListener('click', () => {
            if (!window.BUCIT_SEED && !storageGet(STORE_KEY)) {
                openModal('Demo outbox', '<p class="meta">No emails yet. Submit a ticket or close one from the technician dashboard and the email shows up here.</p>');
                return;
            }
            if (!window.BUCIT_SEED) {
                // Outbox only needs stored data, not the seed
                try { state = JSON.parse(storageGet(STORE_KEY)); } catch (e) { state = { outbox: [] }; }
                if (!Array.isArray(state.outbox)) state.outbox = [];
            }
            showOutbox();
        });
        bar.querySelector('[data-demo="reset"]').addEventListener('click', () => {
            if (!window.confirm('Reset all demo tickets, inventory and accounts back to the starting data? This also logs you out.')) return;
            reset();
            window.location.href = url('index.html');
        });
        document.body.insertBefore(bar, document.body.firstChild);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', injectBanner);
    } else {
        injectBanner();
    }

    window.Bucit = {
        db, save, insert, nextId, reset, ticketById, techByUsername, adminUser, isAdmin,
        session, setSession, login, logout, requireTech,
        setFlash, takeFlash,
        esc, nl2br, url, param, nowSql, todaySql, formatDate,
        defaultPoints, awardPointsIfNeeded, backfillPoints,
        sendEmail, showEmail, showOutbox, openModal, closeModal,
    };
})();
