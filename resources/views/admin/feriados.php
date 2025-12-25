<?php
$title = 'Admin · Feriados';
$styles = ['/assets/css/admin/feriados.css'];
$scripts = ['/assets/js/admin/feriados.js'];

ob_start();
?>
<div class="page-header">
   <h4 class="mb-3">Feriados</h4>

   <div class="toolbar">
      <div class="tool-left">
         <label class="form-label mb-0 small text-muted">Sede (opcional)</label>
         <select id="sede_id" class="form-select form-select-sm sede-select"></select>
      </div>

      <div class="tool-left">
         <label class="form-label mb-0 small text-muted">Mes</label>
         <input id="mes" class="form-control form-control-sm mes" placeholder="YYYY-MM">
      </div>

      <button id="btnRefresh" class="btn btn-outline-secondary btn-sm btn-icon">
         <span class="material-symbols-outlined">refresh</span> Actualizar
      </button>

      <button id="btnNew" class="btn btn-primary btn-sm btn-icon">
         <span class="material-symbols-outlined">add</span> Nuevo
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

<div class="modal fade" id="fModal" tabindex="-1" aria-hidden="true">
   <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title" id="fTitle">Feriado</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>

         <div class="modal-body">
            <div class="row g-3">
               <input type="hidden" id="f_id">

               <div class="col-md-4">
                  <label class="form-label">Fecha *</label>
                  <input id="f_fecha" type="date" class="form-control">
               </div>

               <div class="col-md-8">
                  <label class="form-label">Descripción</label>
                  <input id="f_desc" type="text" class="form-control" maxlength="150" placeholder="Ej. Año Nuevo">
               </div>

               <div class="col-md-6">
                  <label class="form-label">Aplica a</label>
                  <select id="f_sede_id" class="form-select">
                     <option value="">Todas las sedes (Global)</option>
                  </select>
               </div>

               <div class="col-md-3">
                  <label class="form-label">Activo</label>
                  <select id="f_activo" class="form-select">
                     <option value="1">Sí</option>
                     <option value="0">No</option>
                  </select>
               </div>

               <div class="col-12">
                  <div id="f_error" class="alert alert-danger d-none mb-0"></div>
               </div>
            </div>
         </div>

         <div class="modal-footer">
            <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
            <button class="btn btn-primary btn-sm" id="btnSaveF">Guardar</button>
         </div>
      </div>
   </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
