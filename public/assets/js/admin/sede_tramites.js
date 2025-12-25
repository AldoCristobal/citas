(() => {
   const sedeSel = document.querySelector('#sede_id');
   const btnRefresh = document.querySelector('#btnRefresh');
   const btnNew = document.querySelector('#btnNew');
   const btnEdit = document.querySelector('#btnEdit');
   const btnDelete = document.querySelector('#btnDelete');
   const q = document.querySelector('#q');

   const gridDiv = document.querySelector('#grid');
   if (!gridDiv || !sedeSel) return;

   // Modal
   const modalEl = document.querySelector('#stModal');
   const modal = modalEl ? new bootstrap.Modal(modalEl) : null;

   const $id = document.querySelector('#st_id');
   const $tramiteId = document.querySelector('#st_tramite_id');
   const $tramiteHint = document.querySelector('#st_tramite_hint');
   const $cupo = document.querySelector('#st_cupo');
   const $slot = document.querySelector('#st_slot');
   const $ventana = document.querySelector('#st_ventana');
   const $activo = document.querySelector('#st_activo');
   const $title = document.querySelector('#stModalTitle');
   const $err = document.querySelector('#st_error');
   const btnSave = document.querySelector('#btnSaveSt');

   let selected = null;
   let sedes = [];
   let tramites = [];

   const columnDefs = [
      { headerName: 'ID', field: 'id', width: 90 },
      { headerName: 'Trámite', field: 'tramite_nombre', flex: 1, minWidth: 260, filter: 'agTextColumnFilter' },
      { headerName: 'Duración (min)', field: 'tramite_duracion_min', width: 150 },

      { headerName: 'Slot (min)', field: 'slot_min', width: 120 },
      { headerName: 'Cupo/slot', field: 'cupo_por_slot', width: 120 },
      { headerName: 'Ventana (días)', field: 'ventana_dias', width: 140 },

      // ✅ NUEVO: slots requeridos
      {
         headerName: 'Slots por trámite',
         width: 150,
         valueGetter: (p) => {
            const dur = Number(p.data?.tramite_duracion_min || 0);
            const slot = Number(p.data?.slot_min || 0);
            if (!dur || !slot) return '';
            return Math.ceil(dur / slot);
         }
      },

      // ✅ OPCIONAL: tiempo total bloqueado
      {
         headerName: 'Tiempo bloqueado',
         width: 160,
         valueGetter: (p) => {
            const dur = Number(p.data?.tramite_duracion_min || 0);
            const slot = Number(p.data?.slot_min || 0);
            if (!dur || !slot) return '';
            const slots = Math.ceil(dur / slot);
            return `${slots * slot} min`;
         }
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

   function showError(msg) {
      $err.textContent = msg || 'Error';
      $err.classList.remove('d-none');
   }
   function clearError() {
      $err.textContent = '';
      $err.classList.add('d-none');
   }
   function setSaving(x) {
      btnSave.disabled = x;
      btnSave.textContent = x ? 'Guardando...' : 'Guardar';
   }

   function currentSedeId() {
      return Number(sedeSel.value || 0);
   }

   async function loadSedes() {
      const res = await window.api('/api/v1/admin/sedes');
      if (!res.ok) { alert(res.error?.message || 'No se pudieron cargar sedes'); return; }
      sedes = res.data || [];

      sedeSel.innerHTML = '';
      for (const s of sedes) {
         const opt = document.createElement('option');
         opt.value = s.id;
         opt.textContent = `${s.nombre}`;
         sedeSel.appendChild(opt);
      }

      btnNew.disabled = sedes.length === 0;
   }

   async function loadTramites() {
      const res = await window.api('/api/v1/admin/tramites');
      if (!res.ok) { alert(res.error?.message || 'No se pudieron cargar trámites'); return; }
      tramites = res.data || [];
   }

   function fillTramitesSelect(disabledIdSet) {
      $tramiteId.innerHTML = '';
      for (const t of tramites) {
         const opt = document.createElement('option');
         opt.value = t.id;
         opt.textContent = t.nombre;
         if (disabledIdSet && disabledIdSet.has(t.id)) opt.disabled = true;
         $tramiteId.appendChild(opt);
      }
      updateTramiteHint();
   }

   function updateTramiteHint() {
      const tid = Number($tramiteId.value || 0);
      const t = tramites.find(x => Number(x.id) === tid);

      const dur = Number(t?.duracion_min || 0);
      const slot = Number($slot.value || 0);

      if (!dur || !slot) {
         $tramiteHint.textContent = t ? `Duración del trámite: ${t.duracion_min} min` : '';
         return;
      }

      const slots = Math.ceil(dur / slot);
      $tramiteHint.textContent =
         `Duración: ${dur} min · Slot: ${slot} min · Slots: ${slots} · Bloqueado: ${slots * slot} min`;
   }


   function resetForm() {
      $id.value = '';
      $cupo.value = '1';
      $slot.value = '15';
      $ventana.value = '30';
      $activo.value = '1';
      clearError();
   }

   function fillForm(row) {
      $id.value = row.id ?? '';
      $cupo.value = String(row.cupo_por_slot ?? 1);
      $slot.value = String(row.slot_min ?? 15);
      $ventana.value = String(row.ventana_dias ?? 30);
      $activo.value = String(row.activo ?? 1);
      clearError();
   }

   function readForm() {
      return {
         sede_id: currentSedeId(),
         tramite_id: Number($tramiteId.value || 0),
         cupo_por_slot: Number($cupo.value || 0),
         slot_min: Number($slot.value || 0),
         ventana_dias: Number($ventana.value || 0),
         activo: Number($activo.value) ? 1 : 0,
      };
   }

   function validateForm(d) {
      if (d.sede_id <= 0) return 'Selecciona una sede';
      if (d.tramite_id <= 0) return 'Selecciona un trámite';
      if (!Number.isFinite(d.cupo_por_slot) || d.cupo_por_slot <= 0 || d.cupo_por_slot > 50) return 'Cupo inválido (1-50)';
      if (!Number.isFinite(d.slot_min) || d.slot_min < 5 || d.slot_min > 240) return 'Slot inválido (5-240)';
      if (!Number.isFinite(d.ventana_dias) || d.ventana_dias < 1 || d.ventana_dias > 365) return 'Ventana inválida (1-365)';
      return null;
   }

   async function loadGrid() {
      const sedeId = currentSedeId();
      if (sedeId <= 0) return;

      const res = await window.api(`/api/v1/admin/sede-tramites?sede_id=${encodeURIComponent(sedeId)}`);
      if (!res.ok) { alert(res.error?.message || 'Error al cargar configuración'); return; }

      gridApi.setGridOption('rowData', res.data);
      gridApi.deselectAll();
      selected = null;
      btnEdit.disabled = true;
      btnDelete.disabled = true;
   }

   async function createST(d) {
      return window.api('/api/v1/admin/sede-tramites', { method: 'POST', body: JSON.stringify(d) });
   }

   async function updateST(id, d) {
      return window.api(`/api/v1/admin/sede-tramites/${id}`, { method: 'PUT', body: JSON.stringify(d) });
   }

   async function deleteST(id) {
      return window.api(`/api/v1/admin/sede-tramites/${id}`, { method: 'DELETE' });
   }

   // Events
   btnRefresh.addEventListener('click', loadGrid);

   sedeSel.addEventListener('change', async () => {
      q.value = '';
      gridApi.setGridOption('quickFilterText', '');
      await loadGrid();
   });

   q.addEventListener('input', (e) => {
      gridApi.setGridOption('quickFilterText', e.target.value);
   });

   $tramiteId.addEventListener('change', updateTramiteHint);
   $slot.addEventListener('input', updateTramiteHint);

   btnNew.addEventListener('click', async () => {
      resetForm();
      $title.textContent = 'Agregar trámite a la sede';

      // deshabilitar trámites ya asignados (para no chocar con UNIQUE)
      const rows = [];
      gridApi.forEachNode(n => rows.push(n.data));
      const used = new Set(rows.map(r => Number(r.tramite_id)));

      fillTramitesSelect(used);

      // crear: tramite seleccionable
      $tramiteId.disabled = false;

      modal?.show();
      setTimeout(() => $tramiteId.focus(), 50);
   });

   btnEdit.addEventListener('click', () => {
      if (!selected) return;

      resetForm();
      fillForm(selected);
      $title.textContent = `Editar config (#${selected.id})`;

      // en edición NO se cambia trámite
      fillTramitesSelect(null);
      $tramiteId.value = String(selected.tramite_id);
      $tramiteId.disabled = true;
      updateTramiteHint();

      modal?.show();
      setTimeout(() => $cupo.focus(), 50);
   });

   btnDelete.addEventListener('click', async () => {
      if (!selected) return;
      const ok = confirm(`¿Desactivar "${selected.tramite_nombre}" para esta sede?`);
      if (!ok) return;

      const res = await deleteST(selected.id);
      if (!res.ok) { alert(res.error?.message || 'No se pudo desactivar'); return; }

      await loadGrid();
   });

   btnSave.addEventListener('click', async () => {
      clearError();

      const id = Number($id.value || 0);
      const d = readForm();
      const msg = validateForm(d);
      if (msg) { showError(msg); return; }

      setSaving(true);
      try {
         const res = id > 0
            ? await updateST(id, { cupo_por_slot: d.cupo_por_slot, slot_min: d.slot_min, ventana_dias: d.ventana_dias, activo: d.activo })
            : await createST(d);

         if (!res.ok) { showError(res.error?.message || 'No se pudo guardar'); return; }

         modal?.hide();
         await loadGrid();
      } finally {
         setSaving(false);
      }
   });

   // Init
   (async () => {
      await loadSedes();
      await loadTramites();
      if (sedes.length) {
         sedeSel.value = String(sedes[0].id);
         btnNew.disabled = false;
         fillTramitesSelect(null);
         await loadGrid();
      }
   })();
})();
