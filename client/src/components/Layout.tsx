import React, { useState } from 'react';
import { NavLink, useNavigate } from 'react-router-dom';
import {
  LayoutGrid,
  Users,
  Bell,
  Home,
  List,
  LogOut,
  Menu,
  Building2,
} from 'lucide-react';
import { clearAuth, getStoredUser } from '../api/client';

interface LayoutProps {
  title: string;
  todayFollowupsBadge?: number;
  children: React.ReactNode;
}

export const Layout: React.FC<LayoutProps> = ({
  title,
  todayFollowupsBadge = 0,
  children,
}) => {
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const navigate = useNavigate();
  const user = getStoredUser();

  const handleLogout = () => {
    clearAuth();
    navigate('/login');
  };

  const todayStr = new Date().toLocaleDateString('en-IN', {
    weekday: 'short',
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  });

  const navItems = [
    { to: '/', label: 'Dashboard', icon: <LayoutGrid size={18} /> },
    { to: '/leads', label: 'Leads', icon: <Users size={18} /> },
    {
      to: '/followups',
      label: 'Follow-ups',
      icon: <Bell size={18} />,
      badge: todayFollowupsBadge,
    },
    { to: '/properties', label: 'Properties', icon: <Home size={18} /> },
    { to: '/activity', label: 'Activity Log', icon: <List size={18} /> },
  ];

  return (
    <div className="app-shell">
      <button
        className="mobile-toggle"
        onClick={() => setSidebarOpen((v) => !v)}
        aria-label="Toggle Menu"
      >
        <Menu size={20} />
      </button>
      <div
        className={`sidebar-overlay ${sidebarOpen ? 'active' : ''}`}
        onClick={() => setSidebarOpen(false)}
      />

      <aside className={`crm-sidebar ${sidebarOpen ? 'open' : ''}`}>
        <div className="sidebar-brand">
          <div className="brand-icon">
            <Building2 size={26} />
          </div>
          <div>
            <div className="brand-name">RE CRM</div>
            <div className="brand-sub">Real Estate Platform</div>
          </div>
        </div>

        <nav className="sidebar-nav">
          {navItems.map((item) => (
            <NavLink
              key={item.to}
              to={item.to}
              end={item.to === '/'}
              onClick={() => setSidebarOpen(false)}
              className={({ isActive }) =>
                `nav-item ${isActive ? 'active' : ''}`
              }
            >
              {item.icon}
              <span>{item.label}</span>
              {item.badge && item.badge > 0 ? (
                <span className="nav-badge">{item.badge}</span>
              ) : null}
            </NavLink>
          ))}
        </nav>

        <div className="sidebar-footer">
          <div className="user-avatar">
            {(user?.fullName || 'A').charAt(0).toUpperCase()}
          </div>
          <div>
            <div className="user-name">{user?.fullName || 'CRM Admin'}</div>
            <div className="user-role">Agent</div>
          </div>
          <button
            className="logout-btn"
            onClick={handleLogout}
            title="Logout"
          >
            <LogOut size={16} />
          </button>
        </div>
      </aside>

      <div className="crm-main">
        <header className="topbar">
          <div className="topbar-title">{title}</div>
          <div className="topbar-date">{todayStr}</div>
        </header>
        <main className="main-content">{children}</main>
      </div>
    </div>
  );
};
