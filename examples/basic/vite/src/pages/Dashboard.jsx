import React from 'react';
import { Link } from '@inertiajs/inertia-react';

export default function Dashboard({ title, stats }) {
  return (
    <div style={{ padding: '2rem', fontFamily: 'system-ui' }}>
      <h1>{title}</h1>
      <nav style={{ marginBottom: '2rem' }}>
        <Link href="/" style={{ color: '#007bff', textDecoration: 'none' }}>
          ← Back to Home
        </Link>
      </nav>
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: '1rem' }}>
        <div style={{ padding: '1rem', border: '1px solid #ddd', borderRadius: '4px' }}>
          <h3>Users</h3>
          <p style={{ fontSize: '2rem', margin: 0 }}>{stats.users.toLocaleString()}</p>
        </div>
        <div style={{ padding: '1rem', border: '1px solid #ddd', borderRadius: '4px' }}>
          <h3>Revenue</h3>
          <p style={{ fontSize: '2rem', margin: 0 }}>${stats.revenue.toLocaleString()}</p>
        </div>
        <div style={{ padding: '1rem', border: '1px solid #ddd', borderRadius: '4px' }}>
          <h3>Orders</h3>
          <p style={{ fontSize: '2rem', margin: 0 }}>{stats.orders.toLocaleString()}</p>
        </div>
      </div>
    </div>
  );
}

