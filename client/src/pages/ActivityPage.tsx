import React, { useEffect, useState } from 'react';
import { Layout } from '../components/Layout';
import { apiFetch } from '../api/client';
import { Activity } from '../types';

export const ActivityPage: React.FC = () => {
  const [items, setItems] = useState<Activity[]>([]);
  const [error, setError] = useState('');

  useEffect(() => {
    apiFetch<Activity[]>('/api/activities')
      .then(setItems)
      .catch((e) => setError(e.message));
  }, []);

  return (
    <Layout title="Activity Log">
      {error && <div className="alert alert-error">{error}</div>}
      <div className="data-card">
        <div className="data-card-head">
          <span>Recent Activity (last 100 entries)</span>
        </div>
        {items.length === 0 ? (
          <div className="empty-state">No activity logged yet.</div>
        ) : (
          <div className="activity-feed">
            {items.map((act) => (
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
                  <span className="activity-type-tag">{act.type}</span>
                  <span>{act.description}</span>
                  <span className="activity-time">{act.created_at}</span>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </Layout>
  );
};
