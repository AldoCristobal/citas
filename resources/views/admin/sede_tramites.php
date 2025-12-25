<?php
$title = 'Admin · Configuración por Sede';
$styles = ['/assets/css/admin/sede_tramites.css'];
$scripts = ['/assets/js/admin/sede_tramites.js'];

ob_start();
?>
<div class="page-header">
   <h4 class="mb-3">Sede · Trámites</h4>

   <div class="toolbar">
      <div class="tool-left">
         <label class="form-label mb-0 small text-muted">Sede</label>
         <select id="sede_id" class="form-select form-select-sm sede-select"></select>
      </div>

      <button id="btnRefresh" class="btn btn-outline-secondary btn-sm btn-icon">
         <span class="material-symbols-outlined">refresh</span> Actualizar
      </button>

      <button id="btnNew" class="btn btn-primary btn-sm btn-icon" disabled>
         <span class="material-symbols-outlined">add</span> Agregar trámite
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

<!-- Modal Sede-Trámite -->
<div class="modal fade" id="stModal" tabindex="-1" aria-hidden="true">
   <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title" id="stModalTitle">Configurar trámite</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
         </div>

         <div class="modal-body">
            <div class="row g-3">
               <input type="hidden" id="st_id">

               <div class="col-md-8">
                  <label class="form-label">Trámite *</label>
                  <select id="st_tramite_id" class="form-select"></select>
                  <div class="form-text" id="st_tramite_hint"></div>
               </div>

               <div class="col-md-4">
                  <label class="form-label">Activo</label>
                  <select id="st_activo" class="form-select">
                     <option value="1">Sí</option>
                     <option value="0">No</option>
                  </select>
               </div>

               <div class="col-md-4">
                  <label class="form-label">Cupo por slot *</label>
                  <input id="st_cupo" type="number" class="form-control" min="1" max="50" step="1" value="1">
                  <div class="form-text">Ej. 3 computadoras → cupo 3.</div>
               </div>

               <div class="col-md-4">
                  <label class="form-label">Slot (min) *</label>
                  <input id="st_slot" type="number" class="form-control" min="5" max="240" step="5" value="15">
                  <div class="form-text">Bloques de agenda para esa sede.</div>
               </div>

               <div class="col-md-4">
                  <label class="form-label">Ventana (días) *</label>
                  <input id="st_ventana" type="number" class="form-control" min="1" max="365" step="1" value="30">
                  <div class="form-text">Cuántos días hacia adelante se puede agendar.</div>
               </div>

               <div class="col-12">
                  <div id="st_error" class="alert alert-danger d-none mb-0"></div>
               </div>
            </div>
         </div>

         <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
            <button type="button" class="btn btn-primary btn-sm" id="btnSaveSt">Guardar</button>
         </div>
      </div>
   </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
