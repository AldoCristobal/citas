// public/assets/js/admin/api.js
(() => {
   let csrfToken = null;
   let csrfPromise = null;

   async function fetchCsrf() {
      if (csrfToken) return csrfToken;
      if (csrfPromise) return csrfPromise;

      csrfPromise = fetch('/api/v1/admin/csrf', {
         method: 'GET',
         credentials: 'include',
         headers: { 'Content-Type': 'application/json' },
      })
         .then(r => r.json())
         .then(j => {
            csrfPromise = null;
            if (j && j.ok && j.data && j.data.token) {
               csrfToken = j.data.token;
               return csrfToken;
            }
            throw new Error(j?.error?.message || 'No se pudo obtener CSRF');
         })
         .catch(err => {
            csrfPromise = null;
            throw err;
         });

      return csrfPromise;
   }

   async function api(url, opts = {}) {
      const method = (opts.method || 'GET').toUpperCase();
      const isMutation = ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method);

      const headers = {
         'Content-Type': 'application/json',
         ...(opts.headers || {}),
      };

      if (isMutation) {
         // CSRF automático en mutaciones
         const t = await fetchCsrf();
         headers['X-CSRF-Token'] = t;
      }

      const res = await fetch(url, {
         credentials: 'include',
         ...opts,
         headers,
      });

      const data = await res.json().catch(() => null);

      // Si se perdió la sesión, devolvemos consistente
      if (res.status === 401 || res.status === 403) {
         return data ?? { ok: false, error: { code: 'UNAUTH', message: 'No autenticado' } };
      }

      // Si el CSRF expiró o cambió, reintenta 1 vez en mutación
      if (isMutation && (data?.error?.code === 'CSRF' || res.status === 419)) {
         csrfToken = null;
         const t2 = await fetchCsrf();
         const res2 = await fetch(url, {
            credentials: 'include',
            ...opts,
            headers: { ...headers, 'X-CSRF-Token': t2 },
         });
         return (await res2.json().catch(() => null)) ?? { ok: false, error: { code: 'CLIENT', message: 'Respuesta inválida' } };
      }

      return data ?? { ok: false, error: { code: 'CLIENT', message: 'Respuesta inválida' } };
   }

   // Helpers cortos
   api.get = (url) => api(url, { method: 'GET' });
   api.post = (url, body) => api(url, { method: 'POST', body: JSON.stringify(body) });
   api.put = (url, body) => api(url, { method: 'PUT', body: JSON.stringify(body) });
   api.patch = (url, body) => api(url, { method: 'PATCH', body: JSON.stringify(body) });
   api.del = (url) => api(url, { method: 'DELETE' });

   window.api = api;
})();
