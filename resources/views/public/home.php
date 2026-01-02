<?php
$title = 'Citas · Inicio';
$styles = [];
$scripts = [];

ob_start();
?>
<div class="row justify-content-center">
   <div class="col-lg-8">
      <div class="card p-4">
         <h3 class="mb-2">Sistema de Citas</h3>
         <p class="muted mb-4">Agenda una cita o consulta/cancela con tu folio y token.</p>

         <div class="d-flex gap-2">
            <a class="btn btn-primary" href="/agendar">Agendar cita</a>
            <a class="btn btn-outline-light" href="/mi_cita">Mi cita</a>
         </div>
      </div>
   </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
