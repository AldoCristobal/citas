<?php
$title = 'Agendar cita';
$styles = [
   '/assets/css/public/public.css',
   '/assets/css/public/calendar.css',
];
$scripts = [
   // ✅ SweetAlert2 (externo)
   'https://cdn.jsdelivr.net/npm/sweetalert2@11',

   '/assets/js/public/agendar.js',
];

ob_start();
?>

<div class="row row-cards">
   <!-- CARD PRINCIPAL -->
   <div class="col-12">
      <div class="card">
         <div class="card-body">
            <h3 class="card-title mb-1">Agendar cita</h3>
            <div class="text-muted">Selecciona tu sede, trámite y una fecha disponible.</div>

            <div class="row g-3 mt-3">
               <div class="col-md-6">
                  <label class="form-label">Sede</label>
                  <select id="sede_id" class="form-select">
                     <option value="">Cargando sedes…</option>
                  </select>
               </div>

               <div class="col-md-6">
                  <label class="form-label">Trámite</label>
                  <select id="tramite_id" class="form-select" disabled>
                     <option value="">Selecciona sede primero</option>
                  </select>
               </div>
            </div>

            <div id="legend" class="cal-legend mt-4">
               <span class="cal-legend__item"><span class="cal-legend__swatch cal-legend__swatch--ok"></span> Disponible</span>
               <span class="cal-legend__item"><span class="cal-legend__swatch cal-legend__swatch--no"></span> Sin cupo</span>
               <span class="cal-legend__item"><span class="cal-legend__swatch cal-legend__swatch--off"></span> Fuera de ventana</span>
            </div>

            <div class="public-booking mt-2">
               <div class="public-left">
                  <div class="monthbar">
                     <button id="btnPrev" type="button" class="btn btn-outline-secondary btn-sm" title="Mes anterior">‹</button>
                     <div id="mesLabel" class="monthbar__label">—</div>
                     <button id="btnNext" type="button" class="btn btn-outline-secondary btn-sm" title="Mes siguiente">›</button>
                  </div>

                  <!-- ✅ Loader del calendario (3s) -->
                  <div id="calLoader" class="d-none" style="min-height: 320px; display:flex; align-items:center; justify-content:center; border:1px dashed rgba(0,0,0,.08); border-radius:10px;">
                     <div class="spinner-border" role="status" aria-label="Cargando" style="width: 2.2rem; height: 2.2rem;"></div>
                  </div>


                  <div id="cal" class="cal"></div>
               </div>

               <div class="public-right">
                  <div class="card">
                     <div class="card-body">
                        <div id="dayHint" class="text-muted mb-2">Selecciona un día</div>

                        <div class="mb-3">
                           <label class="form-label">Horario</label>
                           <select id="hora_sel" class="form-select" disabled>
                              <option value="">Selecciona una fecha</option>
                           </select>
                        </div>

                        <button id="btnAgendar" type="button" class="btn btn-primary w-100" disabled>
                           Agendar
                        </button>

                        <!-- ✅ Loader removido de aquí -->

                        <div id="err" class="alert alert-danger d-none mt-3 mb-0"></div>
                     </div>
                  </div>
               </div>
            </div>

         </div>
      </div>
   </div>

   <!-- CARD ABAJO: FORMULARIO -->
   <div class="col-12">
      <div id="formCardWrap" class="card d-none">
         <div class="card-body">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
               <div>
                  <h3 class="card-title mb-1">Datos del solicitante</h3>
                  <div class="text-muted">Completa tus datos para confirmar la cita.</div>
               </div>
               <span id="holdMini" class="badge bg-warning-lt d-none">HOLD activo</span>
            </div>

            <!-- ✅ Loader del formulario (3s antes de mostrar) -->
            <div id="formLoader" class="d-none mt-3" style="border:1px dashed rgba(0,0,0,.08); border-radius:10px; padding:14px;">
               <div class="d-flex align-items-center gap-3">
                  <div class="spinner-border" role="status" aria-label="Cargando" style="width: 1.8rem; height: 1.8rem;"></div>
                  <div>
                     <div class="fw-semibold">Preparando formulario…</div>
                     <div class="small text-muted">Por favor espera</div>
                  </div>
               </div>
            </div>

            <!-- CONTADOR HOLD -->
            <div id="holdBar" class="alert alert-warning d-none mt-3 mb-0">
               <div class="d-flex align-items-center justify-content-between gap-2">
                  <div>
                     <div class="fw-semibold">Reserva temporal activa</div>
                     <div class="small text-muted">Tienes este tiempo para confirmar.</div>
                  </div>
                  <div class="text-end">
                     <div class="fw-bold" style="font-variant-numeric: tabular-nums;">
                        <span id="holdCountdown">10:00</span>
                     </div>
                     <div class="small text-muted">mm:ss</div>
                  </div>
               </div>
            </div>

            <div class="hr-text">Identidad</div>

            <div class="row g-2">
               <div class="col-md-4">
                  <label class="form-label">Nombre</label>
                  <input id="f_nombre" class="form-control" placeholder="Ej. Juan">
               </div>
               <div class="col-md-4">
                  <label class="form-label">Apellido paterno</label>
                  <input id="f_apellido_paterno" class="form-control" placeholder="Ej. Pérez">
               </div>
               <div class="col-md-4">
                  <label class="form-label">Apellido materno</label>
                  <input id="f_apellido_materno" class="form-control" placeholder="Ej. López">
               </div>

               <div class="col-md-6">
                  <label class="form-label">CURP</label>
                  <input id="f_curp_rfc" class="form-control" placeholder="Ej. ABCD900101HDF...">
                  <div class="small text-muted mt-1">Se autocompleta fecha de nacimiento y edad si la CURP es válida.</div>
               </div>

               <div class="col-md-3">
                  <label class="form-label">Fecha de nacimiento (opcional)</label>
                  <input id="f_fecha_nacimiento" type="date" class="form-control">
               </div>

               <div class="col-md-3">
                  <label class="form-label">Edad (opcional)</label>
                  <input id="f_edad" type="number" min="0" max="125" class="form-control" placeholder="Ej. 29">
               </div>

               <div class="col-md-6">
                  <label class="form-label">Teléfono</label>
                  <input id="f_telefono" class="form-control" placeholder="Ej. 2221234567">
               </div>

               <div class="col-md-6">
                  <label class="form-label">Email</label>
                  <input id="f_email" type="email" class="form-control" placeholder="correo@ejemplo.com">
               </div>
            </div>

            <div class="hr-text">Dirección</div>

            <div class="row g-2">
               <div class="col-md-6">
                  <label class="form-label">Calle</label>
                  <input id="f_calle" class="form-control" placeholder="Ej. Av. Reforma">
               </div>

               <div class="col-md-3">
                  <label class="form-label">Número exterior</label>
                  <input id="f_numero_exterior" class="form-control" placeholder="Ej. 123">
               </div>

               <div class="col-md-3">
                  <label class="form-label">Número interior (opcional)</label>
                  <input id="f_numero_interior" class="form-control" placeholder="Ej. 4B">
               </div>

               <div class="col-md-6">
                  <label class="form-label">Colonia</label>
                  <input id="f_colonia" class="form-control" placeholder="Ej. Centro">
               </div>

               <div class="col-md-2">
                  <label class="form-label">C.P.</label>
                  <input id="f_codigo_postal" class="form-control" placeholder="Ej. 72000" maxlength="10">
               </div>

               <div class="col-md-2">
                  <label class="form-label">Estado</label>
                  <input id="f_estado" class="form-control" placeholder="Ej. Puebla">
               </div>

               <div class="col-md-2">
                  <label class="form-label">Municipio</label>
                  <input id="f_municipio" class="form-control" placeholder="Ej. Puebla">
               </div>

               <div class="col-12 d-grid mt-2">
                  <button id="btnConfirmar" type="button" class="btn btn-success" disabled>
                     Confirmar cita
                  </button>
               </div>
            </div>

            <input type="hidden" id="hold_token">
         </div>
      </div>
   </div>
