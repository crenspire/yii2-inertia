import { Deferred, Head, router } from '@inertiajs/react'

function Stat({ label, value }) {
  return (
    <div className="rounded-lg border border-slate-200 bg-white p-4">
      <h3 className="text-sm text-slate-500">{label}</h3>
      <p className="text-2xl font-semibold">{value.toLocaleString()}</p>
    </div>
  )
}

export default function Dashboard({ stats, generatedAt, activity }) {
  return (
    <>
      <Head title="Dashboard" />
      <h1 className="mb-6 text-3xl font-bold">Dashboard</h1>

      <div className="mb-8 grid grid-cols-3 gap-4">
        <Stat label="Users" value={stats.users} />
        <Stat label="Revenue" value={stats.revenue} />
        <Stat label="Orders" value={stats.orders} />
      </div>

      <div className="mb-8 flex items-center gap-4 text-sm">
        <span>
          Generated at <strong>{generatedAt}</strong>
        </span>
        <button
          type="button"
          onClick={() => router.reload({ only: ['generatedAt'] })}
          className="rounded-md bg-indigo-600 px-3 py-1.5 font-medium text-white hover:bg-indigo-500"
        >
          Partial reload
        </button>
      </div>

      <h2 className="mb-3 text-xl font-semibold">Recent activity (deferred prop)</h2>
      <Deferred data="activity" fallback={<p className="text-slate-500">Loading activity…</p>}>
        <ul className="divide-y divide-slate-200 rounded-lg border border-slate-200 bg-white">
          {activity?.map((item) => (
            <li key={item.id} className="px-4 py-3 text-sm">
              {item.text}
            </li>
          ))}
        </ul>
      </Deferred>
    </>
  )
}
