<!--=====================================
MODAL LOGIN
======================================-->

<?php
/*=============================================
Procesamiento del formulario (Idealmente esto va ANTES del HTML)
=============================================*/
require_once "controllers/admins.controller.php";

// Generar un token CSRF si no existe en la sesión
if (empty($_SESSION['csrf_token'])) {
	$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Ejecutar controlador de login
$login = new AdminsController();
$login->login();
?>

<div class="modal fade" id="myLogin" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content rounded shadow-lg border-0">

			<form method="POST" id="formLogin">

				<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

				<div class="modal-header bg-dark text-white">
					<h4 class="modal-title" id="loginModalLabel">
						<i class="bi bi-box-arrow-in-right me-2"></i>Acceso al Sistema
					</h4>
					<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
				</div>

				<div class="modal-body px-5 pb-4 pt-4">

					<h3 class="mb-4 text-center fw-bold text-secondary">File Manager System</h3>

					<div class="form-floating mb-3">
						<input type="email" class="form-control rounded" id="email" placeholder="correo@ejemplo.com" name="email_admin" required autocomplete="username">
						<label for="email"><i class="bi bi-envelope me-2"></i>Correo Electrónico</label>
					</div>

					<div class="form-floating mb-3">
						<input type="password" class="form-control rounded" id="pwd" placeholder="Ingrese contraseña" name="password_admin" required autocomplete="current-password">
						<label for="pwd"><i class="bi bi-lock me-2"></i>Contraseña</label>
					</div>

				</div>

				<div class="modal-footer bg-light d-flex justify-content-between rounded-bottom">
					<button type="button" class="btn btn-outline-secondary rounded" data-bs-dismiss="modal">Cancelar</button>
					<button type="submit" class="btn btn-primary rounded px-4 shadow-sm">Ingresar</button>
				</div>

			</form>

		</div>
	</div>
</div>