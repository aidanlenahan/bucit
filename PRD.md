# BucIT Chromebook Support Platform — Product Requirements Document (PRD)

**Date:** 2026-03-03  
**Product:** BucIT Support (student-facing support + technician operations)  
**Prepared from:** Full workspace review across public pages, technician/admin pages, docs, notes, and backlog files

---

## 1) Product Summary

BucIT is a school Chromebook support platform that currently includes:
- Public ticket intake and status checking
- Guided self-service via chatbot and help articles
- Technician workflow for triage, repair updates, closure, and communication
- Inventory tracking for replacement parts
- Technician account administration
- Gamified technician leaderboard ("Bucit Ranked")
- Kiosk UI prototype for check-in/check-out with barcode flow

The platform’s purpose is to reduce time-to-resolution, improve student communication, and give technicians structured workflows and accountability.

---

## 2) Goals and Success Metrics

### Primary goals
1. Reduce avoidable ticket submissions via self-help (chatbot + articles)
2. Improve technician efficiency and consistency in handling repairs
3. Improve student visibility into ticket state and pickup readiness
4. Track operational data (parts usage, closures, technician output)
5. Prepare for loaner lifecycle management and warranty-aware triage

### Success metrics (KPIs)
- % of chatbot/help-article sessions that avoid ticket submission
- Ticket volume by problem category
- Median and average time from ticket creation to closure
- % tickets with complete required fields (SN, category, status, etc.)
- % closed tickets with email notification sent successfully
- Inventory stockout events per month
- Loaner overdue rate (future phase)
- Technician throughput + point accuracy (leaderboard integrity)

---

## 3) Users and Roles

### Student (public user)
- Submits support tickets
- Checks ticket status by ticket ID
- Uses chatbot and help articles before creating ticket
- Interacts with kiosk for check-in/check-out (future backend phase)

### Technician (authenticated)
- Views and filters ticket queue
- Picks up tickets, edits status/notes, assigns parts used
- Closes tickets and sends resolution emails
- Manages inventory (if permitted)
- Appears on leaderboard

### Admin (jmilonas)
- Full technician access plus technician account administration
- Can create/disable/reset/delete technician accounts
- Can manually adjust leaderboard points and per-ticket awards

### Future role: Front Desk (planned)
- Limited permissions (view tickets, pickup updates, loaner issuance)
- Restricted from private tech notes, inventory modification, destructive actions

---

## 4) Current-State Capabilities (Implemented)

## 4.1 Public experience
- Home page with navigation to form, status, chatbot, articles, support
- Intake form captures issue category/detail, restart status, school identity fields
- Ticket creation persists to DB and redirects to confirmation with ticket ID
- Status page allows ticket lookup and displays student-visible fields
- Error pages (403/404/500) with recovery navigation
- Dark-first visual baseline and Google Translate widget on core public pages

## 4.2 Self-service
- Rule-based chatbot with branch logic for common issue categories
- Help article hub + category pages (battery, display, input, connectivity, login, restart)
- Intake form presents chatbot reminder modal before submission

## 4.3 Technician operations
- Technician/admin routes are grouped under `/tech/*` with centralized URL namespace
- Login + forced password-change flow
- Ticket management dashboard with sorting/filtering/search/pagination
- Ticket detail/edit page with status, notes, assignment, pickup actions
- Explicit close-ticket flow with resolution notes and optional email

## 4.4 Communications
- Email confirmations on ticket creation
- Email notifications on ticket closure
- Test harness for email configuration

## 4.5 Inventory
- Inventory CRUD + quantity adjustments + low-stock visual indicators
- Attach parts to tickets with transactional deduction and duplicate prevention
- Prevent deleting parts already used historically

## 4.6 Admin and competition
- Technician account management page (active state, resets, must-change flags, custom password, user creation/deletion)
- Leaderboard page with trophy totals and ticket-level point detail
- Admin override for manual point adjustment/reset

## 4.7 Kiosk prototype
- Check-in/check-out UI flow and manual fallback form
- Simulated ticket confirmation behavior (mock)
- No production API/database integration yet

---

## 5) Problem Statements / Gaps

