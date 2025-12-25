(() => {
   const btn = document.querySelector('#btnLogin');
   if (!btn) return;

   btn.addEventListener('click', async () => {
      const email = document.querySelector('#email').value.trim();
      const password = document.querySelector('#password').value;

      const res = await window.api('/api/v1/admin/login', {
         method: 'POST',
         body: JSON.stringify({ email, password })
      });

      if (!res.ok) {
         const msg = document.querySelector('#msg');
         msg.style.display = 'block';
         msg.textContent = res.error?.message || 'Error';
         return;
      }

      location.href = '/admin/sedes';
   });
})();
