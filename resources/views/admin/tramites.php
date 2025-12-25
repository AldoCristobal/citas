<?php
$title = 'Admin · Trámites';
$styles = ['/assets/css/admin/tramites.css'];
$scripts = ['/assets/js/admin/tramites.js'];

ob_start();
?>
<div class="page-header">
   <h4 class="mb-3">Trámites</h4>

   <div class="toolbar">
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

<!-- Modal Tramite -->
<div class="modal fade" id="tramiteModal" tabindex="-1" aria-hidden="true">
   <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title" id="tramiteModalTitle">Trámite</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
         </div>

         <div class="modal-body">
            <div class="row g-3">
               <input type="hidden" id="tramite_id">

               <div class="col-md-8">
                  <label class="form-label">Nombre *</label>
                  <input id="tramite_nombre" class="form-control" maxlength="150" placeholder="Ej. Renovación">
               </div>

               <div class="col-md-4">
                  <label class="form-label">Duración (min) *</label>
                  <input id="tramite_duracion" type="number" class="form-control" min="5" max="480" step="5" value="15">
                  <div class="form-text">Se usa para calcular slots del trámite.</div>
               </div>

               <div class="col-12">
                  <label class="form-label">Descripción</label>
                  <textarea id="tramite_descripcion" class="form-control" rows="3"
                     placeholder="Opcional..."></textarea>
               </div>

               <div class="col-md-4">
                  <label class="form-label">Activo</label>
                  <select id="tramite_activo" class="form-select">
                     <option value="1">Sí</option>
                     <option value="0">No</option>
                  </select>
               </div>

               <div class="col-12">
                  <div id="tramite_error" class="alert alert-danger d-none mb-0"></div>
               </div>
            </div>
         </div>

         <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
            <button type="button" class="btn btn-primary btn-sm" id="btnSaveTramite">Guardar</button>
         </div>
      </div>
   </div>
</div>


<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