1. **Loaner workflow is not implemented** despite being highest-priority backlog item.
2. **Warranty lookup integration is missing** (SN exists, but no warranty provider integration).
3. **Role-based access is incomplete** (front-desk role planned but not implemented).
4. **Kiosk is frontend-only prototype** without live API/ticket integration.
5. **Security hardening backlog remains** (CSRF, stronger validation/rate limiting, environmentized secrets).
6. **Analytics/reporting is not productized** (no formal dashboard/export/report workflow).
7. **Some UX/content inconsistencies** exist between pages and older notes/scripts.

---

## 6) Scope and Requirements

## 6.1 In Scope (Current + Next Phases)

### A) Public ticket lifecycle
- Submit ticket with required student + issue fields
- Receive immediate confirmation and ticket ID
- Check status with ticket ID and see student-safe update fields

### B) Self-service deflection
- Guided troubleshooting chatbot for common issue categories
- Searchable/organized article library linked from chatbot and intake
- “Still need help” path into ticket creation

### C) Technician ticket workflow
- Authenticated queue with sorting/filtering/search/pagination
- Open → In Progress → Closed status progression
- Assignment to technician
- Notes model separating public and private notes
- Pickup tracking fields shown where relevant

### D) Notification workflow
- Email on ticket creation
- Email on closure with resolution notes
- Future status-change and reminder notifications

### E) Parts and inventory
- Maintain part catalog and stock counts
- Attach consumed parts to tickets and auto-deduct stock
- Prevent data corruption via transactional updates

### F) Team administration + leaderboard
- Manage technician accounts and password policies
- Track ticket-resolution points per technician
- Allow admin point overrides for complex cases

### G) Kiosk operations (phase-based)
- Barcode-based check-in/check-out UX
- Manual entry fallback
- Future integration to student systems + ticket backend

## 6.2 Out of Scope (for immediate MVP hardening)
- Native mobile apps
- Full AI LLM chatbot integration (beyond current deterministic flow)
- Advanced supplier API for auto ordering
- Full calendar scheduling system

---

## 7) Functional Requirements

## 7.1 Public User Requirements
1. User can create a ticket from intake form with required validation.
2. System stores ticket and generates unique ticket ID.
3. User sees ticket confirmation and status-check entry points.
4. User can check status by ticket ID without technician login.
5. Public pages provide multilingual support option (Translate widget).
6. User can access chatbot and article knowledge base from all primary navigation surfaces.

## 7.2 Technician Requirements
1. Technician must authenticate before accessing management pages.
2. Ticket list supports sorting, search, and filters.
3. Technician can view and edit ticket details, including assignment/state.
4. Technician can mark pickup-related state.
5. Technician can close ticket with required resolution notes.
6. System can send closure email when selected.

## 7.3 Inventory Requirements
1. Technician can create/update/delete inventory parts.
2. System tracks quantity and supports quick quantity adjustment.
3. Technician can attach one or more parts to ticket.
4. System deducts attached quantities atomically.
5. System prevents duplicate attachments and over-consumption.
6. Part deletion is blocked if historical ticket usage exists.

## 7.4 Admin Requirements
1. Admin can create technician accounts with generated username.
2. Admin can enable/disable technician accounts.
3. Admin can reset or set passwords and force password changes.
4. Admin can view leaderboard totals and per-ticket point records.
5. Admin can override point awards and revert to defaults.

## 7.5 Kiosk Requirements (future implementation)
1. Check-in and check-out flows accept barcode scanner input.
2. Student profile data auto-populates from SIS/library API.
3. Kiosk can create/check out tickets in primary ticket DB.
4. Manual fallback supports full ticket creation when scan fails.
5. Kiosk auto-resets after each transaction.

---

## 8) Non-Functional Requirements

### Security and Privacy
- Enforce authentication on all technician/admin pages.
- Add CSRF protection to all state-changing forms.
- Add rate-limiting/captcha on public and auth endpoints.
- Move credentials to environment-secure configuration.
- Ensure private notes never surface on public status pages.

### Performance
- Ticket list operations should remain responsive with pagination.
- Add DB indexes for common query paths (status, date, category, tech, SN).

