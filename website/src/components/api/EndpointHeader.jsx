import { HttpMethod } from './HttpMethod'

export function EndpointHeader({ method, path }) {
  return (
    <div className="mt-8 flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
      <HttpMethod method={method} />
      <code className="text-sm font-semibold text-slate-800">{path}</code>
    </div>
  )
}
