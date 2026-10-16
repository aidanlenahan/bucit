// generate-demo-data.mjs - Builds the fictional demo dataset used by both the
// static GitHub Pages demo (docs/js/seed-data.js) and the MySQL seed for WAMP/LAMP
// (sql/bucit.sql). Output is deterministic so both copies always match.
//
// Usage: node scripts/generate-demo-data.mjs

import { writeFileSync, mkdirSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');

// Seeded PRNG (mulberry32) so every run produces identical data
let seed = 20260303;
function rand() {
    seed |= 0; seed = (seed + 0x6D2B79F5) | 0;
    let t = Math.imul(seed ^ (seed >>> 15), 1 | seed);
    t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
    return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
}
const pick = (arr) => arr[Math.floor(rand() * arr.length)];
const int = (min, max) => min + Math.floor(rand() * (max - min + 1));
const pad = (n) => String(n).padStart(2, '0');

// The PHP code hardcodes the admin username; the static demo uses a neutral one.
const SQL_ADMIN = 'jmilonas';
const DEMO_ADMIN = 'admin';
const DEMO_PASSWORD = 'demo';
const DEMO_PASSWORD_HASH = '$2y$10$pygp1Z.rls/bwfOCvL55puAWpYGkwCe4Ri9lkzvBtxwm48.GYrm6.'; // password_hash('demo')

const POINT_DEFAULTS = { screen: 10, battery: 5, keyboard: 7, wifi: 2, login: 3, other: 0 };

// ---------------------------------------------------------------- technicians
const technicians = [
    { username: '__ADMIN__', display_name: 'Admin', active: 1, must_change_password: 0, created_at: '2025-08-25 09:00:00' },
    { username: 'arivera', display_name: 'Alex Rivera', active: 1, must_change_password: 0, created_at: '2025-09-02 08:15:00' },
    { username: 'mchen', display_name: 'Maya Chen', active: 1, must_change_password: 0, created_at: '2025-09-02 08:17:00' },
    { username: 'jokafor', display_name: 'Jordan Okafor', active: 1, must_change_password: 0, created_at: '2025-09-02 08:20:00' },
    { username: 'spatel', display_name: 'Sam Patel', active: 1, must_change_password: 0, created_at: '2025-09-15 13:40:00' },
    { username: 'tnguyen', display_name: 'Taylor Nguyen', active: 0, must_change_password: 1, created_at: '2025-10-06 10:05:00' },
].map((t, i) => ({ id: i + 1, ...t }));
const workingTechs = ['arivera', 'mchen', 'jokafor', 'spatel'];

// ------------------------------------------------------------------- students
const firstNames = ['Olivia', 'Liam', 'Emma', 'Noah', 'Ava', 'Elijah', 'Sophia', 'Lucas', 'Isabella', 'Mason',
    'Mia', 'Ethan', 'Harper', 'Logan', 'Amelia', 'Aiden', 'Evelyn', 'Caleb', 'Abigail', 'Owen',
    'Chloe', 'Wyatt', 'Grace', 'Henry'];
const lastNames = ['Bennett', 'Castillo', 'Donovan', 'Ellison', 'Fischer', 'Garza', 'Hollis', 'Ibarra', 'Jensen', 'Kowalski',
    'Lindqvist', 'Moreno', 'Novak', 'Ortega', 'Pruitt', 'Quinlan', 'Ramos', 'Sato', 'Thornton', 'Underwood',
    'Vance', 'Whitaker', 'Yilmaz', 'Zimmerman'];
const students = firstNames.map((first, i) => {
    const last = lastNames[(i * 7) % lastNames.length];
    return {
        student_id: String(240101 + i * 13),
        first_name: first,
        last_name: last,
        full_name: `${first} ${last}`,
        class_of: String(pick([2026, 2027, 2028, 2029])),
    };
});

// ------------------------------------------------------------------ inventory
const inventory = [
    ['11.6" HD Screen Assembly (non-touch)', 'L92210-001', 6, 'Fits HP Chromebook 11 G8/G9 EE. Remove bezel clips from the bottom edge first.'],
    ['11.6" Touch Screen Assembly', 'L92211-001', 2, 'Touch models only - check for the stylus garage before ordering.'],
    ['Keyboard w/ Top Cover (US)', 'M47750-001', 9, ''],
    ['Replacement Keycap Kit', 'KC-UNIV-11', 25, 'Mixed keycaps + hinge clips. Works for most 11" models.'],
    ['Battery 3-cell 47Wh', 'L75253-271', 4, 'Disconnect battery cable before any other internal repair.'],
    ['45W USB-C Charger', 'L43407-001', 14, 'Loaner chargers are in the blue bin, do not attach those to tickets.'],
    ['USB-C Charging Port Board', 'L89772-001', 3, ''],
    ['Hinge Kit (L+R)', 'L92225-001', 8, ''],
    ['Bottom Base Enclosure', 'L92228-001', 0, 'Backordered from supplier - ETA 2 weeks.'],
    ['Touchpad Module', 'L92233-001', 5, ''],
    ['WLAN Card (Intel AX201)', 'L57250-005', 7, 'Reseat the antenna leads before replacing the card.'],
    ['Webcam Module', 'L92240-001', 11, ''],
].map(([part_name, part_number, quantity, notes], i) => ({
    id: i + 1, part_name, part_number, quantity, notes, created_at: '2025-09-01 12:00:00',
}));

// -------------------------------------------------------------------- tickets
const details = {
    screen: ['cracked', 'black_screen', 'flickering_display', 'something_else'],
    battery: ['dies_quickly', 'does_not_charge', 'something_else'],
    keyboard: ['key(s)_stuck', 'not_typing', 'keys_missing', 'something_else'],
    wifi: ["can't_connect", 'drops_connection', 'slow_speeds', 'something_else'],
    login: ['forgot_password', 'account_locked', 'something_else'],
    other: ['something_else'],
};
const customDetails = {
    screen: 'Lines across the bottom half of the screen',
    battery: 'Charger light blinks orange then turns off',
    keyboard: 'Spacebar only works if pressed on the left side',
    wifi: 'Only disconnects in the library',
    login: 'Says my account is managed by another school',
    other: ['Speakers crackle at any volume', 'Hinge squeaks and lid will not stay up', 'Camera shows a black image in Meet', 'Trackpad clicks on its own'],
};
const additionalInfo = ['', '', '', 'Need it back before my AP test Friday', 'Happened after it fell off my desk',
    'It worked yesterday', 'Already tried a different charger', 'My sibling has the same model and it works', ''];
const resolutions = {
    screen: ['Replaced screen assembly, tested display at all brightness levels.', 'Reseated display cable, flicker resolved.'],
    battery: ['Replaced battery, verified charge cycle.', 'Charger was faulty, issued new charger.'],
    keyboard: ['Replaced keycaps and hinge clips.', 'Replaced keyboard/top cover assembly.'],
    wifi: ['Forgot and re-added school network, updated ChromeOS.', 'Replaced WLAN card, connection stable.'],
    login: ['Reset password with student present, verified login.', 'Account unlocked by district admin.'],
    other: ['Powerwashed device and re-enrolled.', 'Cleaned and tightened hinges.'],
};
const partsFor = {
    screen: { cracked: 1, black_screen: 1, flickering_display: null },
    battery: { does_not_charge: 6, dies_quickly: 5 },
    keyboard: { 'key(s)_stuck': 4, keys_missing: 4, not_typing: 3 },
    wifi: { drops_connection: 11 },
    other: {},
};
const privateNotes = ['Student says it fell, damage looks like pressure from a backpack.', 'Second ticket this semester for this device.',
    'Waiting on part - check inventory Monday.', 'Device is out of warranty.', ''];

const statusPlan = [
    ...Array(26).fill('Closed'),
    ...Array(8).fill('In Progress'),
    ...Array(4).fill('Delayed'),
    ...Array(9).fill('Open'),
];

function dateAdd(base, days) {
    const d = new Date(base + 'T12:00:00Z');
    d.setUTCDate(d.getUTCDate() + days);
    return `${d.getUTCFullYear()}-${pad(d.getUTCMonth() + 1)}-${pad(d.getUTCDate())}`;
}
function isWeekend(date) {
    const day = new Date(date + 'T12:00:00Z').getUTCDay();
    return day === 0 || day === 6;
}

const tickets = [];
const ticket_parts = [];
let ticketId = 1001;
let dayCursor = 0;
statusPlan.forEach((status, i) => {
    // Spread tickets across the school year in order, oldest closed first
    dayCursor += int(2, 5);
    let date = dateAdd('2025-09-08', dayCursor);
    while (isWeekend(date)) date = dateAdd(date, 1);

    const student = pick(students);
    const category = pick(['screen', 'screen', 'screen', 'battery', 'battery', 'keyboard', 'keyboard', 'wifi', 'login', 'other']);
    const detail = pick(details[category]);
    const custom = detail === 'something_else'
        ? (Array.isArray(customDetails[category]) ? pick(customDetails[category]) : customDetails[category])
        : null;
    const tech = status === 'Open' ? null : pick(workingTechs);
    const closed = status === 'Closed';
    const pickedUp = closed && i < 24 ? 'Yes' : 'No';
    const created = `${date} ${pad(int(7, 14))}:${pad(int(0, 59))}:${pad(int(0, 59))}`;

    let notes = null;
    if (closed) notes = pick(resolutions[category]);
    else if (status === 'Delayed') notes = 'Waiting on a replacement part. We will email you when it arrives.';
    else if (status === 'In Progress' && rand() < 0.5) notes = 'A technician is working on your Chromebook.';

    const ticket = {
        id: ticketId,
        first_name: student.first_name,
        last_name: student.last_name,
        school_id: student.student_id,
        class_of: student.class_of,
        school_email: `${student.first_name}.${student.last_name}`.toLowerCase() + '@students.bucit.example',
        additional_info: pick(additionalInfo),
        date_reported: date,
        problem_category: category,
        problem_detail: detail,
        custom_detail: custom,
        restarted: rand() < 0.65 ? 1 : 0,
        status,
        notes,
        private_notes: tech ? (pick(privateNotes) || null) : null,
        priority: closed ? 5 : (category === 'screen' ? int(1, 2) : int(2, 4)),
        tech,
        chromebook_sn: '5CD' + String(int(100, 399)) + String.fromCharCode(65 + int(0, 25), 65 + int(0, 25)) + String(int(100, 999)),
        picked_up: pickedUp,
        pickup_date: pickedUp === 'Yes' ? dateAdd(date, int(2, 6)) : null,
        created_at: created,
    };
    tickets.push(ticket);

    // Attach parts to repaired hardware tickets
    const partId = partsFor[category]?.[detail];
    if (partId && (closed || status === 'In Progress' || (status === 'Delayed' && rand() < 0.5))) {
        ticket_parts.push({
            id: ticket_parts.length + 1,
            ticket_id: ticket.id,
            part_id: partId,
            quantity_used: partId === 4 ? int(1, 3) : 1,
            added_by: tech,
            added_at: `${dateAdd(date, 1)} 10:${pad(int(0, 59))}:00`,
        });
    }
    ticketId++;
});

// ---------------------------------------------------------------- ticket_points
// Mirrors bucit_award_points_if_needed(): closed + assigned tech (not admin) earns
// the category default. A couple of manual overrides show off the admin tools.
const ticket_points = [];
tickets.filter((t) => t.status === 'Closed' && t.tech).forEach((t) => {
    const base = POINT_DEFAULTS[t.problem_category] ?? 0;
    ticket_points.push({
        id: ticket_points.length + 1,
        ticket_id: t.id,
        tech: t.tech,
        problem_category: t.problem_category,
        base_points: base,
        awarded_points: base,
        manual_override: 0,
        notes: null,
        updated_by: 'system',
        updated_at: (t.pickup_date || t.date_reported) + ' 15:30:00',
    });
});
const overrides = [
    [2, 15, 'Full screen + hinge rebuild, took two periods.'],
    [7, 4, 'Bonus for walking a student through a password reset over email.'],
];
overrides.forEach(([index, points, note]) => {
    const row = ticket_points[index];
    if (!row) return;
    Object.assign(row, { awarded_points: points, manual_override: 1, notes: note, updated_by: '__ADMIN__' });
});

// ------------------------------------------------------------------ write JS
function forDemo(value) {
    return JSON.parse(JSON.stringify(value).replaceAll('__ADMIN__', DEMO_ADMIN));
}
const demoSeed = {
    version: 1,
    adminUser: DEMO_ADMIN,
    pointDefaults: POINT_DEFAULTS,
    technicians: forDemo(technicians).map((t) => ({ ...t, password: DEMO_PASSWORD })),
    students: students.map(({ class_of, ...s }) => s),
    inventory,
    tickets,
    ticket_parts,
    ticket_points: forDemo(ticket_points),
};
mkdirSync(join(root, 'docs', 'js'), { recursive: true });
writeFileSync(
    join(root, 'docs', 'js', 'seed-data.js'),
    '// Generated by scripts/generate-demo-data.mjs - do not edit by hand.\n' +
    '// All names, IDs and emails are fictional.\n' +
    'window.BUCIT_SEED = ' + JSON.stringify(demoSeed, null, 1) + ';\n'
);

// ----------------------------------------------------------------- write SQL
function sqlValue(v) {
    if (v === null || v === undefined) return 'NULL';
    if (typeof v === 'number') return String(v);
    return "'" + String(v).replace(/\\/g, '\\\\').replace(/'/g, "\\'") + "'";
}
function inserts(table, rows, columns) {
    const lines = rows.map((r) => '(' + columns.map((c) => sqlValue(r[c])).join(', ') + ')');
    return `INSERT INTO \`${table}\` (${columns.map((c) => '`' + c + '`').join(', ')}) VALUES\n` + lines.join(',\n') + ';\n';
}
const sqlTechs = JSON.parse(JSON.stringify(technicians).replaceAll('__ADMIN__', SQL_ADMIN))
    .map((t) => ({ ...t, password_hash: DEMO_PASSWORD_HASH }));
const sqlPoints = JSON.parse(JSON.stringify(ticket_points).replaceAll('__ADMIN__', SQL_ADMIN));

const schema = `-- BucIT database schema + fictional demo data
-- Generated by scripts/generate-demo-data.mjs - rerun it instead of editing by hand.
--
-- Import on WAMP/LAMP:   mysql -u root < sql/bucit.sql
-- Every technician account's password is "${DEMO_PASSWORD}". The admin account is "${SQL_ADMIN}".

CREATE DATABASE IF NOT EXISTS \`bucit\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE \`bucit\`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS \`ticket_points\`, \`ticket_parts\`, \`inventory\`, \`tickets\`, \`technicians\`, \`students\`;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE \`technicians\` (
  \`id\` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  \`username\` VARCHAR(64) NOT NULL,
  \`display_name\` VARCHAR(128) DEFAULT NULL,
  \`password_hash\` VARCHAR(255) NOT NULL,
  \`active\` TINYINT(1) NOT NULL DEFAULT 1,
  \`must_change_password\` TINYINT(1) NOT NULL DEFAULT 1,
  \`created_at\` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (\`id\`),
  UNIQUE KEY \`uq_technicians_username\` (\`username\`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE \`tickets\` (
  \`id\` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  \`first_name\` VARCHAR(100) NOT NULL,
  \`last_name\` VARCHAR(100) NOT NULL,
  \`school_id\` VARCHAR(20) NOT NULL,
  \`class_of\` VARCHAR(4) DEFAULT NULL,
  \`school_email\` VARCHAR(255) DEFAULT NULL,
  \`additional_info\` VARCHAR(255) DEFAULT NULL,
  \`date_reported\` DATE NOT NULL,
  \`problem_category\` VARCHAR(50) NOT NULL,
  \`problem_detail\` VARCHAR(100) DEFAULT NULL,
  \`custom_detail\` TEXT DEFAULT NULL,
  \`restarted\` TINYINT(1) NOT NULL DEFAULT 0,
  \`status\` VARCHAR(20) NOT NULL DEFAULT 'Open',
  \`notes\` TEXT DEFAULT NULL,
  \`private_notes\` TEXT DEFAULT NULL,
  \`priority\` TINYINT NOT NULL DEFAULT 3,
  \`tech\` VARCHAR(64) DEFAULT NULL,
  \`chromebook_sn\` VARCHAR(50) DEFAULT NULL,
  \`picked_up\` ENUM('Yes','No') NOT NULL DEFAULT 'No',
  \`pickup_date\` DATE DEFAULT NULL,
  \`created_at\` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (\`id\`),
  KEY \`idx_tickets_status\` (\`status\`),
  KEY \`idx_tickets_date\` (\`date_reported\`),
  KEY \`idx_tickets_category\` (\`problem_category\`),
  KEY \`idx_tickets_tech\` (\`tech\`),
  KEY \`idx_tickets_sn\` (\`chromebook_sn\`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE \`inventory\` (
  \`id\` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  \`part_name\` VARCHAR(255) NOT NULL,
  \`part_number\` VARCHAR(100) DEFAULT NULL,
  \`quantity\` INT NOT NULL DEFAULT 0,
  \`notes\` TEXT DEFAULT NULL,
  \`created_at\` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (\`id\`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE \`ticket_parts\` (
  \`id\` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  \`ticket_id\` INT UNSIGNED NOT NULL,
  \`part_id\` INT UNSIGNED NOT NULL,
  \`quantity_used\` INT NOT NULL DEFAULT 1,
  \`added_by\` VARCHAR(64) DEFAULT NULL,
  \`added_at\` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (\`id\`),
  UNIQUE KEY \`uq_ticket_part\` (\`ticket_id\`, \`part_id\`),
  KEY \`idx_ticket_parts_part\` (\`part_id\`),
  CONSTRAINT \`fk_ticket_parts_ticket\` FOREIGN KEY (\`ticket_id\`) REFERENCES \`tickets\` (\`id\`) ON DELETE CASCADE,
  CONSTRAINT \`fk_ticket_parts_part\` FOREIGN KEY (\`part_id\`) REFERENCES \`inventory\` (\`id\`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE \`ticket_points\` (
  \`id\` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  \`ticket_id\` INT UNSIGNED NOT NULL,
  \`tech\` VARCHAR(64) NOT NULL,
  \`problem_category\` VARCHAR(50) DEFAULT NULL,
  \`base_points\` INT NOT NULL DEFAULT 0,
  \`awarded_points\` INT NOT NULL DEFAULT 0,
  \`manual_override\` TINYINT(1) NOT NULL DEFAULT 0,
  \`notes\` TEXT DEFAULT NULL,
  \`updated_by\` VARCHAR(64) DEFAULT NULL,
  \`updated_at\` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (\`id\`),
  UNIQUE KEY \`uq_ticket_points_ticket\` (\`ticket_id\`),
  KEY \`idx_ticket_points_tech\` (\`tech\`),
  CONSTRAINT \`fk_ticket_points_ticket\` FOREIGN KEY (\`ticket_id\`) REFERENCES \`tickets\` (\`id\`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE \`students\` (
  \`student_id\` VARCHAR(20) NOT NULL,
  \`first_name\` VARCHAR(100) NOT NULL,
  \`last_name\` VARCHAR(100) NOT NULL,
  \`full_name\` VARCHAR(201) NOT NULL,
  PRIMARY KEY (\`student_id\`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

`;

const ticketCols = ['id', 'first_name', 'last_name', 'school_id', 'class_of', 'school_email', 'additional_info', 'date_reported',
    'problem_category', 'problem_detail', 'custom_detail', 'restarted', 'status', 'notes', 'private_notes', 'priority', 'tech',
    'chromebook_sn', 'picked_up', 'pickup_date', 'created_at'];

const sql = schema
    + '-- ---------------------------------------------------------------- demo data\n'
    + inserts('technicians', sqlTechs, ['id', 'username', 'display_name', 'password_hash', 'active', 'must_change_password', 'created_at']) + '\n'
    + inserts('students', students, ['student_id', 'first_name', 'last_name', 'full_name']) + '\n'
    + inserts('inventory', inventory, ['id', 'part_name', 'part_number', 'quantity', 'notes', 'created_at']) + '\n'
    + inserts('tickets', tickets, ticketCols) + '\n'
    + inserts('ticket_parts', ticket_parts, ['id', 'ticket_id', 'part_id', 'quantity_used', 'added_by', 'added_at']) + '\n'
    + inserts('ticket_points', sqlPoints, ['id', 'ticket_id', 'tech', 'problem_category', 'base_points', 'awarded_points', 'manual_override', 'notes', 'updated_by', 'updated_at']);

mkdirSync(join(root, 'sql'), { recursive: true });
writeFileSync(join(root, 'sql', 'bucit.sql'), sql);

console.log(`technicians=${technicians.length} students=${students.length} inventory=${inventory.length} ` +
    `tickets=${tickets.length} ticket_parts=${ticket_parts.length} ticket_points=${ticket_points.length}`);
