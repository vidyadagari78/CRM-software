import { Router, Request, Response } from 'express';
import { db, verifyPassword, logActivity } from '../db';
import { requireAuth, signToken, AuthRequest } from '../auth';

const router = Router();

function todayLocal(): string {
  const d = new Date();
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}

// --- AUTH ---
router.post('/auth/login', (req: Request, res: Response) => {
  const { username, password } = req.body as { username?: string; password?: string };
  if (!username || !password) {
    res.status(400).json({ error: 'Username and password are required' });
    return;
  }

  const user = db.prepare('SELECT * FROM users WHERE username = ?').get(username.trim()) as
    | { id: number; username: string; password_hash: string; full_name: string }
    | undefined;

  if (!user || !verifyPassword(password, user.password_hash)) {
    res.status(401).json({ error: 'Invalid username or password' });
    return;
  }

  const token = signToken({
    userId: user.id,
    username: user.username,
    fullName: user.full_name,
  });

  res.json({
    token,
    user: { id: user.id, username: user.username, fullName: user.full_name },
  });
});

router.get('/auth/me', requireAuth, (req: AuthRequest, res: Response) => {
  res.json({ user: req.user });
});

// Protect all routes below
router.use(requireAuth);

// --- DASHBOARD ---
router.get('/dashboard', (_req: Request, res: Response) => {
  const today = todayLocal();

  const totalLeads = (db.prepare('SELECT COUNT(*) as cnt FROM leads').get() as { cnt: number }).cnt;
  const activeLeads = (
    db.prepare("SELECT COUNT(*) as cnt FROM leads WHERE status NOT IN ('Closed', 'Lost')").get() as { cnt: number }
  ).cnt;
  const closedLeads = (
    db.prepare("SELECT COUNT(*) as cnt FROM leads WHERE status = 'Closed'").get() as { cnt: number }
  ).cnt;
  const availableProps = (
    db.prepare("SELECT COUNT(*) as cnt FROM properties WHERE status = 'Available'").get() as { cnt: number }
  ).cnt;
  const todayFollowupsCount = (
    db.prepare("SELECT COUNT(*) as cnt FROM followups WHERE followup_date = ? AND status = 'Pending'").get(today) as {
      cnt: number;
    }
  ).cnt;
  const overdueFollowupsCount = (
    db.prepare("SELECT COUNT(*) as cnt FROM followups WHERE followup_date < ? AND status = 'Pending'").get(today) as {
      cnt: number;
    }
  ).cnt;

  const todayFollowups = db
    .prepare(
      `SELECT f.*, l.name as lead_name, l.phone as lead_phone, l.status as lead_status
       FROM followups f
       JOIN leads l ON l.id = f.lead_id
       WHERE f.followup_date = ? AND f.status = 'Pending'
       ORDER BY f.id DESC LIMIT 10`
    )
    .all(today);

  const recentLeads = db.prepare('SELECT * FROM leads ORDER BY created_at DESC LIMIT 8').all();
  const recentActivities = db.prepare('SELECT * FROM activities ORDER BY created_at DESC LIMIT 10').all();
  const sourceBreakdown = db
    .prepare('SELECT source, COUNT(*) as count FROM leads GROUP BY source ORDER BY count DESC')
    .all();

  res.json({
    stats: {
      totalLeads,
      activeLeads,
      closedLeads,
      availableProps,
      todayFollowupsCount,
      overdueFollowupsCount,
    },
    todayFollowups,
    recentLeads,
    recentActivities,
    sourceBreakdown,
  });
});

// --- LEADS ---
router.get('/leads', (req: Request, res: Response) => {
  const q = String(req.query.q ?? '').trim();
  const status = String(req.query.status ?? '').trim();

  const clauses: string[] = [];
  const params: (string | number)[] = [];

  if (status) {
    clauses.push('status = ?');
    params.push(status);
  }
  if (q) {
    clauses.push('(name LIKE ? OR phone LIKE ? OR location_pref LIKE ?)');
    params.push(`%${q}%`, `%${q}%`, `%${q}%`);
  }

  const where = clauses.length ? `WHERE ${clauses.join(' AND ')}` : '';
  const leads = db.prepare(`SELECT * FROM leads ${where} ORDER BY created_at DESC`).all(...params);
  res.json(leads);
});

router.post('/leads', (req: Request, res: Response) => {
  const {
    name,
    phone,
    email = '',
    source = 'Walk-in',
    budget_min = 0,
    budget_max = 0,
    requirement = '',
    property_type = 'Any',
    location_pref = '',
    status = 'New',
    notes = '',
    followup_date = '',
  } = req.body;

  if (!name?.trim() || !phone?.trim()) {
    res.status(400).json({ error: 'Name and Phone are required' });
    return;
  }

  const result = db
    .prepare(
      `INSERT INTO leads (name, phone, email, source, budget_min, budget_max, requirement, property_type, location_pref, status, notes, followup_date)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`
    )
    .run(
      name.trim(),
      phone.trim(),
      email.trim(),
      source,
      Number(budget_min) || 0,
      Number(budget_max) || 0,
      requirement.trim(),
      property_type,
      location_pref.trim(),
      status,
      notes.trim(),
      followup_date || ''
    );

  const newId = Number(result.lastInsertRowid);

  if (followup_date) {
    db.prepare('INSERT INTO followups (lead_id, followup_date, note, status) VALUES (?, ?, ?, ?)').run(
      newId,
      followup_date,
      'Initial follow-up',
      'Pending'
    );
  }

  logActivity('Lead', `Added new lead: ${name.trim()}`);
  const created = db.prepare('SELECT * FROM leads WHERE id = ?').get(newId);
  res.status(201).json(created);
});

