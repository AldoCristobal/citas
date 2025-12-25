(() => {
   const sedeSel = document.querySelector('#sede_id');
   const btnRefresh = document.querySelector('#btnRefresh');
   const btnNew = document.querySelector('#btnNew');
   const btnEdit = document.querySelector('#btnEdit');
   const btnDelete = document.querySelector('#btnDelete');
   const q = document.querySelector('#q');
   const gridDiv = document.querySelector('#grid');
   if (!gridDiv || !sedeSel) return;

   const modalEl = document.querySelector('#shModal');
   const modal = modalEl ? new bootstrap.Modal(modalEl) : null;

   const $id = document.querySelector('#sh_id');
   const $dow = document.querySelector('#sh_dow');
   const $orden = document.querySelector('#sh_orden');
   const $abre = document.querySelector('#sh_abre');
   const $cierra = document.querySelector('#sh_cierra');
   const $activo = document.querySelector('#sh_activo');
   const $title = document.querySelector('#shTitle');
   const $err = document.querySelector('#sh_error');
   const btnSave = document.querySelector('#btnSaveSh');

   let sedes = [];
   let selected = null;

   const dowName = (n) => (['', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'][Number(n) || 0] || '');

   const columnDefs = [
      { headerName: 'ID', field: 'id', width: 90 },
      { headerName: 'Día', field: 'dow', width: 120, valueFormatter: p => dowName(p.value) },
      { headerName: 'Orden', field: 'orden', width: 110 },
      { headerName: 'Abre', field: 'abre', width: 120 },
      { headerName: 'Cierra', field: 'cierra', width: 120 },
      { headerName: 'Activo', field: 'activo', width: 110, valueFormatter: p => (p.value ? 'Sí' : 'No') },
   ];

   const gridOptions = {
      theme: "legacy",
      columnDefs,
      defaultColDef: { sortable: true, resizable: true, filter: true },
      rowSelection: { mode: 'singleRow', enableClickSelection: true },
      animateRows: true,
      pagination: true,
      paginationPageSize: 25,
      paginationPageSizeSelector: [25, 50, 100],
      onSelectionChanged: (ev) => {
         const rows = ev.api.getSelectedRows();
         selected = rows && rows.length ? rows[0] : null;
         btnEdit.disabled = !selected;
         btnDelete.disabled = !selected;
      }
   };

   const gridApi = agGrid.createGrid(gridDiv, gridOptions);

   const showError = (m) => { $err.textContent = m || 'Error'; $err.classList.remove('d-none'); };
   const clearError = () => { $err.textContent = ''; $err.classList.add('d-none'); };
   const setSaving = (x) => { btnSave.disabled = x; btnSave.textContent = x ? 'Guardando...' : 'Guardar'; };
   const sedeId = () => Number(sedeSel.value || 0);

   async function loadSedes() {
      const res = await window.api('/api/v1/admin/sedes');
      if (!res.ok) { alert(res.error?.message || 'No se pudieron cargar sedes'); return; }
      sedes = res.data || [];
      sedeSel.innerHTML = '';
      for (const s of sedes) {
         const opt = document.createElement('option');
         opt.value = s.id;
         opt.textContent = s.nombre;
         sedeSel.appendChild(opt);
      }
      btnNew.disabled = sedes.length === 0;
   }

   async function loadGrid() {
      const id = sedeId();
      if (id <= 0) return;

      const res = await window.api(`/api/v1/admin/sede-horarios?sede_id=${encodeURIComponent(id)}`);
      if (!res.ok) { alert(res.error?.message || 'Error al cargar horarios'); return; }

      gridApi.setGridOption('rowData', res.data);
      gridApi.deselectAll();
      selected = null;
      btnEdit.disabled = true;
      btnDelete.disabled = true;
   }

   function resetForm() {
      $id.value = '';
      $dow.value = '1';
      $orden.value = '1';
      $abre.value = '09:00';
      $cierra.value = '17:00';
      $activo.value = '1';
      clearError();
   }

   function fillForm(r) {
      $id.value = r.id ?? '';
      $dow.value = String(r.dow ?? 1);
      $orden.value = String(r.orden ?? 1);
      $abre.value = String(r.abre ?? '09:00').slice(0, 5);
      $cierra.value = String(r.cierra ?? '17:00').slice(0, 5);
      $activo.value = String(r.activo ?? 1);
      clearError();
   }

   function readForm() {
      return {
         sede_id: sedeId(),
         dow: Number($dow.value || 0),
         orden: Number($orden.value || 0),
         abre: $abre.value,
         cierra: $cierra.value,
         activo: Number($activo.value) ? 1 : 0
      };
   }

   function validate(d) {
      if (d.sede_id <= 0) return 'Selecciona sede';
      if (d.dow < 1 || d.dow > 7) return 'Día inválido';
      if (d.orden < 1 || d.orden > 10) return 'Orden inválido';
      if (!d.abre || !d.cierra) return 'Abre/Cierra requeridos';
      if (d.abre >= d.cierra) return 'Abre debe ser menor que Cierra';
      return null;
   }

   const create = (d) => window.api('/api/v1/admin/sede-horarios', { method: 'POST', body: JSON.stringify(d) });
   const update = (id, d) => window.api(`/api/v1/admin/sede-horarios/${id}`, { method: 'PUT', body: JSON.stringify(d) });
   const del = (id) => window.api(`/api/v1/admin/sede-horarios/${id}`, { method: 'DELETE' });

   btnRefresh.addEventListener('click', loadGrid);
   sedeSel.addEventListener('change', loadGrid);
   q.addEventListener('input', (e) => gridApi.setGridOption('quickFilterText', e.target.value));

   btnNew.addEventListener('click', () => {
      resetForm();
      $title.textContent = 'Agregar rango';
      modal?.show();
   });

   btnEdit.addEventListener('click', () => {
      if (!selected) return;
      resetForm();
      fillForm(selected);
      $title.textContent = `Editar rango #${selected.id}`;
      modal?.show();
   });

   btnDelete.addEventListener('click', async () => {
      if (!selected) return;
      if (!confirm('¿Desactivar este rango?')) return;
      const res = await del(selected.id);
      if (!res.ok) { alert(res.error?.message || 'No se pudo desactivar'); return; }
      await loadGrid();
   });

   btnSave.addEventListener('click', async () => {
      clearError();
      const id = Number($id.value || 0);
      const d = readForm();
      const msg = validate(d);
      if (msg) { showError(msg); return; }

      setSaving(true);
      try {
         const res = id > 0
            ? await update(id, { dow: d.dow, orden: d.orden, abre: d.abre, cierra: d.cierra, activo: d.activo })
            : await create(d);

         if (!res.ok) { showError(res.error?.message || 'No se pudo guardar'); return; }
         modal?.hide();
         await loadGrid();
      } finally {
         setSaving(false);
      }
   });

   (async () => {
      await loadSedes();
      if (sedes.length) {
         sedeSel.value = String(sedes[0].id);
         btnNew.disabled = false;
         await loadGrid();
      }
   })();
})();
