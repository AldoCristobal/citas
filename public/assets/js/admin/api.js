// public/assets/js/admin/api.js
window.api = async function api(url, opts = {}) {
   const res = await fetch(url, {
      credentials: 'include', // sesión admin
      headers: {
         'Content-Type': 'application/json',
         ...(opts.headers || {})
      },
      ...opts
   });

   // intenta JSON siempre
   const data = await res.json().catch(() => null);

   // si el backend devolvió ok=false, lo respetamos
   return data ?? { ok: false, error: { code: 'CLIENT', message: 'Respuesta inválida' } };
};
