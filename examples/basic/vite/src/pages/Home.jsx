import { Head } from '@inertiajs/react'

export default function Home({ message, phpVersion, yiiVersion }) {
  return (
    <>
      <Head title="Home" />
      <h1 className="mb-4 text-3xl font-bold">Welcome to Inertia.js with Yii2</h1>
      <p className="mb-6 text-slate-600">{message}</p>
      <dl className="grid grid-cols-2 gap-4 text-sm">
        <div className="rounded-lg border border-slate-200 bg-white p-4">
          <dt className="text-slate-500">PHP</dt>
          <dd className="text-lg font-medium">{phpVersion}</dd>
        </div>
        <div className="rounded-lg border border-slate-200 bg-white p-4">
          <dt className="text-slate-500">Yii</dt>
          <dd className="text-lg font-medium">{yiiVersion}</dd>
        </div>
      </dl>
    </>
  )
}
