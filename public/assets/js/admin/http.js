// public/assets/js/admin/http.js
(() => {
   function getCsrfToken() {
      const meta = document.querySelector('meta[name="csrf-token"]');
      return meta?.content || '';
   }

   function authHeaders(extra = {}) {
      const h = { 'Content-Type': 'application/json', ...extra };
      const csrf = getCsrfToken();
      if (csrf) h['X-CSRF-Token'] = csrf;
      return h;
   }

   async function request(url, opts = {}) {
      const res = await fetch(url, {
         credentials: 'same-origin', // sesión admin
         headers: authHeaders(opts.headers || {}),
         ...opts,
      });

      const data = await res.json().catch(() => null);
      return data ?? { ok: false, error: { code: 'CLIENT', message: 'Respuesta inválida' } };
   }

   // atajos
   const get = (url) => request(url, { method: 'GET' });
   const del = (url) => request(url, { method: 'DELETE' });

   const post = (url, body) =>
      request(url, { method: 'POST', body: JSON.stringify(body || {}) });

   const put = (url, body) =>
      request(url, { method: 'PUT', body: JSON.stringify(body || {}) });

   const patch = (url, body) =>
      request(url, { method: 'PATCH', body: JSON.stringify(body || {}) });

   // export global (estilo de tu proyecto)
   window.http = { request, get, post, put, patch, del, authHeaders, getCsrfToken };
})();
