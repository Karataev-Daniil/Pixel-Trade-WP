const translationCache = new Map();

export async function dmApi(path, opts={}) {
  try {
    const res = await fetch(SIMPLE_DM.rest + path, {
      headers: { 'X-WP-Nonce': SIMPLE_DM.nonce, 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      ...opts
    });
    if (!res.ok) {
      const errText = await res.text();
      throw new Error(`HTTP ${res.status}: ${errText}`);
    }
    return res.json();
  } catch(err) {
    console.error('dmApi error:', err);
    throw err;
  }
}

export function getTranslationFromCache(id) {
  return translationCache.get(id);
}

export function setTranslationInCache(id, text) {
  translationCache.set(id, text);
}
