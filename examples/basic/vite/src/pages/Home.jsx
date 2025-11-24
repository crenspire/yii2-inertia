import React from 'react';
import { Link } from '@inertiajs/inertia-react';

export default function Home({ title, message }) {
  return (
    <div style={{ padding: '2rem', fontFamily: 'system-ui' }}>
      <h1>{title}</h1>
      <p>{message}</p>
      <nav style={{ marginTop: '2rem' }}>
        <Link href="/dashboard" style={{ color: '#007bff', textDecoration: 'none' }}>
          Go to Dashboard →
        </Link>
      </nav>
    </div>
  );
}

