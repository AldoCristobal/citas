<?php
// resources/views/admin/sedes.php
$title = 'Admin · Sedes';
$styles = ['/assets/css/admin/sedes.css'];
$scripts = ['/assets/js/admin/sedes.js'];

ob_start();
?>
<div class="page-header">
   <h4 class="mb-3">Sedes</h4>

   <div class="toolbar">
      <button id="btnRefresh" class="btn btn-outline-secondary btn-sm btn-icon">
         <span class="material-symbols-outlined">refresh</span> Actualizar
      </button>

      <button id="btnNew" class="btn btn-primary btn-sm btn-icon">
         <span class="material-symbols-outlined">add</span> Nueva
      </button>

      <button id="btnEdit" class="btn btn-outline-primary btn-sm btn-icon" disabled>
         <span class="material-symbols-outlined">edit</span> Editar
      </button>

      <button id="btnDelete" class="btn btn-outline-danger btn-sm btn-icon" disabled>
         <span class="material-symbols-outlined">delete</span> Eliminar
      </button>

      <div class="ms-auto"></div>

      <input id="q" class="form-control form-control-sm q" placeholder="Buscar...">
   </div>
</div>

<div id="grid" class="ag-theme-quartz ag-wrap"></div>

<!-- Modal Sede -->
<div class="modal fade" id="sedeModal" tabindex="-1" aria-hidden="true">
   <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title" id="sedeModalTitle">Sede</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
         </div>

         <div class="modal-body">
            <div class="row g-3">
               <input type="hidden" id="sede_id">

               <div class="col-md-8">
                  <label class="form-label">Nombre *</label>
                  <input id="sede_nombre" class="form-control" maxlength="180" placeholder="Ej. Sede Puebla">
               </div>

               <div class="col-md-4">
                  <label class="form-label">Prefijo folio *</label>
                  <input id="sede_prefijo" class="form-control text-uppercase" maxlength="10" placeholder="Ej. PUE">
                  <div class="form-text">Se usa para folios: PUE-000001</div>
               </div>

               <div class="col-md-4">
                  <label class="form-label">Ciudad</label>
                  <input id="sede_ciudad" class="form-control" maxlength="120" placeholder="Ej. Puebla, Pue.">
               </div>

               <div class="col-md-4">
                  <label class="form-label">Teléfono</label>
                  <input id="sede_telefono" class="form-control" maxlength="30" placeholder="Ej. 2221234567">
               </div>

               <div class="col-md-4">
                  <label class="form-label">Activo</label>
                  <select id="sede_activo" class="form-select">
                     <option value="1">Sí</option>
                     <option value="0">No</option>
                  </select>
               </div>

               <div class="col-12">
                  <label class="form-label">Dirección</label>
                  <textarea id="sede_direccion" class="form-control" rows="2" maxlength="255"
                     placeholder="Calle, número, colonia, referencias..."></textarea>
               </div>

               <div class="col-12">
                  <div id="sede_error" class="alert alert-danger d-none mb-0"></div>
               </div>
            </div>
         </div>

         <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
               Cerrar
            </button>
            <button type="button" class="btn btn-primary btn-sm" id="btnSaveSede">
               Guardar
            </button>
         </div>
      </div>
   </div>
</div>

<?php
$content = ob_get_clean();

require __DIR__ . '/layout.php';
