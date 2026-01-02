<?php
$title = 'Citas · Mi cita';
$styles = [];
$scripts = ['/assets/js/public/mi_cita.js'];

ob_start();
?>
<div class="row justify-content-center">
   <div class="col-lg-8">

      <div class="card p-4 mb-3">
         <h4 class="mb-1">Mi cita</h4>
         <div class="muted">Consulta o cancela con tu folio y token.</div>
      </div>

      <div class="card p-4 mb-3">
         <div class="row g-3">
            <div class="col-md-6">
               <label class="form-label">Folio</label>
               <input id="folio" class="form-control" placeholder="SED1-000001">
            </div>
            <div class="col-md-6">
               <label class="form-label">Token</label>
               <input id="token" class="form-control mono" placeholder="64 caracteres...">
            </div>

            <div class="col-12 d-flex gap-2">
               <button id="btnBuscar" class="btn btn-outline-light">Buscar</button>
               <button id="btnCancelar" class="btn btn-outline-danger" disabled>Cancelar</button>
               <div class="ms-auto muted small" id="hint"></div>
            </div>

            <div class="col-12">
               <div id="alert" class="alert alert-danger d-none mb-0"></div>
               <div id="ok" class="alert alert-success d-none mb-0"></div>
            </div>
         </div>
      </div>

      <div class="card p-4">
         <h5 class="mb-3">Detalle</h5>
         <div id="detalle" class="muted">Sin datos.</div>
      </div>

   </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
