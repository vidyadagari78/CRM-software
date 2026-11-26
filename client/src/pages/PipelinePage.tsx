import React, { useEffect, useState } from 'react';
import { ChevronLeft, ChevronRight, Phone } from 'lucide-react';
import { Layout } from '../components/Layout';
import { apiFetch } from '../api/client';
import { Lead, LEAD_STATUSES, formatPrice } from '../types';

export const PipelinePage: React.FC = () => {
  const [leads, setLeads] = useState<Lead[]>([]);
  const [msg, setMsg] = useState('');
  const [error, setError] = useState('');

  const load = async () => {
    const items = await apiFetch<Lead[]>('/api/leads');
    setLeads(items);
  };

  useEffect(() => {
    load().catch((e) => setError(e.message));
  }, []);

  const moveStage = async (lead: Lead, newStatus: Lead['status']) => {
    setError('');
    setMsg('');
    try {
      await apiFetch(`/api/leads/${lead.id}/status`, {
        method: 'PATCH',
        body: JSON.stringify({ status: newStatus }),
      });
      setMsg(`Moved ${lead.name} to "${newStatus}".`);
      await load();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to update stage');
    }
  };

  return (
    <Layout title="Deal Pipeline">
      {msg && <div className="alert alert-success">{msg}</div>}
      {error && <div className="alert alert-error">{error}</div>}

      <div className="kanban-board">
        {LEAD_STATUSES.map((stage, stageIdx) => {
          const stageLeads = leads.filter((l) => l.status === stage);
          const totalMaxBudget = stageLeads.reduce(
            (sum, l) => sum + (l.budget_max || l.budget_min || 0),
            0
          );

          return (
            <div key={stage} className="kanban-col">
              <div className="kanban-col-head">
                <div>
                  <span className={`badge badge-${stage}`}>{stage}</span>
                  <span className="kanban-cnt">{stageLeads.length}</span>
                </div>
                {totalMaxBudget > 0 && (
                  <div className="kanban-val">{formatPrice(totalMaxBudget)}</div>
                )}
              </div>

              <div className="kanban-cards">
                {stageLeads.length === 0 ? (
                  <div className="kanban-empty">No leads</div>
                ) : (
                  stageLeads.map((lead) => (
                    <div key={lead.id} className="kanban-card">
                      <div className="kanban-card-title">{lead.name}</div>
                      <div className="kanban-card-meta">
                        {lead.property_type}
                        {lead.location_pref ? ` · ${lead.location_pref}` : ''}
                      </div>
                      {(lead.budget_min > 0 || lead.budget_max > 0) && (
                        <div className="kanban-card-budget">
                          {formatPrice(lead.budget_min)} – {formatPrice(lead.budget_max)}
                        </div>
                      )}
                      <div className="kanban-card-foot">
                        <a href={`tel:${lead.phone}`} className="phone-link" style={{ fontSize: '0.74rem', display: 'inline-flex', alignItems: 'center', gap: 3 }}>
                          <Phone size={12} /> {lead.phone}
                        </a>
                        <div className="kanban-move-btns">
                          {stageIdx > 0 && (
                            <button
                              className="icon-btn"
                              title={`Move back to ${LEAD_STATUSES[stageIdx - 1]}`}
                              onClick={() => moveStage(lead, LEAD_STATUSES[stageIdx - 1])}
                            >
                              <ChevronLeft size={14} />
                            </button>
                          )}
                          {stageIdx < LEAD_STATUSES.length - 1 && (
                            <button
                              className="icon-btn"
                              title={`Advance to ${LEAD_STATUSES[stageIdx + 1]}`}
                              onClick={() => moveStage(lead, LEAD_STATUSES[stageIdx + 1])}
                            >
                              <ChevronRight size={14} />
                            </button>
                          )}
                        </div>
                      </div>
                    </div>
                  ))
                )}
              </div>
            </div>
          );
        })}
      </div>
    </Layout>
  );
};