router.put('/leads/:id', (req: Request, res: Response) => {
  const id = Number(req.params.id);
  const existing = db.prepare('SELECT * FROM leads WHERE id = ?').get(id);
  if (!existing) {
    res.status(404).json({ error: 'Lead not found' });
    return;
  }

  const {
    name,
    phone,
    email = '',
    source = 'Walk-in',
    budget_min = 0,
    budget_max = 0,
    requirement = '',
    property_type = 'Any',
    location_pref = '',
    status = 'New',
    notes = '',
    followup_date = '',
  } = req.body;

  if (!name?.trim() || !phone?.trim()) {
    res.status(400).json({ error: 'Name and Phone are required' });
    return;
  }

  db.prepare(
    `UPDATE leads SET
      name = ?, phone = ?, email = ?, source = ?, budget_min = ?, budget_max = ?,
      requirement = ?, property_type = ?, location_pref = ?, status = ?, notes = ?,
      followup_date = ?, updated_at = datetime('now', 'localtime')
     WHERE id = ?`
  ).run(
    name.trim(),
    phone.trim(),
    email.trim(),
    source,
    Number(budget_min) || 0,
    Number(budget_max) || 0,
    requirement.trim(),
    property_type,
    location_pref.trim(),
    status,
    notes.trim(),
    followup_date || '',
    id
  );

  if (followup_date) {
    db.prepare('INSERT INTO followups (lead_id, followup_date, note, status) VALUES (?, ?, ?, ?)').run(
      id,
      followup_date,
      'Scheduled from lead update',
      'Pending'
    );
  }

  logActivity('Lead', `Updated lead: ${name.trim()}`);
  const updated = db.prepare('SELECT * FROM leads WHERE id = ?').get(id);
  res.json(updated);
});

router.delete('/leads/:id', (req: Request, res: Response) => {
  const id = Number(req.params.id);
  const existing = db.prepare('SELECT name FROM leads WHERE id = ?').get(id) as { name: string } | undefined;
  if (!existing) {
    res.status(404).json({ error: 'Lead not found' });
    return;
  }

  db.prepare('DELETE FROM leads WHERE id = ?').run(id);
  logActivity('Lead', `Deleted lead: ${existing.name}`);
  res.json({ success: true });
});

// --- FOLLOW-UPS ---
router.get('/followups', (req: Request, res: Response) => {
  const filter = String(req.query.filter ?? 'today');
  const today = todayLocal();

  let where = "WHERE f.followup_date = ? AND f.status = 'Pending'";
  const params: string[] = [today];

  if (filter === 'overdue') {
    where = "WHERE f.followup_date < ? AND f.status = 'Pending'";
  } else if (filter === 'upcoming') {
    where = "WHERE f.followup_date > ? AND f.status = 'Pending'";
  } else if (filter === 'done') {
    where = "WHERE f.status = 'Done'";
    params.length = 0;
  }

  const items = db
    .prepare(
      `SELECT f.*, l.name as lead_name, l.phone as lead_phone, l.status as lead_status, l.property_type, l.location_pref
       FROM followups f
       JOIN leads l ON l.id = f.lead_id
       ${where}
       ORDER BY f.followup_date ASC, f.id DESC`
    )
    .all(...params);

  const counts = {
    today: (
      db.prepare("SELECT COUNT(*) as cnt FROM followups WHERE followup_date = ? AND status = 'Pending'").get(today) as {
        cnt: number;
      }
    ).cnt,
    overdue: (
      db.prepare("SELECT COUNT(*) as cnt FROM followups WHERE followup_date < ? AND status = 'Pending'").get(today) as {
        cnt: number;
      }
    ).cnt,
    upcoming: (
      db.prepare("SELECT COUNT(*) as cnt FROM followups WHERE followup_date > ? AND status = 'Pending'").get(today) as {
        cnt: number;
      }
    ).cnt,
  };

  res.json({ items, counts });
});

router.post('/followups', (req: Request, res: Response) => {
  const { lead_id, followup_date, note = '' } = req.body;
  if (!lead_id || !followup_date) {
    res.status(400).json({ error: 'Lead and follow-up date are required' });
    return;
  }

  const lead = db.prepare('SELECT name FROM leads WHERE id = ?').get(Number(lead_id)) as { name: string } | undefined;
  if (!lead) {
    res.status(404).json({ error: 'Lead not found' });
    return;
  }

  const result = db
    .prepare('INSERT INTO followups (lead_id, followup_date, note) VALUES (?, ?, ?)')
    .run(Number(lead_id), followup_date, note.trim());

  db.prepare("UPDATE leads SET followup_date = ?, updated_at = datetime('now', 'localtime') WHERE id = ?").run(
    followup_date,
    Number(lead_id)
  );

  logActivity('Follow-up', `Scheduled follow-up for ${lead.name} on ${followup_date}`);
  const created = db.prepare('SELECT * FROM followups WHERE id = ?').get(Number(result.lastInsertRowid));
  res.status(201).json(created);
});

