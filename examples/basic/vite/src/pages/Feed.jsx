import { Head, InfiniteScroll } from '@inertiajs/react'

export default function Feed({ posts }) {
  return (
    <>
      <Head title="Feed" />
      <h1 className="mb-6 text-3xl font-bold">Feed</h1>
      <InfiniteScroll data="posts" loading={<p className="py-4 text-center text-slate-500">Loading more…</p>}>
        <ul className="space-y-3">
          {posts.data.map((post) => (
            <li key={post.id} className="rounded-lg border border-slate-200 bg-white p-4">
              <h2 className="font-semibold">{post.title}</h2>
              <p className="text-sm text-slate-600">{post.body}</p>
            </li>
          ))}
        </ul>
      </InfiniteScroll>
    </>
  )
}