</div>

<div id="comprobanteWrap" class="d-none">
   <div class="card mt-3">
      <div class="card-body">
         <div class="d-flex justify-content-between align-items-start gap-2">
            <div>
               <h3 class="card-title mb-1">Comprobante de cita</h3>
               <div class="text-muted">Guárdalo o imprímelo.</div>
            </div>
            <div class="text-end">
               <div class="fw-bold" id="cmpFolio">—</div>
               <div class="small text-muted">Folio</div>
            </div>
         </div>

         <div class="hr-text">Datos de la cita</div>
         <div class="row g-2">
            <div class="col-md-6">
               <div class="text-muted small">Sede</div>
               <div id="cmpSede" class="fw-semibold">—</div>
            </div>
            <div class="col-md-3">
               <div class="text-muted small">Fecha</div>
               <div id="cmpFecha" class="fw-semibold">—</div>
            </div>
            <div class="col-md-3">
               <div class="text-muted small">Hora</div>
               <div id="cmpHora" class="fw-semibold">—</div>
            </div>
            <div class="col-md-12">
               <div class="text-muted small">Trámite</div>
               <div id="cmpTramite" class="fw-semibold">—</div>
            </div>
         </div>

         <div class="hr-text">Datos del solicitante</div>
         <div class="row g-2">
            <div class="col-md-4">
               <div class="text-muted small">Nombre</div>
               <div id="cmpNombre">—</div>
            </div>
            <div class="col-md-4">
               <div class="text-muted small">Apellidos</div>
               <div id="cmpApellidos">—</div>
            </div>
            <div class="col-md-4">
               <div class="text-muted small">CURP</div>
               <div id="cmpCurp">—</div>
            </div>

            <div class="col-md-4">
               <div class="text-muted small">Teléfono</div>
               <div id="cmpTel">—</div>
            </div>
            <div class="col-md-4">
               <div class="text-muted small">Email</div>
               <div id="cmpEmail">—</div>
            </div>
            <div class="col-md-4">
               <div class="text-muted small">Nacimiento / Edad</div>
               <div id="cmpNacEdad">—</div>
            </div>

            <div class="col-md-12">
               <div class="text-muted small">Dirección</div>
               <div id="cmpDir">—</div>
            </div>
         </div>

         <div class="d-flex gap-2 mt-3">
            <button id="btnImprimirCmp" class="btn btn-outline-secondary">Imprimir</button>
            <button id="btnCerrarCmp" class="btn btn-primary">Cerrar</button>
         </div>

         <input type="hidden" id="cmpAccessToken">
      </div>
   </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