router.patch('/followups/:id/done', (req: Request, res: Response) => {
  const id = Number(req.params.id);
  const existing = db.prepare('SELECT id FROM followups WHERE id = ?').get(id);
  if (!existing) {
    res.status(404).json({ error: 'Follow-up not found' });
    return;
  }

  db.prepare("UPDATE followups SET status = 'Done' WHERE id = ?").run(id);
  logActivity('Follow-up', `Marked follow-up #${id} as done`);
  res.json({ success: true });
});

router.delete('/followups/:id', (req: Request, res: Response) => {
  const id = Number(req.params.id);
  db.prepare('DELETE FROM followups WHERE id = ?').run(id);
  logActivity('Follow-up', `Deleted follow-up #${id}`);
  res.json({ success: true });
});

// --- PROPERTIES ---
router.get('/properties', (req: Request, res: Response) => {
  const q = String(req.query.q ?? '').trim();
  const type = String(req.query.type ?? '').trim();
  const status = String(req.query.status ?? '').trim();

  const clauses: string[] = [];
  const params: string[] = [];

  if (type) {
    clauses.push('type = ?');
    params.push(type);
  }
  if (status) {
    clauses.push('status = ?');
    params.push(status);
  }
  if (q) {
    clauses.push('(title LIKE ? OR location LIKE ?)');
    params.push(`%${q}%`, `%${q}%`);
  }

  const where = clauses.length ? `WHERE ${clauses.join(' AND ')}` : '';
  const items = db.prepare(`SELECT * FROM properties ${where} ORDER BY created_at DESC`).all(...params);
  res.json(items);
});

router.post('/properties', (req: Request, res: Response) => {
  const {
    title,
    type = 'Apartment',
    location,
    area_sqft = 0,
    price,
    status = 'Available',
    bedrooms = 0,
    bathrooms = 0,
    description = '',
  } = req.body;

  if (!title?.trim() || !location?.trim() || !Number(price)) {
    res.status(400).json({ error: 'Title, Location and Price are required' });
    return;
  }

  const result = db
    .prepare(
      `INSERT INTO properties (title, type, location, area_sqft, price, status, bedrooms, bathrooms, description)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)`
    )
    .run(
      title.trim(),
      type,
      location.trim(),
      Number(area_sqft) || 0,
      Number(price),
      status,
      Number(bedrooms) || 0,
      Number(bathrooms) || 0,
      description.trim()
    );

  logActivity('Property', `Added property: ${title.trim()}`);
  const created = db.prepare('SELECT * FROM properties WHERE id = ?').get(Number(result.lastInsertRowid));
  res.status(201).json(created);
});

router.put('/properties/:id', (req: Request, res: Response) => {
  const id = Number(req.params.id);
  const existing = db.prepare('SELECT id FROM properties WHERE id = ?').get(id);
  if (!existing) {
    res.status(404).json({ error: 'Property not found' });
    return;
  }

  const {
    title,
    type = 'Apartment',
    location,
    area_sqft = 0,
    price,
    status = 'Available',
    bedrooms = 0,
    bathrooms = 0,
    description = '',
  } = req.body;

  if (!title?.trim() || !location?.trim() || !Number(price)) {
    res.status(400).json({ error: 'Title, Location and Price are required' });
    return;
  }

  db.prepare(
    `UPDATE properties SET title = ?, type = ?, location = ?, area_sqft = ?, price = ?, status = ?, bedrooms = ?, bathrooms = ?, description = ?
     WHERE id = ?`
  ).run(
    title.trim(),
    type,
    location.trim(),
    Number(area_sqft) || 0,
    Number(price),
    status,
    Number(bedrooms) || 0,
    Number(bathrooms) || 0,
    description.trim(),
    id
  );

  logActivity('Property', `Updated property: ${title.trim()}`);
  const updated = db.prepare('SELECT * FROM properties WHERE id = ?').get(id);
  res.json(updated);
});

router.delete('/properties/:id', (req: Request, res: Response) => {
  const id = Number(req.params.id);
  const existing = db.prepare('SELECT title FROM properties WHERE id = ?').get(id) as { title: string } | undefined;
  if (!existing) {
    res.status(404).json({ error: 'Property not found' });
    return;
  }

  db.prepare('DELETE FROM properties WHERE id = ?').run(id);
  logActivity('Property', `Deleted property: ${existing.title}`);
  res.json({ success: true });
});

// --- ACTIVITIES ---
router.get('/activities', (_req: Request, res: Response) => {
  const items = db.prepare('SELECT * FROM activities ORDER BY created_at DESC LIMIT 100').all();
  res.json(items);
});

export default router;
