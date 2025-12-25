(() => {
   const $ = (id) => document.getElementById(id);

   const sedeSel = $('sede_id');
   const fechaInp = $('fecha');
   const estadoSel = $('estado');
   const qInp = $('q');
   const btnBuscar = $('btnBuscar');

   const btnEnAtencion = document.getElementById('btnEnAtencion');
   const btnAtendida = document.getElementById('btnAtendida');
   const btnNoAsistio = document.getElementById('btnNoAsistio');
   const btnCancelar = document.getElementById('btnCancelar');
   const selHint = document.getElementById('selHint');

   let selectedRow = null;


   let gridApi = null;
   let selectedId = null;
   let modal = null;

   function authHeaders() {
      // Si ya tienes helpers globales, úsalos. Fallback:
      const h = { 'Content-Type': 'application/json' };
      const meta = document.querySelector('meta[name="csrf-token"]');
      if (meta && meta.content) h['X-CSRF-Token'] = meta.content;
      return h;
   }

   async function apiGet(url) {
      const res = await fetch(url, { headers: authHeaders(), credentials: 'same-origin' });
      return res.json();
   }

   async function apiPatch(url, body) {
      const res = await fetch(url, {
         method: 'PATCH',
         headers: authHeaders(),
         credentials: 'same-origin',
         body: JSON.stringify(body),
      });
      return res.json();
   }

   async function loadSedes() {
      // Reusa tu endpoint admin o el de catálogo público si ya está OK para admin.
      // Aquí asumo que tienes /api/v1/public/sedes funcionando (ya lo tienes).
      const j = await apiGet('/api/v1/admin/catalogos/sedes');
      if (!j.ok) throw new Error(j?.error?.message || 'No se pudieron cargar sedes');

      sedeSel.innerHTML = '';
      for (const s of j.data) {
         const opt = document.createElement('option');
         opt.value = s.id;
         opt.textContent = s.nombre;
         sedeSel.appendChild(opt);
      }
   }

   function fmtEstado(estado, reFolio) {
      const e = String(estado || '');
      if (e === 'reprogramada' && reFolio) return `reprogramada → ${reFolio}`;
      return e;
   }

   function badgeEstado(estado, reFolio) {
      const e = String(estado || '');
      const label = (e === 'reprogramada' && reFolio) ? `reprogramada → ${reFolio}` : e;

      // clases bootstrap simples (sin complicarte)
      let cls = 'secondary';
      if (e === 'confirmada') cls = 'primary';
      else if (e === 'en_atencion') cls = 'info';
      else if (e === 'atendida') cls = 'success';
      else if (e === 'no_asistio') cls = 'warning';
      else if (e === 'cancelada' || e === 'expirada') cls = 'danger';

      const span = document.createElement('span');
      span.className = `badge bg-${cls}`;
      span.textContent = label;
      return span;
   }

   function canAction(row, action) {
      const e = String(row?.estado || '');
      if (e === 'confirmada') return ['en_atencion', 'atendida', 'no_asistio', 'cancelada'].includes(action);
      if (e === 'en_atencion') return ['atendida', 'no_asistio', 'cancelada'].includes(action); // ✅
      return false;
   }

   function buildGrid() {
      const columnDefs = [
         { field: 'hora', headerName: 'Hora', width: 90 },
         { field: 'folio_publico', headerName: 'Folio', width: 140 },
         { field: 'nombre', headerName: 'Nombre', flex: 1, minWidth: 180 },
         { field: 'curp_rfc', headerName: 'CURP/RFC', width: 150 },
         { field: 'tramite_nombre', headerName: 'Trámite', width: 160 },
         {
            headerName: 'Estado',
            field: 'estado',
            width: 170,
            cellRenderer: (p) => badgeEstado(p.data?.estado, p.data?.reprogramada_a_folio),
         },
         {
            headerName: '',
            width: 80,
            cellRenderer: (p) => {
               const btn = document.createElement('button');
               btn.className = 'btn btn-sm btn-outline-primary';
               btn.innerHTML = '<span class="material-symbols-outlined" style="font-size:18px;">visibility</span>';
               btn.addEventListener('click', () => openDetalle(p.data?.id));
               return btn;
            },
         },
      ];
      const gridOptions = {
         columnDefs,
         rowData: [],
         rowSelection: { mode: 'singleRow' },
         pagination: true,
         paginationPageSize: 25,
         paginationPageSizeSelector: [25, 50, 100],
         onSelectionChanged: () => {
            const sel = gridApi.getSelectedRows();
            selectedRow = sel?.[0] ?? null;
            selectedId = selectedRow ? selectedRow.id : null;
            refreshToolbar();
         },
         theme: 'legacy', // para evitar warning si usas CSS themes
      };

      const el = document.getElementById('agendaGrid');
      gridApi = agGrid.createGrid(el, gridOptions);

      refreshToolbar();
   }

   async function loadAgenda() {
      const sedeId = sedeSel.value;
      const fecha = fechaInp.value;
      const estado = estadoSel.value;
      const q = qInp.value.trim();

      if (!sedeId || !fecha) return;

      const qs = new URLSearchParams({ sede_id: sedeId, fecha, estado, q });
      const j = await apiGet('/api/v1/admin/agenda?' + qs.toString());

      if (!j.ok) throw new Error(j?.error?.message || 'No se pudo cargar agenda');
      gridApi.setGridOption('rowData', j.data || []);

      selectedRow = null;
      selectedId = null;
      refreshToolbar();
   }

   async function openDetalle(id) {
      if (!id) return;

      const j = await apiGet('/api/v1/admin/agenda/show?id=' + encodeURIComponent(id));
      if (!j.ok) throw new Error(j?.error?.message || 'No se pudo cargar detalle');

      const c = j.data.cita;
      const ev = j.data.eventos || [];

      selectedId = c.id;

      $('detalle').innerHTML = `
      <div class="small text-muted mb-2">${c.sede_nombre} · ${c.tramite_nombre}</div>
      <div><b>Folio:</b> ${c.folio_publico || '-'}</div>
      <div><b>Fecha:</b> ${c.fecha} <b>Hora:</b> ${String(c.hora_inicio).slice(0, 5)} - ${String(c.hora_fin).slice(0, 5)}</div>
      <div><b>Estado:</b> ${c.estado}</div>
      <hr>
      <div><b>Nombre:</b> ${c.nombre || '-'}</div>
      <div><b>CURP/RFC:</b> ${c.curp_rfc || '-'}</div>
      <div><b>Email:</b> ${c.email || '-'}</div>
      <div><b>Teléfono:</b> ${c.telefono || '-'}</div>
    `;

      $('eventos').innerHTML = ev.map(e => {
         const payload = e.payload ? `<pre class="mb-0 small">${escapeHtml(JSON.stringify(e.payload, null, 2))}</pre>` : '';
         return `
        <div class="border rounded p-2 mb-2">
          <div class="d-flex justify-content-between">
            <div><b>${e.tipo}</b></div>
            <div class="text-muted small">${e.creado_en}</div>
          </div>
          <div class="text-muted small">${e.ip || ''}</div>
          ${payload}
        </div>
      `;
      }).join('');

      if (!modal) modal = new bootstrap.Modal(document.getElementById('agendaModal'));
      modal.show();
   }

   async function cambiarEstado(estado) {
      if (!selectedId) return;

      if (estado === 'cancelada') {
         if (!confirm('¿Cancelar esta cita?')) return;
      }

      const j = await apiPatch('/api/v1/admin/agenda/estado', { id: selectedId, estado });
      if (!j.ok) throw new Error(j?.error?.message || 'No se pudo cambiar estado');

      await loadAgenda();
      if (modal) await openDetalle(selectedId);
   }


   function escapeHtml(s) {
      return String(s)
         .replaceAll('&', '&amp;')
         .replaceAll('<', '&lt;')
         .replaceAll('>', '&gt;');
   }

   function todayLocalYYYYMMDD() {
      const d = new Date();
      const y = d.getFullYear();
      const m = String(d.getMonth() + 1).padStart(2, '0');
      const dd = String(d.getDate()).padStart(2, '0');
      return `${y}-${m}-${dd}`;
   }

   async function init() {
      buildGrid();

      fechaInp.value = todayLocalYYYYMMDD();

      await loadSedes();
      await loadAgenda();

      btnBuscar.addEventListener('click', loadAgenda);
      sedeSel.addEventListener('change', loadAgenda);
      fechaInp.addEventListener('change', loadAgenda);
      estadoSel.addEventListener('change', loadAgenda);
      btnEnAtencion?.addEventListener('click', () => cambiarEstado('en_atencion'));
      btnAtendida?.addEventListener('click', () => cambiarEstado('atendida'));
      btnNoAsistio?.addEventListener('click', () => cambiarEstado('no_asistio'));
      btnCancelar?.addEventListener('click', () => cambiarEstado('cancelada'));

      document.querySelector('#agendaModal .modal-footer')?.addEventListener('click', async (e) => {
         const btn = e.target.closest('button[data-estado]');
         if (!btn) return;
         await cambiarEstado(btn.getAttribute('data-estado'));
      });
   }

   function refreshToolbar() {
      if (!selectedRow) {
         btnEnAtencion.disabled = true;
         btnAtendida.disabled = true;
         btnNoAsistio.disabled = true;
         btnCancelar.disabled = true;
         if (selHint) selHint.textContent = 'Selecciona una cita';
         return;
      }

      const e = String(selectedRow.estado || '');
      const folio = selectedRow.folio_publico || '';
      if (selHint) selHint.textContent = `Seleccionada: ${folio} (${e})`;

      btnEnAtencion.disabled = !canAction(selectedRow, 'en_atencion');
      btnAtendida.disabled = !canAction(selectedRow, 'atendida');
      btnNoAsistio.disabled = !canAction(selectedRow, 'no_asistio');
      btnCancelar.disabled = !canAction(selectedRow, 'cancelada');
   }


   init().catch(err => {
      console.error(err);
      alert(err.message || 'Error');
   });
})();
