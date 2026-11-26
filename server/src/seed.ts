import { db, logActivity } from './db';

function offsetDate(days: number): string {
  const d = new Date();
  d.setDate(d.getDate() + days);
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}

const today = offsetDate(0);
const yesterday = offsetDate(-1);
const twoDaysAgo = offsetDate(-2);
const tomorrow = offsetDate(1);
const nextWeek = offsetDate(5);

const existingLeads = (db.prepare('SELECT COUNT(*) as cnt FROM leads').get() as { cnt: number }).cnt;
if (existingLeads > 0) {
  console.log(`Database already has ${existingLeads} leads. Clearing sample tables before re-seeding...`);
  db.exec('DELETE FROM followups; DELETE FROM leads; DELETE FROM properties; DELETE FROM activities;');
}

const sampleLeads = [
  {
    name: 'Rahul Menon',
    phone: '+91 9847011223',
    email: 'rahul.menon@example.com',
    source: 'Website',
    budget_min: 4500000,
    budget_max: 6500000,
    requirement: '3BHK ready-to-move apartment near IT park',
    property_type: 'Apartment',
    location_pref: 'Kakkanad, Kochi',
    status: 'Site Visit',
    notes: 'Prefers east-facing unit with covered parking',
    followup_date: today,
  },
  {
    name: 'Anjali Nair',
    phone: '+91 9633445566',
    email: 'anjali.nair@example.com',
    source: 'Referral',
    budget_min: 8000000,
    budget_max: 12000000,
    requirement: '4BHK independent villa in gated community',
    property_type: 'Villa',
    location_pref: 'Edappally, Ernakulam',
    status: 'Negotiation',
    notes: 'Second family visit completed; discussing payment schedule',
    followup_date: today,
  },
  {
    name: 'Suresh Kumar',
    phone: '+91 9447123456',
    email: 'suresh.k@example.com',
    source: 'Phone Enquiry',
    budget_min: 2500000,
    budget_max: 3800000,
    requirement: '8-10 cents residential plot with road access',
    property_type: 'Plot',
    location_pref: 'North Paravur',
    status: 'Contacted',
    notes: 'Needs clear title deed and bank loan eligibility',
    followup_date: yesterday,
  },
  {
    name: 'Meera Krishnan',
    phone: '+91 9947889900',
    email: 'meera.krishnan@example.com',
    source: 'Social Media',
    budget_min: 3500000,
    budget_max: 5000000,
    requirement: '2BHK flat for investment / rental income',
    property_type: 'Apartment',
    location_pref: 'Aluva',
    status: 'New',
    notes: 'Enquired via Instagram ad',
    followup_date: tomorrow,
  },
  {
    name: 'Deepak Varghese',
    phone: '+91 9895223344',
    email: 'deepak.v@example.com',
    source: 'Walk-in',
    budget_min: 15000000,
    budget_max: 22000000,
    requirement: 'Commercial showroom space on main road',
    property_type: 'Commercial',
    location_pref: 'MG Road, Ernakulam',
    status: 'Closed',
    notes: 'Deal closed and advance token received',
    followup_date: '',
  },
  {
    name: 'Lakshmi Pillai',
    phone: '+91 9745667788',
    email: 'lakshmi.p@example.com',
    source: 'Newspaper Ad',
    budget_min: 5500000,
    budget_max: 7500000,
    requirement: '3BHK independent house near school',
    property_type: 'Independent House',
    location_pref: 'Tripunithura',
    status: 'Site Visit',
    notes: 'Weekend visit scheduled with spouse',
    followup_date: nextWeek,
  },
  {
    name: 'Arun George',
    phone: '+91 9567001122',
    email: 'arun.g@example.com',
    source: 'Website',
    budget_min: 2800000,
    budget_max: 3500000,
    requirement: '1200 sq.ft office space',
    property_type: 'Office Space',
    location_pref: 'Palarivattom',
    status: 'Lost',
    notes: 'Client postponed purchase for 6 months',
    followup_date: '',
  },
  {
    name: 'Nisha Thomas',
    phone: '+91 9846334455',
    email: 'nisha.t@example.com',
    source: 'Referral',
    budget_min: 6000000,
    budget_max: 8500000,
    requirement: '3BHK waterfront apartment',
    property_type: 'Apartment',
    location_pref: 'Marine Drive, Kochi',
    status: 'Contacted',
    notes: 'Sent brochure and floor plans on WhatsApp',
    followup_date: twoDaysAgo,
  },
];

