import React, { useEffect, useState } from 'react';
import { Plus, X, Home, Calculator, Download } from 'lucide-react';
import { Layout } from '../components/Layout';
import { apiFetch } from '../api/client';
import {
  Property,
  PROPERTY_TYPES,
  PROPERTY_STATUSES,
  formatPrice,
  exportToCsv,
} from '../types';

const emptyProp = {
  title: '',
  type: 'Apartment',
  status: 'Available' as Property['status'],
  location: '',
  price: '',
  area_sqft: '',
  bedrooms: '',
  bathrooms: '',
  description: '',
};

export const PropertiesPage: React.FC = () => {
  const [properties, setProperties] = useState<Property[]>([]);
  const [q, setQ] = useState('');
  const [typeFilter, setTypeFilter] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [formOpen, setFormOpen] = useState(false);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [form, setForm] = useState(emptyProp);
  const [msg, setMsg] = useState('');
  const [error, setError] = useState('');
  const [calcProp, setCalcProp] = useState<Property | null>(null);
  const [downPct, setDownPct] = useState(20);
  const [rate, setRate] = useState(8.5);
  const [years, setYears] = useState(20);
  const [commPct, setCommPct] = useState(2);

  const loadProperties = async (search = q, type = typeFilter, status = statusFilter) => {
    const params = new URLSearchParams();
    if (search) params.set('q', search);
    if (type) params.set('type', type);
    if (status) params.set('status', status);
    const items = await apiFetch<Property[]>(`/api/properties?${params.toString()}`);
    setProperties(items);
  };

  useEffect(() => {
    loadProperties().catch((e) => setError(e.message));
  }, []);

  const openNew = () => {
    setEditingId(null);
    setForm(emptyProp);
    setFormOpen(true);
  };

  const openEdit = (prop: Property) => {
    setEditingId(prop.id);
    setForm({
      title: prop.title,
      type: prop.type,
      status: prop.status,
      location: prop.location,
      price: String(prop.price),
      area_sqft: prop.area_sqft ? String(prop.area_sqft) : '',
      bedrooms: prop.bedrooms ? String(prop.bedrooms) : '',
      bathrooms: prop.bathrooms ? String(prop.bathrooms) : '',
      description: prop.description || '',
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
        price: Number(form.price) || 0,
        area_sqft: Number(form.area_sqft) || 0,
        bedrooms: Number(form.bedrooms) || 0,
        bathrooms: Number(form.bathrooms) || 0,
      };
      if (editingId) {
        await apiFetch(`/api/properties/${editingId}`, {
          method: 'PUT',
          body: JSON.stringify(payload),
        });
        setMsg('Property updated.');
      } else {
        await apiFetch('/api/properties', {
          method: 'POST',
          body: JSON.stringify(payload),
        });
        setMsg('Property added.');
      }
      setFormOpen(false);
      setEditingId(null);
      setForm(emptyProp);
      await loadProperties();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Save failed');
    }
  };

  const handleDelete = async (id: number) => {
    if (!window.confirm('Delete this property?')) return;
    try {
      await apiFetch(`/api/properties/${id}`, { method: 'DELETE' });
      setMsg('Property deleted.');
      await loadProperties();
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Delete failed');
    }
  };

  return (
    <Layout title="Properties">
      {msg && <div className="alert alert-success">{msg}</div>}
      {error && <div className="alert alert-error">{error}</div>}

      <div className="page-toolbar">
        <form
          className="toolbar-search"
          onSubmit={(e) => {
            e.preventDefault();
            loadProperties();
          }}
        >
          <input
            type="text"
            value={q}
            onChange={(e) => setQ(e.target.value)}
            placeholder="Search title, location..."
          />
          <select
            value={typeFilter}
            onChange={(e) => setTypeFilter(e.target.value)}
          >
            <option value="">All Types</option>
            {PROPERTY_TYPES.filter((t) => t !== 'Any').map((t) => (
              <option key={t} value={t}>{t}</option>
            ))}
          </select>
          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
          >
            <option value="">All Status</option>
            {PROPERTY_STATUSES.map((s) => (
              <option key={s} value={s}>{s}</option>
            ))}
          </select>
          <button type="submit" className="btn btn-secondary">Filter</button>
          {(q || typeFilter || statusFilter) && (
            <button
              type="button"
              className="btn btn-ghost"
              onClick={() => {
                setQ('');
                setTypeFilter('');
                setStatusFilter('');
                loadProperties('', '', '');
              }}
            >
              Clear
            </button>
          )}
        </form>
        <div style={{ display: 'flex', gap: 8 }}>
          <button
            className="btn btn-ghost"
            onClick={() =>
              exportToCsv(
                'properties-export.csv',
                properties.map((p) => ({
                  Title: p.title,
                  Type: p.type,
                  Location: p.location,
                  Price: p.price,
                  AreaSqFt: p.area_sqft,
                  Bedrooms: p.bedrooms,
                  Bathrooms: p.bathrooms,
                  Status: p.status,
                }))
              )
            }
          >
            <Download size={15} /> Export CSV
          </button>
          <button className="btn btn-primary" onClick={openNew}>
            <Plus size={16} /> Add Property
          </button>
        </div>
      </div>

      {formOpen && (
        <div className="form-panel">
          <div className="form-panel-head">
            <span>{editingId ? 'Edit Property' : 'Add Property'}</span>
            <button className="close-btn" onClick={() => setFormOpen(false)}>
              <X size={18} />
            </button>
          </div>
          <form onSubmit={handleSubmit} className="crm-form">
            <div className="form-row">
              <div className="form-group" style={{ flex: 2 }}>
                <label>Property Title *</label>
                <input
                  required
                  value={form.title}
                  onChange={(e) => setForm({ ...form, title: e.target.value })}
                  placeholder="e.g. 3BHK Apartment in Kakkanad"
                />
              </div>
              <div className="form-group">
                <label>Type</label>
                <select
                  value={form.type}
                  onChange={(e) => setForm({ ...form, type: e.target.value })}
                >
                  {PROPERTY_TYPES.filter((t) => t !== 'Any').map((t) => (
                    <option key={t} value={t}>{t}</option>
                  ))}
                </select>
              </div>
              <div className="form-group">
                <label>Status</label>
                <select
                  value={form.status}
                  onChange={(e) =>
                    setForm({ ...form, status: e.target.value as Property['status'] })
                  }
                >
                  {PROPERTY_STATUSES.map((s) => (
                    <option key={s} value={s}>{s}</option>
                  ))}
                </select>
              </div>
            </div>

            <div className="form-row">
              <div className="form-group">
                <label>Location *</label>
                <input
                  required
                  value={form.location}
                  onChange={(e) => setForm({ ...form, location: e.target.value })}
                  placeholder="Area, City"
                />
              </div>
              <div className="form-group">
                <label>Price (₹) *</label>
                <input
                  type="number"
                  required
                  value={form.price}
                  onChange={(e) => setForm({ ...form, price: e.target.value })}
                  placeholder="4500000"
                />
              </div>
              <div className="form-group">
                <label>Area (Sq.ft)</label>
                <input
                  type="number"
                  value={form.area_sqft}
                  onChange={(e) => setForm({ ...form, area_sqft: e.target.value })}
                  placeholder="1200"
                />
              </div>
            </div>

            <div className="form-row">
              <div className="form-group">
                <label>Bedrooms</label>
                <input
                  type="number"
                  min={0}
                  value={form.bedrooms}
                  onChange={(e) => setForm({ ...form, bedrooms: e.target.value })}
                  placeholder="0"
                />
              </div>
              <div className="form-group">
                <label>Bathrooms</label>
                <input
                  type="number"
                  min={0}
                  value={form.bathrooms}
                  onChange={(e) => setForm({ ...form, bathrooms: e.target.value })}
                  placeholder="0"
                />
              </div>
            </div>

            <div className="form-group">
              <label>Description</label>
              <textarea
                rows={2}
                value={form.description}
                onChange={(e) =>
                  setForm({ ...form, description: e.target.value })
                }
                placeholder="Key highlights, amenities..."
              />
            </div>

            <div className="form-actions">
              <button type="submit" className="btn btn-primary">
                {editingId ? 'Update Property' : 'Save Property'}
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

      {properties.length === 0 ? (
        <div className="data-card">
          <div className="empty-state">
            No properties yet. Click &quot;+ Add Property&quot; to add your first listing.
          </div>
        </div>
      ) : (
        <div className="prop-grid">
          {properties.map((prop) => (
            <div key={prop.id} className="prop-card">
              <div className="prop-header">
                <span style={{ color: '#e67e22' }}>
                  <Home size={20} />
                </span>
                <span className={`badge badge-${prop.status}`}>{prop.status}</span>
              </div>
              <div className="prop-title">{prop.title}</div>
              <div className="prop-loc">{prop.location}</div>
              <div className="prop-price">{formatPrice(prop.price)}</div>
              <div className="prop-specs">
                {prop.type}
                {prop.area_sqft ? ` · ${prop.area_sqft.toLocaleString()} sq.ft` : ''}
                {prop.bedrooms ? ` · ${prop.bedrooms}BHK` : ''}
              </div>
              {prop.description && (
                <div className="prop-desc">{prop.description}</div>
              )}
              <div className="prop-actions">
                <button
                  className="btn btn-xs btn-ghost"
                  onClick={() => setCalcProp(prop)}
                  title="EMI & Commission Calculator"
                >
                  <Calculator size={13} /> EMI / Comm
                </button>
                <button
                  className="btn btn-xs btn-outline"
                  onClick={() => openEdit(prop)}
                >
                  Edit
                </button>
                <button
                  className="btn btn-xs btn-danger"
                  onClick={() => handleDelete(prop.id)}
                >
                  Delete
                </button>
              </div>
            </div>
          ))}
        </div>
      )}

      {calcProp && (() => {
        const principal = calcProp.price * (1 - downPct / 100);
        const monthlyRate = rate / 12 / 100;
        const n = years * 12;
        const emi =
          monthlyRate > 0
            ? (principal * monthlyRate * Math.pow(1 + monthlyRate, n)) /
              (Math.pow(1 + monthlyRate, n) - 1)
            : principal / n;
        const commission = (calcProp.price * commPct) / 100;

        return (
          <div className="modal-backdrop" onClick={() => setCalcProp(null)}>
            <div className="modal-card" onClick={(e) => e.stopPropagation()}>
              <div className="modal-head">
                <span>EMI &amp; Commission — {calcProp.title}</span>
                <button
                  className="close-btn"
                  style={{ color: '#475569' }}
                  onClick={() => setCalcProp(null)}
                >
                  <X size={18} />
                </button>
              </div>
              <div className="modal-body">
                <div className="form-row">
                  <div className="form-group">
                    <label>Down Payment (%)</label>
                    <input
                      type="number"
                      min={0}
                      max={90}
                      value={downPct}
                      onChange={(e) => setDownPct(Number(e.target.value) || 0)}
                    />
                  </div>
                  <div className="form-group">
                    <label>Interest Rate (% p.a.)</label>
                    <input
                      type="number"
                      step="0.1"
                      min={1}
                      max={25}
                      value={rate}
                      onChange={(e) => setRate(Number(e.target.value) || 1)}
                    />
                  </div>
                  <div className="form-group">
                    <label>Tenure (Years)</label>
                    <input
                      type="number"
                      min={1}
                      max={35}
                      value={years}
                      onChange={(e) => setYears(Number(e.target.value) || 1)}
                    />
                  </div>
                  <div className="form-group">
                    <label>Brokerage (%)</label>
                    <input
                      type="number"
                      step="0.25"
                      min={0}
                      max={10}
                      value={commPct}
                      onChange={(e) => setCommPct(Number(e.target.value) || 0)}
                    />
                  </div>
                </div>

                <div className="calc-result-grid">
                  <div className="calc-box">
                    <div className="calc-box-val">
                      ₹{Math.round(emi).toLocaleString('en-IN')}/mo
                    </div>
                    <div className="calc-box-lbl">Estimated Monthly EMI</div>
                  </div>
                  <div className="calc-box">
                    <div className="calc-box-val">{formatPrice(principal)}</div>
                    <div className="calc-box-lbl">Loan Amount</div>
                  </div>
                  <div className="calc-box">
                    <div className="calc-box-val">{formatPrice(commission)}</div>
                    <div className="calc-box-lbl">Est. Brokerage ({commPct}%)</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        );
      })()}
    </Layout>
  );
};
