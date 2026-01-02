(() => {
   const $ = (id) => document.getElementById(id);

   const sedeSel = $('sede_id');

   const btnLlamar = $('btnLlamar');
   const btnAtendida = $('btnAtendida');
   const btnNoAsistio = $('btnNoAsistio');
   const btnCancelar = $('btnCancelar');
   const btnRefrescar = $('btnRefrescar');
   const btnSiguiente = $('btnSiguiente');
   const selHint = $('selHint');

   let gridApi = null;
   let gridOptions = null;

   let selectedRow = null;
   let selectedId = null;

   function getApi() {
      // en algunas versiones createGrid regresa api; en otras, está en gridOptions.api
      return gridApi || gridOptions?.api || null;
   }

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
      const api = getApi();

      if (api && typeof api.setGridOption === 'function') {
         api.setGridOption('rowData', rows);
         return;
      }
      if (api && typeof api.setRowData === 'function') {
         api.setRowData(rows);
         return;
      }
      if (gridOptions) gridOptions.rowData = rows;
   }

   function clearSelection() {
      selectedRow = null;
      selectedId = null;

      const api = getApi();
      if (api?.deselectAll) api.deselectAll();

      refreshToolbar();
   }

   function selectNode(node) {
      const api = getApi();
      if (!api || !node) return;

      api.deselectAll?.();
      node.setSelected?.(true);

      selectedRow = node.data || null;
      selectedId = selectedRow ? selectedRow.id : null;

      refreshToolbar();
   }

   function selectById(id) {
      const api = getApi();
      if (!api || !id) return false;

      let found = null;
      api.forEachNodeAfterFilterAndSort?.((node) => {
         if (found) return;
         if (node?.data?.id === id) found = node;
      });

      if (found) {
         selectNode(found);
         return true;
      }
      return false;
   }

   // Selecciona la primera confirmada; si no hay, opcionalmente la primera en_atencion; si no, limpia.
   function autoSelectNextPreferida() {
      const api = getApi();
      if (!api) return;

      let nodeToSelect = null;

      api.forEachNodeAfterFilterAndSort?.((node) => {
         if (nodeToSelect) return;
         if (node?.data?.estado === 'confirmada') nodeToSelect = node;
      });

      // opcional: si no hay confirmadas, seleccionar la primera en_atencion
      if (!nodeToSelect) {
         api.forEachNodeAfterFilterAndSort?.((node) => {
            if (nodeToSelect) return;
            if (node?.data?.estado === 'en_atencion') nodeToSelect = node;
         });
      }

      if (nodeToSelect) {
         selectNode(nodeToSelect);
      } else {
         clearSelection();
      }
   }

   function buildGrid() {
      const columnDefs = [
         { field: 'hora', headerName: 'Hora', width: 90, sortable: true },
         { field: 'folio_publico', headerName: 'Folio', width: 140, sortable: true },
         { field: 'nombre', headerName: 'Nombre', flex: 1, minWidth: 180, sortable: true },
         { field: 'curp_rfc', headerName: 'CURP/RFC', width: 150, sortable: true },
         { field: 'tramite_nombre', headerName: 'Trámite', width: 160, sortable: true },
         { headerName: 'Estado', field: 'estado', width: 140, sortable: true, cellRenderer: (p) => badgeEstado(p.data?.estado) },
      ];

      gridOptions = {
         columnDefs,
         rowData: [],
         rowSelection: 'single',
         pagination: false,
         suppressRowClickSelection: false,

         // click actualiza selección SIEMPRE
         onRowClicked: (e) => {
            if (!e?.node) return;
            selectNode(e.node);
         },

         // por si hay selección por teclado o API
         onSelectionChanged: () => {
            const api = getApi();
            const sel = api?.getSelectedRows?.() || [];
            if (sel.length) {
               selectedRow = sel[0];
               selectedId = selectedRow?.id ?? null;
            } else {
               selectedRow = null;
               selectedId = null;
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

   /**
    * loadTurnos() con control de selección:
    * - preferId: intenta re-seleccionar ese id si existe
    * - si no existe (o no se pasa), auto-selecciona la primera confirmada
    */
   async function loadTurnos({ preferId = null } = {}) {
      const sedeId = sedeSel.value;
      if (!sedeId) return;

      const j = await apiGet('/api/v1/admin/turnos/hoy?sede_id=' + encodeURIComponent(sedeId));
      if (!j.ok) throw new Error(j?.error?.message || 'No se pudieron cargar turnos');

      const rows = j.data || [];
      setRowData(rows);

      // Selección controlada
      if (preferId && selectById(preferId)) return;

      autoSelectNextPreferida();
   }

   function selectNextConfirmada() {
      const api = getApi();
      if (!api) return;

      const nodes = [];
      api.forEachNodeAfterFilterAndSort?.((n) => nodes.push(n));

      if (!nodes.length) {
         alert('No hay turnos para hoy.');
         clearSelection();
         return;
      }

      const curId = selectedId;
      let startIdx = -1;
      if (curId) startIdx = nodes.findIndex(n => n?.data?.id === curId);

      const findFrom = (from) => {
         for (let i = from; i < nodes.length; i++) {
            if (nodes[i]?.data?.estado === 'confirmada') return nodes[i];
         }
         return null;
      };

      let next = findFrom(startIdx + 1);
      if (!next) next = findFrom(0);

      if (next) {
         selectNode(next);
      } else {
         alert('No hay turnos en estado confirmada.');
      }
   }

   function isCierre(estado) {
      return ['atendida', 'no_asistio', 'cancelada'].includes(String(estado || ''));
   }

   async function cambiarEstado(estado) {
      if (!selectedId) { alert('Selecciona un turno'); return; }
      if (estado === 'cancelada' && !confirm('¿Cancelar este turno?')) return;

      const idAntes = selectedId;

      const j = await apiPatch('/api/v1/admin/agenda/estado', { id: idAntes, estado });

      // conflicto típico (otro operador lo movió)
      if (!j.ok) {
         const msg = j?.error?.message || 'No se pudo cambiar estado';
         alert(msg);

         // si fue 409, refresca para traer estado real
         if ((j?.error?.code === 'INVALID') || (j?.status === 409)) {
            await loadTurnos({ preferId: idAntes });
         }
         return;
      }

      // ✅ refresca: si fue "Llamar" mantenemos id; si fue cierre avanzamos
      if (!isCierre(estado)) {
         await loadTurnos({ preferId: idAntes }); // en_atencion -> mantener
         return;
      }

      await loadTurnos();        // cierre -> autoSelect primera confirmada
      // si quieres que sea "siguiente confirmada a partir de la actual", usa:
      // selectNextConfirmada();
   }

   async function init() {
      buildGrid();
      await loadSedes();
      await loadTurnos();

      sedeSel.addEventListener('change', async () => {
         clearSelection();
         await loadTurnos();
      });

      btnRefrescar.addEventListener('click', () => loadTurnos({ preferId: selectedId }));

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