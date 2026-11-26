# RealEstate CRM (React + TypeScript + Node.js)

A full-stack daily-use Real Estate CRM built with **React**, **TypeScript**, **Node.js (Express)**, and **SQLite** (`node:sqlite`).

## Features
- **Authentication**: JWT login (`admin` / `admin123` default)
- **Dashboard**: KPI stats, Today's Follow-ups, Recent Leads, Lead Source breakdown, and Activity Feed
- **Leads**: Add, edit, delete, search, status filter, budget formatting (₹ Lakhs/Crores), Call & WhatsApp links
- **Follow-ups**: Today, Overdue, Upcoming, and Done tabs with one-click completion
- **Properties**: Apartment, Villa, Plot, Commercial listings with price, area, bedrooms/bathrooms, and status
- **Activity Log**: Automatic audit trail for leads, follow-ups, and properties

## Local Setup

```bash
# 1. Install dependencies
npm run install:all

# 2. (Optional) Seed sample real-estate data
npx --prefix server tsx server/src/seed.ts

# 3. Run backend API (port 5000) & React frontend (port 5173)
npm run dev:server
npm run dev:client
```

Open **http://127.0.0.1:5173/** and log in with `admin` / `admin123`.
