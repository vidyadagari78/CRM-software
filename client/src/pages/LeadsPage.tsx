import React, { useEffect, useState } from 'react';
import { Plus, X } from 'lucide-react';
import { Layout } from '../components/Layout';
import { apiFetch } from '../api/client';
import {
  Lead,
  LEAD_SOURCES,
  LEAD_STATUSES,
  PROPERTY_TYPES,
  formatPrice,
} from '../types';

const emptyForm = {
  name: '',
  phone: '',
  email: '',
  source: 'Walk-in',
  property_type: 'Any',
  status: 'New' as Lead['status'],
  budget_min: '',
  budget_max: '',
  location_pref: '',
  requirement: '',
  followup_date: '',
  notes: '',
};

export const LeadsPage: React.FC = () => {
  const [leads, setLeads] = useState<Lead[]>([]);
  const [q, setQ] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [formOpen, setFormOpen] = useState(false);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [form, setForm] = useState(emptyForm);
  const [msg, setMsg] = useState('');
  const [error, setError] = useState('');

  const loadLeads = async (search = q, status = statusFilter) => {
    const params = new URLSearchParams();
    if (search) params.set('q', search);
    if (status) params.set('status', status);
    const items = await apiFetch<Lead[]>(`/api/leads?${params.toString()}`);
    setLeads(items);
  };

  useEffect(() => {
    loadLeads().catch((e) => setError(e.message));
  }, []);

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    loadLeads(q, statusFilter).catch((err) => setError(err.message));
  };

  const openNew = () => {
    setEditingId(null);
    setForm(emptyForm);
    setFormOpen(true);
  };

  const openEdit = (lead: Lead) => {
    setEditingId(lead.id);
    setForm({
      name: lead.name,
      phone: lead.phone,
      email: lead.email || '',
      source: lead.source || 'Walk-in',
      property_type: lead.property_type || 'Any',
      status: lead.status,
      budget_min: lead.budget_min ? String(lead.budget_min) : '',
      budget_max: lead.budget_max ? String(lead.budget_max) : '',
      location_pref: lead.location_pref || '',
      requirement: lead.requirement || '',
      followup_date: lead.followup_date || '',
      notes: lead.notes || '',
    });
    setFormOpen(true);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setMsg('');
    try {
      const payload = {
        ...form,
        budget_min: Number(form.budget_min) || 0,
        budget_max: Number(form.budget_max) || 0,
      };
      if (editingId) {
        await apiFetch(`/api/leads/${editingId}`, {
          method: 'PUT',
          body: JSON.stringify(payload),
        });
        setMsg('Lead updated successfully.');
      } else {
        await apiFetch('/api/leads', {
          method: 'POST',
          body: JSON.stringify(payload),
        });
        setMsg('Lead added successfully.');
      }
      setFormOpen(false);
      setEditingId(null);
      setForm(emptyForm);
      await loadLeads();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Save failed');
    }
  };

  const handleDelete = async (id: number) => {
    if (!window.confirm('Delete this lead?')) return;
    try {
      await apiFetch(`/api/leads/${id}`, { method: 'DELETE' });
      setMsg('Lead deleted.');
      await loadLeads();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Delete failed');
    }
  };

  return (
    <Layout title="Leads">
      {msg && <div className="alert alert-success">{msg}</div>}
      {error && <div className="alert alert-error">{error}</div>}

      <div className="page-toolbar">
        <form className="toolbar-search" onSubmit={handleSearch}>
          <input
            type="text"
            value={q}
            onChange={(e) => setQ(e.target.value)}
            placeholder="Search name, phone, location..."
          />
          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
          >
            <option value="">All Statuses</option>
            {LEAD_STATUSES.map((s) => (
              <option key={s} value={s}>{s}</option>
            ))}
          </select>
          <button type="submit" className="btn btn-secondary">Search</button>
          {(q || statusFilter) && (
            <button
              type="button"
              className="btn btn-ghost"
              onClick={() => {
                setQ('');
                setStatusFilter('');
                loadLeads('', '');
              }}
            >
              Clear
            </button>
          )}
        </form>
        <button className="btn btn-primary" onClick={openNew}>
          <Plus size={16} /> Add Lead
        </button>
      </div>

      {formOpen && (
        <div className="form-panel">
          <div className="form-panel-head">
            <span>{editingId ? 'Edit Lead' : 'Add New Lead'}</span>
            <button className="close-btn" onClick={() => setFormOpen(false)}>
              <X size={18} />
            </button>
          </div>
          <form onSubmit={handleSubmit} className="crm-form">
            <div className="form-row">
              <div className="form-group">
                <label>Name *</label>
                <input
                  required
                  value={form.name}
                  onChange={(e) => setForm({ ...form, name: e.target.value })}
                  placeholder="Client full name"
                />
              </div>
              <div className="form-group">
                <label>Phone *</label>
                <input
                  required
                  value={form.phone}
                  onChange={(e) => setForm({ ...form, phone: e.target.value })}
                  placeholder="+91 XXXXX XXXXX"
                />
              </div>
              <div className="form-group">
                <label>Email</label>
                <input
                  type="email"
                  value={form.email}
                  onChange={(e) => setForm({ ...form, email: e.target.value })}
                  placeholder="optional@email.com"
                />
              </div>
            </div>

            <div className="form-row">
              <div className="form-group">
                <label>Source</label>
                <select
                  value={form.source}
                  onChange={(e) => setForm({ ...form, source: e.target.value })}
                >
                  {LEAD_SOURCES.map((s) => (
                    <option key={s} value={s}>{s}</option>
                  ))}
                </select>
              </div>
              <div className="form-group">
                <label>Property Type</label>
                <select
                  value={form.property_type}
                  onChange={(e) =>
                    setForm({ ...form, property_type: e.target.value })
                  }
                >
                  {PROPERTY_TYPES.map((t) => (
                    <option key={t} value={t}>{t}</option>
                  ))}
                </select>
              </div>
              <div className="form-group">
                <label>Status</label>
                <select
                  value={form.status}
                  onChange={(e) =>
                    setForm({ ...form, status: e.target.value as Lead['status'] })
                  }
                >
                  {LEAD_STATUSES.map((s) => (
                    <option key={s} value={s}>{s}</option>
                  ))}
                </select>
              </div>
            </div>

            <div className="form-row">
              <div className="form-group">
                <label>Budget Min (₹)</label>
                <input
                  type="number"
                  value={form.budget_min}
                  onChange={(e) =>
                    setForm({ ...form, budget_min: e.target.value })
                  }
                  placeholder="2000000"
                />
              </div>
              <div className="form-group">
                <label>Budget Max (₹)</label>
                <input
                  type="number"
                  value={form.budget_max}
                  onChange={(e) =>
                    setForm({ ...form, budget_max: e.target.value })
                  }
                  placeholder="5000000"
                />
              </div>
              <div className="form-group">
                <label>Preferred Location</label>
                <input
                  value={form.location_pref}
                  onChange={(e) =>
                    setForm({ ...form, location_pref: e.target.value })
                  }
                  placeholder="Area / City"
                />
              </div>
            </div>

            <div className="form-row">
              <div className="form-group">
                <label>Requirement</label>
                <input
                  value={form.requirement}
                  onChange={(e) =>
                    setForm({ ...form, requirement: e.target.value })
                  }
                  placeholder="e.g. 3BHK near metro"
                />
              </div>
              <div className="form-group">
                <label>Next Follow-up Date</label>
                <input
                  type="date"
                  value={form.followup_date}
                  onChange={(e) =>
                    setForm({ ...form, followup_date: e.target.value })
                  }
                />
              </div>
            </div>

            <div className="form-group">
              <label>Notes</label>
              <textarea
                rows={2}
                value={form.notes}
                onChange={(e) => setForm({ ...form, notes: e.target.value })}
                placeholder="Any remarks..."
              />
            </div>

            <div className="form-actions">
              <button type="submit" className="btn btn-primary">
                {editingId ? 'Update Lead' : 'Save Lead'}
              </button>
              <button
                type="button"
                className="btn btn-ghost"
                onClick={() => setFormOpen(false)}
              >
                Cancel
              </button>
            </div>
          </form>
        </div>
      )}

      <div className="data-card">
        <div className="data-card-head">
          <span>{leads.length} Lead(s) found</span>
        </div>
        {leads.length === 0 ? (
          <div className="empty-state">No leads yet. Click &quot;+ Add Lead&quot; to get started.</div>
        ) : (
          <div className="table-scroll">
            <table className="crm-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Name</th>
                  <th>Phone</th>
                  <th>Type</th>
                  <th>Budget</th>
                  <th>Location</th>
                  <th>Status</th>
                  <th>Follow-up</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {leads.map((lead, idx) => (
                  <tr key={lead.id}>
                    <td>{idx + 1}</td>
                    <td>
                      <div style={{ fontWeight: 600 }}>{lead.name}</div>
                      {lead.requirement && (
                        <div style={{ fontSize: '0.74rem', color: '#64748b' }}>
                          {lead.requirement}
                        </div>
                      )}
                    </td>
                    <td>
                      <a href={`tel:${lead.phone}`} className="phone-link">
                        {lead.phone}
                      </a>
                      <a
                        href={`https://wa.me/${lead.phone.replace(/[^0-9]/g, '')}`}
                        target="_blank"
                        rel="noreferrer"
                        className="wa-link"
                      >
                        WA
                      </a>
                    </td>
                    <td>{lead.property_type}</td>
                    <td>
                      {lead.budget_min || lead.budget_max
                        ? `${formatPrice(lead.budget_min)} – ${formatPrice(lead.budget_max)}`
                        : '—'}
                    </td>
                    <td>{lead.location_pref || '—'}</td>
                    <td>
                      <span className={`badge badge-${lead.status}`}>
                        {lead.status}
                      </span>
                    </td>
                    <td>{lead.followup_date || '—'}</td>
                    <td className="td-actions">
                      <button
                        className="btn btn-xs btn-outline"
                        onClick={() => openEdit(lead)}
                      >
                        Edit
                      </button>
                      <button
                        className="btn btn-xs btn-danger"
                        onClick={() => handleDelete(lead.id)}
                      >
                        Del
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </Layout>
  );
};
