import { DatabaseSync } from 'node:sqlite';
import path from 'node:path';
import fs from 'node:fs';
import crypto from 'node:crypto';

const dataDir = path.join(__dirname, '..', 'data');
if (!fs.existsSync(dataDir)) {
  fs.mkdirSync(dataDir, { recursive: true });
}

const dbPath = path.join(dataDir, 'crm.sqlite3');
export const db = new DatabaseSync(dbPath);

db.exec('PRAGMA journal_mode = WAL;');
db.exec('PRAGMA foreign_keys = ON;');

db.exec(`
CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  username TEXT NOT NULL UNIQUE,
  password_hash TEXT NOT NULL,
  full_name TEXT NOT NULL,
  created_at TEXT DEFAULT (datetime('now', 'localtime'))
);

CREATE TABLE IF NOT EXISTS leads (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  phone TEXT NOT NULL,
  email TEXT DEFAULT '',
  source TEXT DEFAULT 'Walk-in',
  budget_min REAL DEFAULT 0,
  budget_max REAL DEFAULT 0,
  requirement TEXT DEFAULT '',
  property_type TEXT DEFAULT 'Any',
  location_pref TEXT DEFAULT '',
  status TEXT DEFAULT 'New',
  notes TEXT DEFAULT '',
  followup_date TEXT DEFAULT '',
  created_at TEXT DEFAULT (datetime('now', 'localtime')),
  updated_at TEXT DEFAULT (datetime('now', 'localtime'))
);

CREATE TABLE IF NOT EXISTS properties (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  title TEXT NOT NULL,
  type TEXT NOT NULL,
  location TEXT NOT NULL,
  area_sqft REAL DEFAULT 0,
  price REAL NOT NULL,
  status TEXT DEFAULT 'Available',
  description TEXT DEFAULT '',
  bedrooms INTEGER DEFAULT 0,
  bathrooms INTEGER DEFAULT 0,
  listed_date TEXT DEFAULT (date('now', 'localtime')),
  created_at TEXT DEFAULT (datetime('now', 'localtime'))
);

CREATE TABLE IF NOT EXISTS followups (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  lead_id INTEGER NOT NULL,
  followup_date TEXT NOT NULL,
  note TEXT DEFAULT '',
  status TEXT DEFAULT 'Pending',
  created_at TEXT DEFAULT (datetime('now', 'localtime')),
  FOREIGN KEY (lead_id) REFERENCES leads(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS activities (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  type TEXT NOT NULL,
  description TEXT NOT NULL,
  created_at TEXT DEFAULT (datetime('now', 'localtime'))
);
`);

export function hashPassword(password: string): string {
  const salt = crypto.randomBytes(16).toString('hex');
  const hash = crypto.scryptSync(password, salt, 64).toString('hex');
  return `${salt}:${hash}`;
}

export function verifyPassword(password: string, stored: string): boolean {
  const [salt, key] = stored.split(':');
  if (!salt || !key) return false;
  const keyBuf = Buffer.from(key, 'hex');
  const derived = crypto.scryptSync(password, salt, 64);
  return crypto.timingSafeEqual(keyBuf, derived);
}

export function logActivity(type: string, description: string): void {
  const stmt = db.prepare('INSERT INTO activities (type, description) VALUES (?, ?)');
  stmt.run(type, description);
}

// Seed default admin user if none exists
const userCount = db.prepare('SELECT COUNT(*) as cnt FROM users').get() as { cnt: number };
if (userCount.cnt === 0) {
  db.prepare('INSERT INTO users (username, password_hash, full_name) VALUES (?, ?, ?)').run(
    'admin',
    hashPassword('admin123'),
    'CRM Admin'
  );
}
