(() => {
   const $ = (id) => document.getElementById(id);

   const sedeSel = $('sede_id');

   const btnLlamar = $('btnLlamar');
   const btnAtendida = $('btnAtendida');
   const btnNoAsistio = $('btnNoAsistio');
   const btnCancelar = $('btnCancelar');
   const btnRefrescar = $('btnRefrescar');
   const selHint = $('selHint');
   const btnSiguiente = $('btnSiguiente');
   
   let lastRows = [];

   let gridApi = null;
   let gridOptions = null;

   let selectedRow = null;
   let selectedId = null;

   function authHeaders() {
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

   function badgeEstado(estado) {
      const e = String(estado || '');
      let cls = 'secondary';
      if (e === 'confirmada') cls = 'primary';
      else if (e === 'en_atencion') cls = 'info';

      const span = document.createElement('span');
      span.className = `badge bg-${cls}`;
      span.textContent = e;
      return span;
   }

   function canAction(row, action) {
      const e = String(row?.estado || '');
      if (e === 'confirmada') return ['en_atencion', 'cancelada', 'no_asistio'].includes(action);
      if (e === 'en_atencion') return ['atendida', 'no_asistio', 'cancelada'].includes(action);
      return false;
   }

   function refreshToolbar() {
      if (!selectedRow) {
         btnLlamar.disabled = true;
         btnAtendida.disabled = true;
         btnNoAsistio.disabled = true;
         btnCancelar.disabled = true;
         if (selHint) selHint.textContent = 'Selecciona un turno';
         return;
      }

      const e = String(selectedRow.estado || '');
      const folio = selectedRow.folio_publico || '';
      if (selHint) selHint.textContent = `Seleccionado: ${folio} (${e})`;

      btnLlamar.disabled = !canAction(selectedRow, 'en_atencion');
      btnAtendida.disabled = !canAction(selectedRow, 'atendida');
      btnNoAsistio.disabled = !canAction(selectedRow, 'no_asistio');
      btnCancelar.disabled = !canAction(selectedRow, 'cancelada');
   }

   // Compatible con varias versiones de AG Grid
   function setRowData(rows) {
      if (gridApi && typeof gridApi.setGridOption === 'function') {
         gridApi.setGridOption('rowData', rows);
         return;
      }
      if (gridOptions?.api && typeof gridOptions.api.setGridOption === 'function') {
         gridOptions.api.setGridOption('rowData', rows);
         return;
      }
      if (gridOptions?.api && typeof gridOptions.api.setRowData === 'function') {
         gridOptions.api.setRowData(rows);
         return;
      }
      if (gridOptions) gridOptions.rowData = rows;
   }

   function clearSelection() {
      selectedRow = null;
      selectedId = null;
      if (gridOptions?.api?.deselectAll) gridOptions.api.deselectAll();
      refreshToolbar();
   }

   function buildGrid() {
      const columnDefs = [
         { field: 'hora', headerName: 'Hora', width: 90 },
         { field: 'folio_publico', headerName: 'Folio', width: 140 },
         { field: 'nombre', headerName: 'Nombre', flex: 1, minWidth: 180 },
         { field: 'curp_rfc', headerName: 'CURP/RFC', width: 150 },
         { field: 'tramite_nombre', headerName: 'Trámite', width: 160 },
         { headerName: 'Estado', field: 'estado', width: 140, cellRenderer: (p) => badgeEstado(p.data?.estado) },
      ];

      gridOptions = {
         columnDefs,
         rowData: [],
         // ✅ más compatible que { mode: 'singleRow' }
         rowSelection: 'single',
         pagination: false,

         // ✅ click siempre actualiza selección + toolbar
         onRowClicked: (e) => {
            selectedRow = e.data || null;
            selectedId = selectedRow ? selectedRow.id : null;

            // fuerza selección visual
            if (gridOptions?.api?.deselectAll) gridOptions.api.deselectAll();
            if (e.node?.setSelected) e.node.setSelected(true);

            refreshToolbar();
         },

         // ✅ por si hay selección vía teclado o API
         onSelectionChanged: () => {
            const sel = gridOptions?.api?.getSelectedRows?.() || [];
            if (sel.length) {
               selectedRow = sel[0];
               selectedId = selectedRow?.id ?? null;
            }
            refreshToolbar();
         },

         theme: 'legacy',
      };

      const el = document.getElementById('turnosGrid');
      gridApi = agGrid.createGrid(el, gridOptions);
      refreshToolbar();
   }

   async function loadSedes() {
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

   async function loadTurnos() {
      const sedeId = sedeSel.value;
      if (!sedeId) return;

      const j = await apiGet('/api/v1/admin/turnos/hoy?sede_id=' + encodeURIComponent(sedeId));
      if (!j.ok) throw new Error(j?.error?.message || 'No se pudieron cargar turnos');

      setRowData(j.data || []);
      lastRows = j.data || [];
      setRowData(lastRows);

      // auto-selecciona primera confirmada
      autoSelectNext();
      clearSelection();
   }

   function autoSelectNext() {
  if (!gridOptions?.api) return;

  // busca primera confirmada
  let nodeToSelect = null;

  gridOptions.api.forEachNodeAfterFilterAndSort((node) => {
    if (nodeToSelect) return;
    if (node?.data?.estado === 'confirmada') nodeToSelect = node;
  });

  // si no hay confirmadas, toma la primera en_atencion (opcional)
  if (!nodeToSelect) {
    gridOptions.api.forEachNodeAfterFilterAndSort((node) => {
      if (nodeToSelect) return;
      if (node?.data?.estado === 'en_atencion') nodeToSelect = node;
    });
  }

  if (nodeToSelect) {
    gridOptions.api.deselectAll();
    nodeToSelect.setSelected(true);
    selectedRow = nodeToSelect.data;
    selectedId = selectedRow?.id ?? null;
    refreshToolbar();
  } else {
    clearSelection();
  }
}

   function selectNextConfirmada() {
      if (!gridOptions?.api) return;

      const nodes = [];
      gridOptions.api.forEachNodeAfterFilterAndSort((n) => nodes.push(n));

      // índice actual
      const curId = selectedId;
      let startIdx = -1;
      if (curId) startIdx = nodes.findIndex(n => n?.data?.id === curId);

      // busca siguiente confirmada desde (startIdx + 1), luego desde inicio
      const findFrom = (from) => {
         for (let i = from; i < nodes.length; i++) {
            if (nodes[i]?.data?.estado === 'confirmada') return nodes[i];
         }
         return null;
      };

      let next = findFrom(startIdx + 1);
      if (!next) next = findFrom(0);

      if (next) {
         gridOptions.api.deselectAll();
         next.setSelected(true);
         selectedRow = next.data;
         selectedId = selectedRow?.id ?? null;
         refreshToolbar();
      } else {
         alert('No hay más confirmadas en fila.');
      }
   }

   async function cambiarEstado(estado) {
      if (!selectedId) { alert('Selecciona un turno'); return; }
      if (estado === 'cancelada' && !confirm('¿Cancelar este turno?')) return;

      const j = await apiPatch('/api/v1/admin/agenda/estado', { id: selectedId, estado });
      if (!j.ok) throw new Error(j?.error?.message || 'No se pudo cambiar estado');

      await loadTurnos();
   }

   async function init() {
      buildGrid();
      await loadSedes();
      await loadTurnos();

      sedeSel.addEventListener('change', loadTurnos);
      btnRefrescar.addEventListener('click', loadTurnos);

      btnLlamar.addEventListener('click', () => cambiarEstado('en_atencion'));
      btnAtendida.addEventListener('click', () => cambiarEstado('atendida'));
      btnNoAsistio.addEventListener('click', () => cambiarEstado('no_asistio'));
      btnCancelar.addEventListener('click', () => cambiarEstado('cancelada'));
      btnSiguiente?.addEventListener('click', selectNextConfirmada);
      
   }

   init().catch(err => {
      console.error(err);
      alert(err.message || 'Error');
   });
})();
