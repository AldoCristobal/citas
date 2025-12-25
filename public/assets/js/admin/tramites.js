(() => {
   const gridDiv = document.querySelector('#grid');
   if (!gridDiv) return;

   const btnRefresh = document.querySelector('#btnRefresh');
   const btnNew = document.querySelector('#btnNew');
   const btnEdit = document.querySelector('#btnEdit');
   const btnDelete = document.querySelector('#btnDelete');
   const q = document.querySelector('#q');

   const modalEl = document.querySelector('#tramiteModal');
   const modal = modalEl ? new bootstrap.Modal(modalEl) : null;

   const $id = document.querySelector('#tramite_id');
   const $nombre = document.querySelector('#tramite_nombre');
   const $dur = document.querySelector('#tramite_duracion');
   const $desc = document.querySelector('#tramite_descripcion');
   const $activo = document.querySelector('#tramite_activo');
   const $title = document.querySelector('#tramiteModalTitle');
   const $err = document.querySelector('#tramite_error');
   const btnSave = document.querySelector('#btnSaveTramite');

   let selected = null;

   const columnDefs = [
      { headerName: 'ID', field: 'id', width: 90 },
      { headerName: 'Nombre', field: 'nombre', flex: 1, minWidth: 240, filter: 'agTextColumnFilter' },
      { headerName: 'Duración (min)', field: 'duracion_min', width: 150 },
      { headerName: 'Activo', field: 'activo', width: 110, valueFormatter: p => (p.value ? 'Sí' : 'No') },
      { headerName: 'Descripción', field: 'descripcion', flex: 1, minWidth: 260 },
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

   function showError(msg) {
      $err.textContent = msg || 'Error';
      $err.classList.remove('d-none');
   }
   function clearError() {
      $err.textContent = '';
      $err.classList.add('d-none');
   }
   function setSaving(isSaving) {
      btnSave.disabled = isSaving;
      btnSave.textContent = isSaving ? 'Guardando...' : 'Guardar';
   }

   function resetForm() {
      $id.value = '';
      $nombre.value = '';
      $dur.value = '15';
      $desc.value = '';
      $activo.value = '1';
      clearError();
   }

   function fillForm(row) {
      $id.value = row.id ?? '';
      $nombre.value = row.nombre ?? '';
      $dur.value = String(row.duracion_min ?? 15);
      $desc.value = row.descripcion ?? '';
      $activo.value = String(row.activo ?? 1);
      clearError();
   }

   function readForm() {
      return {
         nombre: $nombre.value.trim(),
         duracion_min: Number($dur.value || 0),
         descripcion: $desc.value, // puede ser ''
         activo: Number($activo.value) ? 1 : 0,
      };
   }

   function validateForm(d) {
      if (!d.nombre || d.nombre.length < 3) return 'Nombre requerido (mín. 3 caracteres)';
      if (!Number.isFinite(d.duracion_min) || d.duracion_min <= 0 || d.duracion_min > 480) return 'Duración inválida';
      return null;
   }

   async function load() {
      const res = await window.api('/api/v1/admin/tramites');
      if (!res.ok) { alert(res.error?.message || 'Error al cargar trámites'); return; }
      gridApi.setGridOption('rowData', res.data);
      gridApi.deselectAll();
      selected = null;
      btnEdit.disabled = true;
      btnDelete.disabled = true;
   }

   async function createTramite(d) {
      return window.api('/api/v1/admin/tramites', { method: 'POST', body: JSON.stringify(d) });
   }

   async function updateTramite(id, d) {
      return window.api(`/api/v1/admin/tramites/${id}`, { method: 'PUT', body: JSON.stringify(d) });
   }

   async function deleteTramite(id) {
      return window.api(`/api/v1/admin/tramites/${id}`, { method: 'DELETE' });
   }

   btnRefresh.addEventListener('click', load);
   q.addEventListener('input', (e) => gridApi.setGridOption('quickFilterText', e.target.value));

   btnNew.addEventListener('click', () => {
      resetForm();
      $title.textContent = 'Nuevo trámite';
      modal?.show();
      setTimeout(() => $nombre.focus(), 50);
   });

   btnEdit.addEventListener('click', () => {
      if (!selected) return;
      fillForm(selected);
      $title.textContent = `Editar trámite #${selected.id}`;
      modal?.show();
      setTimeout(() => $nombre.focus(), 50);
   });

   btnDelete.addEventListener('click', async () => {
      if (!selected) return;
      const ok = confirm(`¿Desactivar el trámite "${selected.nombre}"?`);
      if (!ok) return;

      const res = await deleteTramite(selected.id);
      if (!res.ok) { alert(res.error?.message || 'No se pudo desactivar'); return; }

      await load();
   });

   btnSave.addEventListener('click', async () => {
      clearError();

      const id = Number($id.value || 0);
      const d = readForm();

      const msg = validateForm(d);
      if (msg) { showError(msg); return; }

      setSaving(true);
      try {
         const res = id > 0 ? await updateTramite(id, d) : await createTramite(d);
         if (!res.ok) { showError(res.error?.message || 'No se pudo guardar'); return; }
         modal?.hide();
         await load();
      } finally {
         setSaving(false);
      }
   });

   load();
})();
