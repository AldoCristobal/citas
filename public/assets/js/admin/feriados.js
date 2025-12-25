(() => {
   const sedeSel = document.querySelector('#sede_id');
   const mes = document.querySelector('#mes');
   const btnRefresh = document.querySelector('#btnRefresh');
   const btnNew = document.querySelector('#btnNew');
   const btnEdit = document.querySelector('#btnEdit');
   const btnDelete = document.querySelector('#btnDelete');
   const q = document.querySelector('#q');
   const gridDiv = document.querySelector('#grid');

   const modalEl = document.querySelector('#fModal');
   const modal = modalEl ? new bootstrap.Modal(modalEl) : null;

   const $id = document.querySelector('#f_id');
   const $fecha = document.querySelector('#f_fecha');
   const $desc = document.querySelector('#f_desc');
   const $sedeId = document.querySelector('#f_sede_id');
   const $activo = document.querySelector('#f_activo');
   const $title = document.querySelector('#fTitle');
   const $err = document.querySelector('#f_error');
   const btnSave = document.querySelector('#btnSaveF');

   let sedes = [];
   let selected = null;

   const columnDefs = [
      { headerName: 'ID', field: 'id', width: 90 },
      { headerName: 'Fecha', field: 'fecha', width: 140 },
      { headerName: 'Descripción', field: 'descripcion', flex: 1, minWidth: 260 },
      {
         headerName: 'Sede',
         field: 'sede_nombre',
         width: 220,
         valueFormatter: p => p.value || 'Global'
      },
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

   function yyyymmNow() {
      const d = new Date();
      const y = d.getFullYear();
      const m = String(d.getMonth() + 1).padStart(2, '0');
      return `${y}-${m}`;
   }

   async function loadSedes() {
      const res = await window.api('/api/v1/admin/sedes');
      if (!res.ok) { alert(res.error?.message || 'No se pudieron cargar sedes'); return; }
      sedes = res.data || [];

      sedeSel.innerHTML = '<option value="">Todas (incluye Global)</option>';
      $sedeId.innerHTML = '<option value="">Todas las sedes (Global)</option>';

      for (const s of sedes) {
         const opt1 = document.createElement('option');
         opt1.value = s.id;
         opt1.textContent = s.nombre;
         sedeSel.appendChild(opt1);

         const opt2 = document.createElement('option');
         opt2.value = s.id;
         opt2.textContent = s.nombre;
         $sedeId.appendChild(opt2);
      }
   }

   async function loadGrid() {
      const qs = new URLSearchParams();
      if (mes.value && /^\d{4}-\d{2}$/.test(mes.value)) qs.set('mes', mes.value);
      if (sedeSel.value) qs.set('sede_id', sedeSel.value);

      const url = '/api/v1/admin/feriados' + (qs.toString() ? `?${qs.toString()}` : '');
      const res = await window.api(url);
      if (!res.ok) { alert(res.error?.message || 'Error al cargar feriados'); return; }

      gridApi.setGridOption('rowData', res.data);
      gridApi.deselectAll();
      selected = null;
      btnEdit.disabled = true;
      btnDelete.disabled = true;
   }

   function resetForm() {
      $id.value = '';
      $fecha.value = '';
      $desc.value = '';
      $sedeId.value = '';
      $activo.value = '1';
      clearError();
   }

   function fillForm(r) {
      $id.value = r.id ?? '';
      $fecha.value = r.fecha ?? '';
      $desc.value = r.descripcion ?? '';
      $sedeId.value = (r.sede_id == null ? '' : String(r.sede_id));
      $activo.value = String(r.activo ?? 1);
      clearError();
   }

   function readForm() {
      return {
         fecha: $fecha.value,
         descripcion: $desc.value,
         sede_id: $sedeId.value === '' ? null : Number($sedeId.value),
         activo: Number($activo.value) ? 1 : 0,
      };
   }

   function validate(d) {
      if (!d.fecha) return 'Fecha requerida';
      return null;
   }

   const create = (d) => window.api('/api/v1/admin/feriados', { method: 'POST', body: JSON.stringify(d) });
   const update = (id, d) => window.api(`/api/v1/admin/feriados/${id}`, { method: 'PUT', body: JSON.stringify(d) });
   const del = (id) => window.api(`/api/v1/admin/feriados/${id}`, { method: 'DELETE' });

   btnRefresh.addEventListener('click', loadGrid);
   sedeSel.addEventListener('change', loadGrid);
   mes.addEventListener('change', loadGrid);
   q.addEventListener('input', (e) => gridApi.setGridOption('quickFilterText', e.target.value));

   btnNew.addEventListener('click', () => {
      resetForm();
      $title.textContent = 'Nuevo feriado';
      // prellenar mes actual si quieres
      modal?.show();
   });

   btnEdit.addEventListener('click', () => {
      if (!selected) return;
      resetForm();
      fillForm(selected);
      $title.textContent = `Editar feriado #${selected.id}`;
      modal?.show();
   });

   btnDelete.addEventListener('click', async () => {
      if (!selected) return;
      if (!confirm('¿Desactivar este feriado?')) return;
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
            ? await update(id, { fecha: d.fecha, descripcion: d.descripcion, sede_id: d.sede_id, activo: d.activo })
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
      mes.value = yyyymmNow();
      await loadGrid();
   })();
})();
