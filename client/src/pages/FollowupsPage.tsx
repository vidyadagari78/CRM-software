import React, { useEffect, useState } from 'react';
import { Plus, X, Check } from 'lucide-react';
import { Layout } from '../components/Layout';
import { apiFetch } from '../api/client';
import { FollowUp, Lead } from '../types';

export const FollowupsPage: React.FC = () => {
  const [filter, setFilter] = useState<'today' | 'overdue' | 'upcoming' | 'done'>('today');
  const [items, setItems] = useState<FollowUp[]>([]);
  const [counts, setCounts] = useState({ today: 0, overdue: 0, upcoming: 0 });
  const [leads, setLeads] = useState<Lead[]>([]);
  const [formOpen, setFormOpen] = useState(false);
  const [leadId, setLeadId] = useState('');
  const [followupDate, setFollowupDate] = useState(
    new Date().toISOString().slice(0, 10)
  );
  const [note, setNote] = useState('');
  const [msg, setMsg] = useState('');
  const [error, setError] = useState('');

  const loadData = async (currentFilter = filter) => {
    const res = await apiFetch<{
      items: FollowUp[];
      counts: { today: number; overdue: number; upcoming: number };
    }>(`/api/followups?filter=${currentFilter}`);
    setItems(res.items);
    setCounts(res.counts);
  };

  useEffect(() => {
    loadData(filter).catch((e) => setError(e.message));
  }, [filter]);

  useEffect(() => {
    apiFetch<Lead[]>('/api/leads').then(setLeads).catch(() => {});
  }, []);

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setMsg('');
    try {
      await apiFetch('/api/followups', {
        method: 'POST',
        body: JSON.stringify({
          lead_id: Number(leadId),
          followup_date: followupDate,
          note,
        }),
      });
      setMsg('Follow-up scheduled.');
      setFormOpen(false);
      setNote('');
      await loadData(filter);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to schedule');
    }
  };

  const markDone = async (id: number) => {
    try {
      await apiFetch(`/api/followups/${id}/done`, { method: 'PATCH' });
      setMsg('Follow-up marked as done.');
      await loadData(filter);
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to update');
    }
  };

  const today = new Date().toISOString().slice(0, 10);

  return (
    <Layout title="Follow-ups" todayFollowupsBadge={counts.today}>
      {msg && <div className="alert alert-success">{msg}</div>}
      {error && <div className="alert alert-error">{error}</div>}

      <div className="filter-tabs">
        <button
          className={`tab ${filter === 'today' ? 'active' : ''}`}
          onClick={() => setFilter('today')}
        >
          Today <span className="tab-cnt">{counts.today}</span>
        </button>
        <button
          className={`tab ${filter === 'overdue' ? 'active' : ''}`}
          onClick={() => setFilter('overdue')}
        >
          Overdue <span className="tab-cnt">{counts.overdue}</span>
        </button>
        <button
          className={`tab ${filter === 'upcoming' ? 'active' : ''}`}
          onClick={() => setFilter('upcoming')}
        >
          Upcoming <span className="tab-cnt">{counts.upcoming}</span>
        </button>
        <button
          className={`tab ${filter === 'done' ? 'active' : ''}`}
          onClick={() => setFilter('done')}
        >
          Done
        </button>
        <button
          className="btn btn-primary"
          style={{ marginLeft: 'auto' }}
          onClick={() => setFormOpen((v) => !v)}
        >
          <Plus size={16} /> Add Follow-up
        </button>
      </div>

      {formOpen && (
        <div className="form-panel">
          <div className="form-panel-head">
            <span>Schedule a Follow-up</span>
            <button className="close-btn" onClick={() => setFormOpen(false)}>
              <X size={18} />
            </button>
          </div>
          <form onSubmit={handleCreate} className="crm-form">
            <div className="form-row">
              <div className="form-group">
                <label>Lead *</label>
                <select
                  required
                  value={leadId}
                  onChange={(e) => setLeadId(e.target.value)}
                >
                  <option value="">Select a lead...</option>
                  {leads.map((l) => (
                    <option key={l.id} value={l.id}>
                      {l.name} — {l.phone}
                    </option>
                  ))}
                </select>
              </div>
              <div className="form-group">
                <label>Follow-up Date *</label>
                <input
                  type="date"
                  required
                  value={followupDate}
                  onChange={(e) => setFollowupDate(e.target.value)}
                />
              </div>
              <div className="form-group">
                <label>Note</label>
                <input
                  value={note}
                  onChange={(e) => setNote(e.target.value)}
                  placeholder="Call reminder / site visit note"
                />
              </div>
            </div>
            <div className="form-actions">
              <button type="submit" className="btn btn-primary">
                Save Follow-up
              </button>
            </div>
          </form>
        </div>
      )}

      <div className="data-card">
        <div className="data-card-head">
          <span>{items.length} Follow-up(s)</span>
        </div>
        {items.length === 0 ? (
          <div className="empty-state">No follow-ups in this category.</div>
        ) : (
          items.map((fu) => {
            const isOverdue = fu.followup_date < today && fu.status === 'Pending';
            const d = new Date(fu.followup_date + 'T00:00:00');
            return (
              <div
                key={fu.id}
                className={`fu-card ${isOverdue ? 'fu-overdue' : ''}`}
              >
                <div className="fu-date">
                  <div className="fu-day">{d.getDate()}</div>
                  <div className="fu-month">
                    {d.toLocaleString('en-US', { month: 'short' })}
                  </div>
                </div>
                <div style={{ flex: 1 }}>
                  <div style={{ fontWeight: 700 }}>{fu.lead_name}</div>
                  <div style={{ fontSize: '0.78rem', color: '#64748b' }}>
                    <a href={`tel:${fu.lead_phone}`} className="phone-link">
                      {fu.lead_phone}
                    </a>{' '}
                    · {fu.property_type || 'Any'}
                    {fu.location_pref ? ` · ${fu.location_pref}` : ''}
                  </div>
                  {fu.note && (
                    <div style={{ fontSize: '0.76rem', color: '#64748b', marginTop: 2 }}>
                      Note: {fu.note}
                    </div>
                  )}
                </div>
                <div className="item-actions">
                  <span className={`badge badge-${fu.lead_status}`}>
                    {fu.lead_status}
                  </span>
                  {fu.status === 'Pending' && (
                    <button
                      className="btn btn-xs btn-success"
                      onClick={() => markDone(fu.id)}
                    >
                      <Check size={14} /> Done
                    </button>
                  )}
                </div>
              </div>
            );
          })
        )}
      </div>
    </Layout>
  );
};
