import { Head, useForm } from '@inertiajs/react'

function Field({ label, error, children }) {
  return (
    <label className="block">
      <span className="mb-1 block text-sm font-medium">{label}</span>
      {children}
      {error && <span className="mt-1 block text-sm text-red-600">{error}</span>}
    </label>
  )
}

const inputClass = 'w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none'

export default function Contact() {
  const { data, setData, post, processing, errors, reset } = useForm({ name: '', email: '', message: '' })

  const submit = (event) => {
    event.preventDefault()
    post('/contact', { onSuccess: () => reset() })
  }

  return (
    <>
      <Head title="Contact" />
      <h1 className="mb-6 text-3xl font-bold">Contact us</h1>
      <form onSubmit={submit} className="space-y-4 rounded-lg border border-slate-200 bg-white p-6">
        <Field label="Name" error={errors.name}>
          <input className={inputClass} value={data.name} onChange={(e) => setData('name', e.target.value)} />
        </Field>
        <Field label="Email" error={errors.email}>
          <input className={inputClass} value={data.email} onChange={(e) => setData('email', e.target.value)} />
        </Field>
        <Field label="Message" error={errors.message}>
          <textarea
            className={inputClass}
            rows={4}
            value={data.message}
            onChange={(e) => setData('message', e.target.value)}
          />
        </Field>
        <button
          type="submit"
          disabled={processing}
          className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 disabled:opacity-50"
        >
          {processing ? 'Sending…' : 'Send'}
        </button>
      </form>
    </>
  )
}
