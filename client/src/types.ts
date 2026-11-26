export interface User {
  id: number;
  username: string;
  fullName: string;
}

export interface Lead {
  id: number;
  name: string;
  phone: string;
  email: string;
  source: string;
  budget_min: number;
  budget_max: number;
  requirement: string;
  property_type: string;
  location_pref: string;
  status: 'New' | 'Contacted' | 'Site Visit' | 'Negotiation' | 'Closed' | 'Lost';
  notes: string;
  followup_date: string;
  created_at: string;
  updated_at: string;
}

export interface Property {
  id: number;
  title: string;
  type: string;
  location: string;
  area_sqft: number;
  price: number;
  status: 'Available' | 'Sold' | 'Rented';
  description: string;
  bedrooms: number;
  bathrooms: number;
  listed_date: string;
  created_at: string;
}

export interface FollowUp {
  id: number;
  lead_id: number;
  followup_date: string;
  note: string;
  status: 'Pending' | 'Done';
  created_at: string;
  lead_name: string;
  lead_phone: string;
  lead_status: string;
  property_type?: string;
  location_pref?: string;
}

export interface Activity {
  id: number;
  type: string;
  description: string;
  created_at: string;
}

export interface DashboardData {
  stats: {
    totalLeads: number;
    activeLeads: number;
    closedLeads: number;
    availableProps: number;
    todayFollowupsCount: number;
    overdueFollowupsCount: number;
  };
  todayFollowups: FollowUp[];
  recentLeads: Lead[];
  recentActivities: Activity[];
  sourceBreakdown: { source: string; count: number }[];
}

export const LEAD_SOURCES = [
  'Walk-in',
  'Phone Enquiry',
  'Website',
  'Referral',
  'Social Media',
  'Newspaper Ad',
  'Hoarding',
  'Other',
];

export const PROPERTY_TYPES = [
  'Apartment',
  'Villa',
  'Independent House',
  'Plot',
  'Commercial',
  'Office Space',
  'Farmhouse',
  'Any',
];

export const LEAD_STATUSES: Lead['status'][] = [
  'New',
  'Contacted',
  'Site Visit',
  'Negotiation',
  'Closed',
  'Lost',
];

export const PROPERTY_STATUSES: Property['status'][] = ['Available', 'Sold', 'Rented'];

export function formatPrice(amount: number): string {
  if (!amount) return '₹0';
  if (amount >= 10000000) return `₹${(amount / 10000000).toFixed(2)} Cr`;
  if (amount >= 100000) return `₹${(amount / 100000).toFixed(2)} L`;
  return `₹${amount.toLocaleString('en-IN')}`;
}

export function exportToCsv(filename: string, rows: Record<string, unknown>[]): void {
  if (!rows.length) return;
  const headers = Object.keys(rows[0]);
  const escape = (val: unknown) => {
    const s = String(val ?? '').replace(/"/g, '""');
    return `"${s}"`;
  };
  const csv = [
    headers.join(','),
    ...rows.map((r) => headers.map((h) => escape(r[h])).join(',')),
  ].join('\n');

  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = filename;
  a.click();
  URL.revokeObjectURL(url);
}
