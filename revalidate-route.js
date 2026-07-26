// Target path in toddflaw-next: app/api/revalidate/route.js
// Pairs with the WP mu-plugin tmf-headless-revalidate.php.
// Env required on Vercel: REVALIDATE_SECRET (same value as WP's TMF_REVALIDATE_SECRET).
import { revalidatePath } from 'next/cache';

export async function POST(request) {
  let body;
  try {
    body = await request.json();
  } catch {
    return Response.json({ ok: false, error: 'invalid json' }, { status: 400 });
  }

  const secret = process.env.REVALIDATE_SECRET;
  if (!secret || body?.secret !== secret) {
    return Response.json({ ok: false, error: 'unauthorized' }, { status: 401 });
  }

  const path = typeof body?.path === 'string' ? body.path : '';
  // Only site-relative paths; no protocol, no traversal.
  if (!path.startsWith('/') || path.includes('..') || path.includes('//')) {
    return Response.json({ ok: false, error: 'invalid path' }, { status: 400 });
  }

  revalidatePath(path);
  return Response.json({ ok: true, revalidated: path, at: new Date().toISOString() });
}
