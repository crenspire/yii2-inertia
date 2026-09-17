import { defineConfig } from 'vitepress'

const repository = 'https://github.com/crenspire/yii2-inertia'

export default defineConfig({
  title: 'Yii2 Inertia',
  description: 'The Inertia.js v3 adapter for the Yii 2 framework: build React, Vue and Svelte single-page apps with classic Yii controllers.',
  base: '/yii2-inertia/',
  cleanUrls: true,
  lastUpdated: true,
  head: [
    ['link', { rel: 'icon', type: 'image/svg+xml', href: '/yii2-inertia/logo.svg' }],
    ['meta', { name: 'theme-color', content: '#6d28d9' }],
    ['meta', { property: 'og:type', content: 'website' }],
    ['meta', { property: 'og:title', content: 'Yii2 Inertia' }],
    ['meta', { property: 'og:description', content: 'The Inertia.js v3 adapter for the Yii 2 framework.' }],
  ],
  markdown: {
    theme: { light: 'github-light', dark: 'github-dark' },
  },
  themeConfig: {
    logo: '/logo.svg',
    nav: [
      { text: 'Guide', link: '/guide/introduction', activeMatch: '/guide/' },
      { text: 'Reference', link: '/reference/configuration', activeMatch: '/reference/' },
      {
        text: '2.x',
        items: [
          { text: 'Changelog', link: '/changelog' },
          { text: 'Upgrading from 1.x', link: '/guide/upgrade' },
          { text: 'Packagist', link: 'https://packagist.org/packages/crenspire/yii2-inertia' },
          { text: 'Inertia.js documentation', link: 'https://inertiajs.com' },
        ],
      },
    ],
    sidebar: {
      '/guide/': [
        {
          text: 'Getting started',
          items: [
            { text: 'Introduction', link: '/guide/introduction' },
            { text: 'Installation', link: '/guide/installation' },
            { text: 'Upgrading from 1.x', link: '/guide/upgrade' },
          ],
        },
        {
          text: 'The basics',
          items: [
            { text: 'Pages and props', link: '/guide/responses' },
            { text: 'Shared data', link: '/guide/shared-data' },
            { text: 'Redirects', link: '/guide/redirects' },
            { text: 'Forms and validation', link: '/guide/forms' },
            { text: 'File uploads', link: '/guide/file-uploads' },
            { text: 'Flash data', link: '/guide/flash-data' },
            { text: 'CSRF protection', link: '/guide/csrf-protection' },
            { text: 'Authentication', link: '/guide/authentication' },
            { text: 'Error handling', link: '/guide/error-handling' },
          ],
        },
        {
          text: 'Data loading',
          items: [
            { text: 'Partial reloads', link: '/guide/partial-reloads' },
            { text: 'Deferred props', link: '/guide/deferred-props' },
            { text: 'Merging props', link: '/guide/merging-props' },
            { text: 'Once props', link: '/guide/once-props' },
            { text: 'Infinite scroll', link: '/guide/infinite-scroll' },
          ],
        },
        {
          text: 'Advanced',
          items: [
            { text: 'Asset versioning', link: '/guide/asset-versioning' },
            { text: 'History encryption', link: '/guide/history-encryption' },
            { text: 'Vite', link: '/guide/vite' },
            { text: 'Server-side rendering', link: '/guide/ssr' },
            { text: 'Testing', link: '/guide/testing' },
            { text: 'Troubleshooting', link: '/guide/troubleshooting' },
          ],
        },
      ],
      '/reference/': [
        {
          text: 'Reference',
          items: [
            { text: 'Configuration', link: '/reference/configuration' },
            { text: 'Inertia facade', link: '/reference/inertia' },
            { text: 'Prop types', link: '/reference/props' },
            { text: 'Vite helper', link: '/reference/vite' },
            { text: 'SSR gateway', link: '/reference/ssr' },
            { text: 'Protocol handling', link: '/reference/protocol' },
          ],
        },
      ],
    },
    socialLinks: [{ icon: 'github', link: repository }],
    editLink: {
      pattern: `${repository}/edit/develop/docs/:path`,
      text: 'Edit this page on GitHub',
    },
    search: { provider: 'local' },
    outline: { level: [2, 3] },
    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright © Crenspire',
    },
  },
})
