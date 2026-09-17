import { Link, usePage } from '@inertiajs/react'

const links = [
  { href: '/', label: 'Home' },
  { href: '/dashboard', label: 'Dashboard' },
  { href: '/contact', label: 'Contact' },
  { href: '/feed', label: 'Feed' },
]

export default function Layout({ children }) {
  const { url, props, flash } = usePage()

  return (
    <div className="mx-auto max-w-3xl px-6 py-10">
      <header className="mb-8 flex items-center justify-between">
        <span className="text-lg font-semibold">{props.appName}</span>
        <nav className="flex gap-4 text-sm">
          {links.map((link) => {
            const active = link.href === '/' ? url === '/' : url.startsWith(link.href)
            return (
              <Link
                key={link.href}
                href={link.href}
                prefetch
                className={active ? 'font-semibold text-indigo-600' : 'text-slate-600 hover:text-slate-900'}
              >
                {link.label}
              </Link>
            )
          })}
        </nav>
      </header>

      {flash.success && (
        <div className="mb-6 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
          {flash.success}
        </div>
      )}

      <main>{children}</main>
    </div>
  )
}
