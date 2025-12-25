<?php
$title = 'Admin · Login';
$styles = ['/assets/css/admin/admin.css'];
$scripts = ['/assets/js/admin/login.js'];

ob_start();
?>
<div class="container" style="max-width:420px; padding-top:70px;">
   <div class="card">
      <div class="card-body">
         <h5 class="mb-3">Iniciar sesión</h5>

         <div class="mb-2">
            <label class="form-label">Email</label>
            <input id="email" class="form-control" value="admin@citas.local">
         </div>

         <div class="mb-3">
            <label class="form-label">Password</label>
            <input id="password" type="password" class="form-control" value="Admin123!">
         </div>

         <button id="btnLogin" class="btn btn-primary w-100">Entrar</button>
         <div id="msg" class="text-danger mt-2" style="display:none;"></div>
      </div>
   </div>
</div>
<?php
$content = ob_get_clean();
require __DIR__ . '/layout.php';
