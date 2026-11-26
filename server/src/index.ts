import express from 'express';
import cors from 'cors';
import path from 'node:path';
import fs from 'node:fs';
import apiRouter from './routes/api';

const app = express();
const PORT = Number(process.env.PORT) || 5000;

app.use(cors());
app.use(express.json());

app.get('/api/health', (_req, res) => {
  res.json({ status: 'ok', timestamp: new Date().toISOString() });
});

app.use('/api', apiRouter);

// Serve built React frontend in production if present
const clientDist = path.join(__dirname, '..', '..', 'client', 'dist');
if (fs.existsSync(clientDist)) {
  app.use(express.static(clientDist));
  app.get('*', (_req, res) => {
    res.sendFile(path.join(clientDist, 'index.html'));
  });
}

app.listen(PORT, '127.0.0.1', () => {
  console.log(`RealEstate CRM API running at http://127.0.0.1:${PORT}`);
});
