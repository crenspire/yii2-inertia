import './app.css'
import { createInertiaApp } from '@inertiajs/react'
import { createRoot } from 'react-dom/client'
import Layout from './Layout'

createInertiaApp({
  title: (title) => (title ? `${title} · Yii2 + Inertia` : 'Yii2 + Inertia'),
  resolve: (name) => {
    const pages = import.meta.glob('./pages/**/*.jsx', { eager: true })
    const page = pages[`./pages/${name}.jsx`]
    if (!page) {
      throw new Error(`Page component "${name}" not found.`)
    }
    page.default.layout ??= (content) => <Layout>{content}</Layout>
    return page
  },
  setup({ el, App, props }) {
    createRoot(el).render(<App {...props} />)
  },
  progress: { color: '#4f46e5' },
})