### Reliability
- Inventory + ticket-part writes must remain transactional.
- Email failures should not block core ticket state changes.

### Usability
- Consistent navigation and branding across public and internal pages.
- Touch-friendly kiosk UI for student flow.

### Accessibility
- Keyboard-navigable forms and actionable controls.
- Clear contrast and readable typography in both light/dark themes.

---

## 9) Data and Domain Model (Current + Planned)

### Core entities currently present
- `tickets`
- `technicians`
- `inventory`
- `ticket_parts`
- `ticket_points`

### Planned entities
- `loaner_devices`
- `loaner_assignments` (or ticket-linked loaner columns)
- `warranty_cache`
- `roles` / permissions mapping for front-desk and admin separation

### Critical relationships
- `ticket_parts` links tickets ↔ inventory with quantity consumed
- `ticket_points` links tickets ↔ technician scoring history
- Loaner model should link each loaner assignment to one ticket interval and return state

---

## 10) Release Phasing and Prioritization

## Phase 0 — Stabilization (Immediate)
- Security hardening (CSRF, validation consistency, rate limiting)
- Secrets/config cleanup and deployment hygiene
- Resolve UX/content inconsistencies across form/chatbot/status/support copy

## Phase 1 — High Priority Next Sprint
1. Loaner Chromebook system (table, assignment, due dates, reminders, ticket linkage)
2. Warranty lookup integration using Chromebook SN + caching
3. Role-based access (front desk permissions)
4. Enhanced chatbot handoff into pre-filled tickets

## Phase 2 — Medium Priority
- Automated status/update reminders and digest notifications
- Improved search/filters + SN/date/category/tech facets
- Student dashboard (multi-ticket view and richer status experience)
- Analytics/reporting exports
- Leaderboard enhancements (time windows, badges, bonus logic)

## Phase 3 — Nice-to-Have
- QR claim/status flow
- SMS notifications
- Photo uploads
- Calendar integration
- Expanded knowledge base with richer media
- Advanced AI chatbot

---

## 11) Acceptance Criteria (Near-Term)

1. A student can complete a full ticket lifecycle: submit → confirmation → status lookup.
2. A technician can authenticate, update ticket, attach parts, close ticket, and notify student.
3. Admin can fully manage technician accounts and adjust leaderboard points.
4. Inventory counts remain correct under concurrent technician usage.
5. Private/internal pages are inaccessible without authentication.
6. Priority Phase 1 requirements have implementation specs and schema approved.

---

## 12) Risks and Mitigations

1. **Credential/security exposure risk**  
   Mitigation: migrate sensitive config to environment secrets and rotate credentials.

2. **Data integrity risk in multi-user workflows**  
   Mitigation: enforce transactions, constraints, and idempotent update patterns.

3. **Operational inconsistency from role ambiguity**  
   Mitigation: implement explicit RBAC before front-desk scale-up.

4. **Kiosk launch risk without backend integration**  
   Mitigation: define API contract and complete end-to-end integration before deployment.

5. **Notification fatigue or mismatch**  
   Mitigation: configurable notification preferences and clear trigger policy.

---

## 13) Open Questions

1. What is the authoritative source/API for barcode → student identity lookup (Genesis vs Destiny priority order)?
2. What exact SLA and overdue policy should govern loaner return reminders/escalations?
3. Should students authenticate for dashboard access, or remain ticket-ID-only in near term?
4. What reporting cadence is required by school stakeholders (weekly/monthly/term)?
5. Should leaderboard points be tied to closure only, or include pickup/loaner handling events?

---

## 14) Appendix — Source Coverage (Audited)

This PRD was derived from:
- Public pages: home, intake, status, chatbot, support, article hub + article pages, kiosk screens, error pages
- Technician/admin pages: login/logout/password, ticket management, ticket edit/close, inventory pages, leaderboard, admin technician management
- Backend/service files: ticket submission, email functions, schema update, normalization/test scripts
- Docs/notes/backlog: todo list, sprint journal, overview, kiosk overview, inventory/email integration docs, chatbot flowchart, leaderboard prompt, loaner notes, robots/access hints

This document reflects both implemented behavior and planned roadmap items discovered in those sources.