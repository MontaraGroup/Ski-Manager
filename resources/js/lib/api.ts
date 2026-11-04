/**
 * Client for the session-authenticated JSON API under /api/v1 (App\Controllers\Api\V1).
 *
 * Every response is { ok, data, message, errors }. Writes send the CSRF token from the
 * <meta name="csrf-token"> tag the layout prints for pages with islands, in the header
 * CodeIgniter checks (Config\Security::$headerName). X-Requested-With makes a CSRF failure
 * come back as JSON instead of a redirect.
 */
export type ApiEnvelope<T> = {
  ok: boolean;
  data: T | null;
  message: string | null;
  errors: Record<string, string>;
};

export class ApiError extends Error {
  constructor(
    message: string,
    readonly status: number,
    readonly errors: Record<string, string> = {},
  ) {
    super(message);
  }
}

function csrf(): { header: string; token: string } | null {
  const meta = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]');
  return meta ? { header: meta.dataset.header || 'X-CSRF-TOKEN', token: meta.content } : null;
}

export async function api<T>(path: string, options: { method?: string; body?: unknown } = {}): Promise<T> {
  const method = (options.method ?? 'GET').toUpperCase();
  const headers: Record<string, string> = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
  if (options.body !== undefined) headers['Content-Type'] = 'application/json';
  const token = method === 'GET' ? null : csrf();
  if (token) headers[token.header] = token.token;

  const res = await fetch(`/api/v1/${path.replace(/^\//, '')}`, {
    method,
    headers,
    credentials: 'same-origin',
    body: options.body === undefined ? undefined : JSON.stringify(options.body),
  });

  if (res.status === 401) {
    // Session expired: back to sign-in
    window.location.assign('/login');
    throw new ApiError('Your session has ended. Sign in again.', 401);
  }

  let body: ApiEnvelope<T> | null = null;
  try {
    body = (await res.json()) as ApiEnvelope<T>;
  } catch {
    // not JSON: a PHP error page or a proxy error
  }
  if (!res.ok || !body?.ok) {
    throw new ApiError(body?.message || `Request failed (${res.status}). Try again.`, res.status, body?.errors ?? {});
  }
  return body.data as T;
}
