/**
 * Shared CSRF-aware HTTP client for /map actions so 3D view, 2D view, and building placement
 * always stay synchronized on the latest rotated CSRF token.
 */
export function createMapApi(initialCsrf: { name: string; hash: string }, onCsrfRotate?: (hash: string) => void) {
  let csrfName = initialCsrf.name;
  let csrfHash = initialCsrf.hash;

  function updateHash(next?: string) {
    if (next && next !== csrfHash) {
      csrfHash = next;
      onCsrfRotate?.(next);
    }
  }

  async function postJSON<T = any>(url: string, data: Record<string, any> = {}): Promise<T> {
    const payload = { ...data, [csrfName]: csrfHash };
    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfHash,
      },
      body: JSON.stringify(payload),
    });
    const json = await res.json().catch(() => {
      throw new Error('Server returned HTTP ' + res.status);
    });
    if (json?.csrf_hash) updateHash(json.csrf_hash);
    return json as T;
  }

  async function getJSON<T = any>(url: string): Promise<T> {
    const res = await fetch(url, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
    if (!res.ok) throw new Error('HTTP ' + res.status);
    return (await res.json()) as T;
  }

  return {
    postJSON,
    getJSON,
    setCsrfHash: (hash: string) => {
      csrfHash = hash;
    },
    getCsrfHash: () => csrfHash,
  };
}
