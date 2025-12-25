<?php
$title = 'Admin · Turnos';
$styles = ['/assets/css/admin/turnos.css'];      // opcional (puede ser [])
$scripts = ['/assets/js/oper/turnos.js'];       // aquí va el JS

ob_start();
?>
<div class="page-header">
   <h4 class="mb-3">Turnos (Hoy)</h4>

   <div class="toolbar">
      <div style="min-width: 260px;">
         <select id="sede_id" class="form-select form-select-sm"></select>
      </div>

      <button id="btnLlamar" class="btn btn-outline-secondary btn-sm btn-icon" disabled title="Llamar (En atención)">
         <span class="material-symbols-outlined">campaign</span> Llamar
      </button>

      <button id="btnAtendida" class="btn btn-outline-secondary btn-sm btn-icon" disabled title="Atendida">
         <span class="material-symbols-outlined">task_alt</span> Atendida
      </button>

      <button id="btnNoAsistio" class="btn btn-outline-secondary btn-sm btn-icon" disabled title="No asistió">
         <span class="material-symbols-outlined">person_off</span> No asistió
      </button>

      <button id="btnCancelar" class="btn btn-outline-danger btn-sm btn-icon" disabled title="Cancelar">
         <span class="material-symbols-outlined">cancel</span> Cancelar
      </button>
      <button id="btnSiguiente" class="btn btn-outline-primary btn-sm btn-icon" title="Siguiente">
         <span class="material-symbols-outlined">skip_next</span> Siguiente
      </button>

      <div class="ms-auto"></div>

      <button id="btnRefrescar" class="btn btn-outline-secondary btn-sm btn-icon" title="Refrescar">
         <span class="material-symbols-outlined">refresh</span> Actualizar
      </button>
   </div>

   <div class="small text-muted mt-2" id="selHint">Selecciona un turno</div>
</div>

<div id="turnosGrid" class="ag-theme-quartz ag-wrap"></div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
