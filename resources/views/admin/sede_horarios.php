<?php
$title = 'Admin · Horarios por Sede';
$styles = ['/assets/css/admin/sede_horarios.css'];
$scripts = ['/assets/js/admin/sede_horarios.js'];

ob_start();
?>
<div class="page-header">
   <h4 class="mb-3">Sede · Horarios</h4>

   <div class="toolbar">
      <div class="tool-left">
         <label class="form-label mb-0 small text-muted">Sede</label>
         <select id="sede_id" class="form-select form-select-sm sede-select"></select>
      </div>

      <button id="btnRefresh" class="btn btn-outline-secondary btn-sm btn-icon">
         <span class="material-symbols-outlined">refresh</span> Actualizar
      </button>

      <button id="btnNew" class="btn btn-primary btn-sm btn-icon" disabled>
         <span class="material-symbols-outlined">add</span> Agregar rango
      </button>

      <button id="btnEdit" class="btn btn-outline-primary btn-sm btn-icon" disabled>
         <span class="material-symbols-outlined">edit</span> Editar
      </button>

      <button id="btnDelete" class="btn btn-outline-danger btn-sm btn-icon" disabled>
         <span class="material-symbols-outlined">delete</span> Desactivar
      </button>

      <div class="ms-auto"></div>
      <input id="q" class="form-control form-control-sm q" placeholder="Buscar...">
   </div>
</div>

<div id="grid" class="ag-theme-quartz ag-wrap"></div>

<div class="modal fade" id="shModal" tabindex="-1" aria-hidden="true">
   <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title" id="shTitle">Rango horario</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>

         <div class="modal-body">
            <div class="row g-3">
               <input type="hidden" id="sh_id">

               <div class="col-md-4">
                  <label class="form-label">Día *</label>
                  <select id="sh_dow" class="form-select">
                     <option value="1">Lunes</option>
                     <option value="2">Martes</option>
                     <option value="3">Miércoles</option>
                     <option value="4">Jueves</option>
                     <option value="5">Viernes</option>
                     <option value="6">Sábado</option>
                     <option value="7">Domingo</option>
                  </select>
               </div>

               <div class="col-md-2">
                  <label class="form-label">Orden *</label>
                  <input id="sh_orden" type="number" class="form-control" min="1" max="10" step="1" value="1">
               </div>

               <div class="col-md-3">
                  <label class="form-label">Abre *</label>
                  <input id="sh_abre" type="time" class="form-control" value="09:00">
               </div>

               <div class="col-md-3">
                  <label class="form-label">Cierra *</label>
                  <input id="sh_cierra" type="time" class="form-control" value="17:00">
               </div>

               <div class="col-md-4">
                  <label class="form-label">Activo</label>
                  <select id="sh_activo" class="form-select">
                     <option value="1">Sí</option>
                     <option value="0">No</option>
                  </select>
               </div>

               <div class="col-12">
                  <div id="sh_error" class="alert alert-danger d-none mb-0"></div>
               </div>
            </div>
         </div>

         <div class="modal-footer">
            <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
            <button class="btn btn-primary btn-sm" id="btnSaveSh">Guardar</button>
         </div>
      </div>
   </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
