import React, { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { Users, Clock, CheckCircle2, Home, Bell, AlertCircle } from 'lucide-react';
import { Layout } from '../components/Layout';
import { apiFetch } from '../api/client';
import { DashboardData } from '../types';

export const DashboardPage: React.FC = () => {
  const [data, setData] = useState<DashboardData | null>(null);
  const [error, setError] = useState('');

  useEffect(() => {
    apiFetch<DashboardData>('/api/dashboard')
      .then(setData)
      .catch((e) => setError(e.message));
  }, []);

  const stats = data?.stats;

  return (
    <Layout
      title="Dashboard"
      todayFollowupsBadge={stats?.todayFollowupsCount ?? 0}
    >
      {error && <div className="alert alert-error">{error}</div>}

      <div className="stats-grid">
        <div className="stat-card stat-blue">
          <div className="stat-icon"><Users size={24} /></div>
          <div>
            <div className="stat-num">{stats?.totalLeads ?? 0}</div>
            <div className="stat-label">Total Leads</div>
          </div>
        </div>
        <div className="stat-card stat-orange">
          <div className="stat-icon"><Clock size={24} /></div>
          <div>
            <div className="stat-num">{stats?.activeLeads ?? 0}</div>
            <div className="stat-label">Active Leads</div>
          </div>
        </div>
        <div className="stat-card stat-green">
          <div className="stat-icon"><CheckCircle2 size={24} /></div>
          <div>
            <div className="stat-num">{stats?.closedLeads ?? 0}</div>
            <div className="stat-label">Closed Deals</div>
          </div>
        </div>
        <div className="stat-card stat-purple">
          <div className="stat-icon"><Home size={24} /></div>
          <div>
            <div className="stat-num">{stats?.availableProps ?? 0}</div>
            <div className="stat-label">Available Properties</div>
          </div>
        </div>
        <div className={`stat-card ${(stats?.todayFollowupsCount ?? 0) > 0 ? 'stat-red' : ''}`}>
          <div className="stat-icon"><Bell size={24} /></div>
          <div>
            <div className="stat-num">{stats?.todayFollowupsCount ?? 0}</div>
            <div className="stat-label">Today&apos;s Follow-ups</div>
          </div>
        </div>
        <div className={`stat-card ${(stats?.overdueFollowupsCount ?? 0) > 0 ? 'stat-red' : ''}`}>
          <div className="stat-icon"><AlertCircle size={24} /></div>
          <div>
            <div className="stat-num">{stats?.overdueFollowupsCount ?? 0}</div>
            <div className="stat-label">Overdue Follow-ups</div>
          </div>
        </div>
      </div>

      <div className="dash-grid">
        <div className="dash-panel">
          <div className="panel-head">
            <span>Today&apos;s Follow-ups</span>
            <Link to="/followups" className="panel-link">View All</Link>
          </div>
          {!data?.todayFollowups.length ? (
            <div className="empty-state">No follow-ups due today.</div>
          ) : (
            data.todayFollowups.map((fu) => (
              <div key={fu.id} className="list-item">
                <div>
                  <div className="item-name">{fu.lead_name}</div>
                  <div className="item-meta">
                    {fu.lead_phone} · {fu.note || 'Follow up'}
                  </div>
                </div>
                <div className="item-actions">
                  <span className={`badge badge-${fu.lead_status}`}>{fu.lead_status}</span>
                  <Link to="/followups" className="btn btn-xs btn-outline">Open</Link>
                </div>
              </div>
            ))
          )}
        </div>

        <div className="dash-panel">
          <div className="panel-head">
            <span>Recent Leads</span>
            <Link to="/leads" className="panel-link">View All</Link>
          </div>
          {!data?.recentLeads.length ? (
            <div className="empty-state">No leads added yet.</div>
          ) : (
            data.recentLeads.map((lead) => (
              <div key={lead.id} className="list-item">
                <div>
                  <div className="item-name">{lead.name}</div>
                  <div className="item-meta">
                    {lead.phone} · {lead.property_type}
                  </div>
                </div>
                <span className={`badge badge-${lead.status}`}>{lead.status}</span>
              </div>
            ))
          )}
        </div>
      </div>

      <div className="dash-grid dash-grid-3-2">
        <div className="dash-panel">
          <div className="panel-head"><span>Lead Sources</span></div>
          {!data?.sourceBreakdown.length ? (
            <div className="empty-state">No source data yet.</div>
          ) : (
            <div className="source-chart">
              {data.sourceBreakdown.map((src) => {
                const total = stats?.totalLeads || 1;
                const pct = Math.round((src.count / total) * 100);
                return (
                  <div key={src.source} className="source-row">
                    <div className="source-label">{src.source}</div>
                    <div className="source-bar-wrap">
                      <div className="source-bar" style={{ width: `${pct}%` }} />
                    </div>
                    <div className="source-count">{src.count}</div>
                  </div>
                );
              })}
            </div>
          )}
        </div>

        <div className="dash-panel">
          <div className="panel-head"><span>Recent Activity</span></div>
          {!data?.recentActivities.length ? (
            <div className="empty-state">No activity yet.</div>
          ) : (
            <div className="activity-feed">
              {data.recentActivities.map((act) => (
                <div key={act.id} className="activity-item">
                  <div
                    className={`activity-dot ${
                      act.type === 'Lead'
                        ? 'dot-blue'
                        : act.type === 'Property'
                        ? 'dot-green'
                        : 'dot-orange'
                    }`}
                  />
                  <div className="activity-info">
                    <div>{act.description}</div>
                    <div className="activity-time">{act.created_at}</div>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </Layout>
  );
};
