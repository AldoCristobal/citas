// public/assets/js/admin/sedes.js
(() => {
   const gridDiv = document.querySelector('#grid');
   if (!gridDiv) return;

   const btnRefresh = document.querySelector('#btnRefresh');
   const btnNew = document.querySelector('#btnNew');
   const btnEdit = document.querySelector('#btnEdit');
   const btnDelete = document.querySelector('#btnDelete');
   const q = document.querySelector('#q');

   // Modal refs
   const modalEl = document.querySelector('#sedeModal');
   const modal = modalEl ? new bootstrap.Modal(modalEl) : null;

   const $id = document.querySelector('#sede_id');
   const $nombre = document.querySelector('#sede_nombre');
   const $prefijo = document.querySelector('#sede_prefijo');
   const $ciudad = document.querySelector('#sede_ciudad');
   const $telefono = document.querySelector('#sede_telefono');
   const $direccion = document.querySelector('#sede_direccion');
   const $activo = document.querySelector('#sede_activo');
   const $title = document.querySelector('#sedeModalTitle');
   const $err = document.querySelector('#sede_error');
   const btnSave = document.querySelector('#btnSaveSede');

   let selected = null;

   const columnDefs = [
      { headerName: 'ID', field: 'id', width: 90 },
      { headerName: 'Nombre', field: 'nombre', flex: 1, minWidth: 220, filter: 'agTextColumnFilter' },
      { headerName: 'Prefijo', field: 'prefijo_folio', width: 120 },
      { headerName: 'Ciudad', field: 'ciudad', flex: 1, minWidth: 160 },
      { headerName: 'Dirección', field: 'direccion', flex: 1, minWidth: 220 },
      { headerName: 'Teléfono', field: 'telefono', width: 160 },
      { headerName: 'Activo', field: 'activo', width: 110, valueFormatter: p => (p.value ? 'Sí' : 'No') },
   ];

   const gridOptions = {
      theme: "legacy", // AG Grid v35 + CSS legacy
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
      if (!$err) return;
      $err.textContent = msg || 'Error';
      $err.classList.remove('d-none');
   }
   function clearError() {
      if (!$err) return;
      $err.textContent = '';
      $err.classList.add('d-none');
   }

   function setSaving(isSaving) {
      if (!btnSave) return;
      btnSave.disabled = isSaving;
      btnSave.textContent = isSaving ? 'Guardando...' : 'Guardar';
   }

   function resetForm() {
      $id.value = '';
      $nombre.value = '';
      $prefijo.value = '';
      $ciudad.value = '';
      $telefono.value = '';
      $direccion.value = '';
      $activo.value = '1';
      clearError();
   }

   function fillForm(row) {
      $id.value = row.id ?? '';
      $nombre.value = row.nombre ?? '';
      $prefijo.value = row.prefijo_folio ?? '';
      $ciudad.value = row.ciudad ?? '';
      $telefono.value = row.telefono ?? '';
      $direccion.value = row.direccion ?? '';
      $activo.value = String(row.activo ?? 1);
      clearError();
   }

   function readForm() {
      return {
         nombre: $nombre.value.trim(),
         prefijo_folio: $prefijo.value.trim().toUpperCase(),
         ciudad: $ciudad.value.trim(),
         telefono: $telefono.value.trim(),
         direccion: $direccion.value.trim(),
         activo: Number($activo.value) ? 1 : 0,
      };
   }

   function validateForm(d) {
      if (!d.nombre || d.nombre.length < 3) return 'Nombre requerido (mín. 3 caracteres)';
      if (!d.prefijo_folio || d.prefijo_folio.length < 2) return 'Prefijo requerido (mín. 2 caracteres)';
      if (!/^[A-Z0-9_-]+$/.test(d.prefijo_folio)) return 'Prefijo inválido (solo A-Z, 0-9, _ o -)';
      return null;
   }

   async function load() {
      const res = await window.api('/api/v1/admin/sedes');
      if (!res.ok) {
         alert(res.error?.message || 'Error al cargar sedes');
         return;
      }
      gridApi.setGridOption('rowData', res.data);
      gridApi.deselectAll();
      selected = null;
      btnEdit.disabled = true;
      btnDelete.disabled = true;
   }

   async function createSede(d) {
      return window.api('/api/v1/admin/sedes', {
         method: 'POST',
         body: JSON.stringify(d),
      });
   }

   async function updateSede(id, d) {
      return window.api(`/api/v1/admin/sedes/${id}`, {
         method: 'PUT',
         body: JSON.stringify(d),
      });
   }

   async function deleteSede(id) {
      return window.api(`/api/v1/admin/sedes/${id}`, {
         method: 'DELETE',
      });
   }

   // Eventos
   btnRefresh.addEventListener('click', load);

   q.addEventListener('input', (e) => {
      gridApi.setGridOption('quickFilterText', e.target.value);
   });

   btnNew.addEventListener('click', () => {
      resetForm();
      $title.textContent = 'Nueva sede';
      modal?.show();
      setTimeout(() => $nombre.focus(), 50);
   });

   btnEdit.addEventListener('click', () => {
      if (!selected) return;
      fillForm(selected);
      $title.textContent = `Editar sede #${selected.id}`;
      modal?.show();
      setTimeout(() => $nombre.focus(), 50);
   });

   btnDelete.addEventListener('click', async () => {
      if (!selected) return;
      const ok = confirm(`¿Eliminar la sede "${selected.nombre}"?\n\nEsto la desactivará (soft delete).`);
      if (!ok) return;

      const res = await deleteSede(selected.id);
      if (!res.ok) {
         alert(res.error?.message || 'No se pudo eliminar');
         return;
      }
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
         const res = id > 0 ? await updateSede(id, d) : await createSede(d);

         if (!res.ok) {
            showError(res.error?.message || 'No se pudo guardar');
            return;
         }

         modal?.hide();
         await load();
      } finally {
         setSaving(false);
      }
   });

   // Inicial
   load();
})();