const insertLead = db.prepare(`
  INSERT INTO leads (name, phone, email, source, budget_min, budget_max, requirement, property_type, location_pref, status, notes, followup_date)
  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
`);

const leadIds: number[] = [];
for (const l of sampleLeads) {
  const res = insertLead.run(
    l.name,
    l.phone,
    l.email,
    l.source,
    l.budget_min,
    l.budget_max,
    l.requirement,
    l.property_type,
    l.location_pref,
    l.status,
    l.notes,
    l.followup_date
  );
  leadIds.push(Number(res.lastInsertRowid));
  logActivity('Lead', `Added new lead: ${l.name}`);
}

const sampleProperties = [
  {
    title: 'Skyline Crest 3BHK Apartment',
    type: 'Apartment',
    location: 'Kakkanad, Kochi',
    area_sqft: 1650,
    price: 6200000,
    status: 'Available',
    bedrooms: 3,
    bathrooms: 3,
    description: 'Semi-furnished 3BHK with clubhouse, pool, and 24/7 security near Infopark.',
  },
  {
    title: 'Greenwood Luxury Villa',
    type: 'Villa',
    location: 'Edappally, Ernakulam',
    area_sqft: 2800,
    price: 11500000,
    status: 'Available',
    bedrooms: 4,
    bathrooms: 4,
    description: 'Gated community villa with landscaped garden and 2-car covered garage.',
  },
  {
    title: 'Main Road Commercial Corner Plot',
    type: 'Plot',
    location: 'North Paravur, Ernakulam',
    area_sqft: 4356,
    price: 3600000,
    status: 'Available',
    bedrooms: 0,
    bathrooms: 0,
    description: '10 cents level dry land with 20ft tarred road frontage.',
  },
  {
    title: 'Metro Square Showroom Unit',
    type: 'Commercial',
    location: 'MG Road, Ernakulam',
    area_sqft: 2200,
    price: 18500000,
    status: 'Sold',
    bedrooms: 0,
    bathrooms: 2,
    description: 'Ground floor prime retail frontage with dedicated customer parking.',
  },
  {
    title: 'Riverview 2BHK Flat',
    type: 'Apartment',
    location: 'Aluva, Ernakulam',
    area_sqft: 1120,
    price: 4200000,
    status: 'Rented',
    bedrooms: 2,
    bathrooms: 2,
    description: 'Walkable distance to Aluva Metro Station, currently yielding ₹18,000/month.',
  },
  {
    title: 'Heritage Courtyard House',
    type: 'Independent House',
    location: 'Tripunithura, Kochi',
    area_sqft: 2100,
    price: 7200000,
    status: 'Available',
    bedrooms: 3,
    bathrooms: 3,
    description: 'Newly renovated independent house in quiet residential lane.',
  },
];

const insertProp = db.prepare(`
  INSERT INTO properties (title, type, location, area_sqft, price, status, bedrooms, bathrooms, description)
  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
`);

for (const p of sampleProperties) {
  insertProp.run(
    p.title,
    p.type,
    p.location,
    p.area_sqft,
    p.price,
    p.status,
    p.bedrooms,
    p.bathrooms,
    p.description
  );
  logActivity('Property', `Added property: ${p.title}`);
}

const insertFu = db.prepare(`
  INSERT INTO followups (lead_id, followup_date, note, status)
  VALUES (?, ?, ?, ?)
`);

const sampleFollowups = [
  { leadIdx: 0, date: today, note: 'Confirm evening site visit at Skyline Crest', status: 'Pending' },
  { leadIdx: 1, date: today, note: 'Send final price breakdown for Greenwood Villa', status: 'Pending' },
  { leadIdx: 2, date: yesterday, note: 'Share plot survey sketch & tax receipt', status: 'Pending' },
  { leadIdx: 7, date: twoDaysAgo, note: 'Call to check feedback on Marine Drive brochure', status: 'Pending' },
  { leadIdx: 3, date: tomorrow, note: 'Intro call and budget qualification', status: 'Pending' },
  { leadIdx: 5, date: nextWeek, note: 'Schedule Tripunithura house tour', status: 'Pending' },
  { leadIdx: 4, date: twoDaysAgo, note: 'Collect booking cheque', status: 'Done' },
];

for (const f of sampleFollowups) {
  insertFu.run(leadIds[f.leadIdx], f.date, f.note, f.status);
  logActivity('Follow-up', `Scheduled follow-up for ${sampleLeads[f.leadIdx].name} on ${f.date}`);
}

console.log(`Seeded ${sampleLeads.length} leads, ${sampleProperties.length} properties, and ${sampleFollowups.length} follow-ups.`);
