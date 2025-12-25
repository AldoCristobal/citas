<?php
$title = 'Agenda';
$styles = [];
$scripts = ['/assets/js/admin/agenda.js'];

ob_start();
?>
<div class="d-flex align-items-center justify-content-between mb-3">
   <h1 class="h4 m-0">Agenda</h1>
</div>

<div class="card">
   <div class="card-body">

      <div class="row g-2 align-items-end mb-3">
         <div class="col-12 col-md-4">
            <label class="form-label">Sede</label>
            <select id="sede_id" class="form-select"></select>
         </div>

         <div class="col-12 col-md-3">
            <label class="form-label">Fecha</label>
            <input id="fecha" type="date" class="form-control">
         </div>

         <div class="col-12 col-md-3">
            <label class="form-label">Estado</label>
            <select id="estado" class="form-select">
               <option value="">Todos</option>
               <option value="confirmada">Confirmada</option>
               <option value="en_atencion">En atención</option>
               <option value="atendida">Atendida</option>
               <option value="no_asistio">No asistió</option>
               <option value="cancelada">Cancelada</option>
               <option value="reprogramada">Reprogramada</option>
            </select>
         </div>

         <div class="col-12 col-md-2 d-grid">
            <button id="btnBuscar" class="btn btn-primary">
               <span class="material-symbols-outlined" style="font-size:18px;vertical-align:middle;">search</span>
               <span class="ms-1">Buscar</span>
            </button>
         </div>

         <div class="col-12 col-md-6">
            <label class="form-label">Buscar (folio/nombre/curp/email)</label>
            <input id="q" type="text" class="form-control" placeholder="Ej: PUE-000123 o CURP...">
         </div>
      </div>

      <div class="d-flex gap-2 mb-2 align-items-center">
         <button id="btnEnAtencion" class="btn btn-sm btn-outline-secondary" disabled title="En atención">
            <span class="material-symbols-outlined" style="font-size:18px;vertical-align:middle;">play_circle</span>
         </button>

         <button id="btnAtendida" class="btn btn-sm btn-outline-secondary" disabled title="Atendida">
            <span class="material-symbols-outlined" style="font-size:18px;vertical-align:middle;">task_alt</span>
         </button>

         <button id="btnNoAsistio" class="btn btn-sm btn-outline-secondary" disabled title="No asistió">
            <span class="material-symbols-outlined" style="font-size:18px;vertical-align:middle;">person_off</span>
         </button>

         <button id="btnCancelar" class="btn btn-sm btn-outline-secondary" disabled title="Cancelar">
            <span class="material-symbols-outlined" style="font-size:18px;vertical-align:middle;">cancel</span>
         </button>

         <div class="ms-auto small text-muted" id="selHint">Selecciona una cita</div>
      </div>


      <div id="agendaGrid" class="ag-theme-quartz" style="height: 520px; width: 100%;"></div>

   </div>
</div>

<!-- Modal detalle -->
<div class="modal fade" id="agendaModal" tabindex="-1">
   <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title">Detalle de cita</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
         </div>
         <div class="modal-body">
            <div id="detalle"></div>
            <hr>
            <h6 class="mb-2">Eventos</h6>
            <div id="eventos"></div>
         </div>
         <div class="modal-footer">
            <div class="me-auto d-flex gap-2">
               <button class="btn btn-outline-secondary btn-sm" data-estado="en_atencion">En atención</button>
               <button class="btn btn-outline-success btn-sm" data-estado="atendida">Atendida</button>
               <button class="btn btn-outline-warning btn-sm" data-estado="no_asistio">No asistió</button>
               <button class="btn btn-outline-danger btn-sm" data-estado="cancelada">Cancelar</button>
            </div>
            <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
         </div>
      </div>
   </div>
</div>

<?php
$content = ob_get_clean();
require APP_ROOT . '/resources/views/admin/layout.php';
