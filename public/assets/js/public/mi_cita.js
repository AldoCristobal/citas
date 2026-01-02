(() => {
   const $ = (id) => document.getElementById(id);

   const folioInp = $('folio');
   const tokenInp = $('token');
   const btnBuscar = $('btnBuscar');
   const btnCancelar = $('btnCancelar');
   const hint = $('hint');

   const alertBox = $('alert');
   const okBox = $('ok');
   const detalle = $('detalle');

   let last = null;

   function showError(msg) {
      okBox.classList.add('d-none');
      alertBox.textContent = msg || 'Error';
      alertBox.classList.remove('d-none');
   }
   function showOk(msg) {
      alertBox.classList.add('d-none');
      okBox.textContent = msg || 'OK';
      okBox.classList.remove('d-none');
   }

   async function apiGet(url) {
      const res = await fetch(url, { credentials: 'same-origin' });
      return res.json();
   }
   async function apiPost(url, body) {
      const res = await fetch(url, {
         method: 'POST',
         credentials: 'same-origin',
         headers: { 'Content-Type': 'application/json' },
         body: JSON.stringify(body),
      });
      return res.json();
   }

   function render(c) {
      if (!c) {
         detalle.textContent = 'Sin datos.';
         btnCancelar.disabled = true;
         hint.textContent = '';
         return;
      }

      detalle.innerHTML = `
      <div><b>Folio:</b> <span class="mono">${c.folio || '-'}</span></div>
      <div><b>Fecha:</b> ${c.fecha} <b>Hora:</b> ${c.hora}</div>
      <div><b>Estado:</b> ${c.estado}</div>
    `;

      btnCancelar.disabled = (String(c.estado) !== 'confirmada');
      hint.textContent = btnCancelar.disabled ? 'No se puede cancelar en ese estado.' : 'Cancelable';
   }

   async function buscar() {
      const folio = (folioInp.value || '').trim();
      const token = (tokenInp.value || '').trim();

      if (!folio || !token) return showError('Folio y token son requeridos.');

      // Recomendado: token por query string
      const url = '/api/v1/public/citas/' + encodeURIComponent(folio) + '?token=' + encodeURIComponent(token);
      const j = await apiGet(url);

      if (!j.ok) {
         last = null;
         render(null);
         return showError(j?.error?.message || 'No encontrada');
      }

      last = j.data;
      render(last);
      showOk('Cita cargada.');
   }

   async function cancelar() {
      const folio = (folioInp.value || '').trim();
      const token = (tokenInp.value || '').trim();
      if (!folio || !token) return showError('Folio y token son requeridos.');

      if (!confirm('¿Cancelar esta cita?')) return;

      const j = await apiPost('/api/v1/public/citas/' + encodeURIComponent(folio) + '/cancelar', { token });

      if (!j.ok) return showError(j?.error?.message || 'No se pudo cancelar');

      showOk('Cita cancelada.');
      await buscar();
   }

   function init() {
      // autollenado desde querystring (opcional)
      const qs = new URLSearchParams(location.search);
      if (qs.get('folio')) folioInp.value = qs.get('folio');
      if (qs.get('token')) tokenInp.value = qs.get('token');

      btnBuscar.addEventListener('click', () => buscar().catch(e => showError(e.message)));
      btnCancelar.addEventListener('click', () => cancelar().catch(e => showError(e.message)));

      if (folioInp.value && tokenInp.value) {
         buscar().catch(() => { });
      }
   }

   init();
})();
