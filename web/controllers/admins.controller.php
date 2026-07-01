<?php

class AdminsController
{

	/*=============================================
	Login de administradores
	=============================================*/

	public function login()
	{

		if (isset($_POST["email_admin"]) && isset($_POST["password_admin"])) {

			/*=============================================
			1. Validaciones y Sanitización de Input
			=============================================*/
			// Forzar que los inputs sean strings (evita inyecciones de arreglos)
			$emailInput = (string)$_POST["email_admin"];
			$passwordInput = (string)$_POST["password_admin"];

			// Sanitizar y validar el formato del correo
			$email = filter_var($emailInput, FILTER_SANITIZE_EMAIL);

			if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
				echo '<script>
					if(typeof fncMatPreloader === "function") fncMatPreloader("off");
					if(typeof fncToastr === "function") fncToastr("error", "Formato de correo electrónico inválido");
				</script>';
				return;
			}

			/*=============================================
			2. Inicio visual del Loader
			=============================================*/
			echo '<script>
				if(typeof fncMatPreloader === "function") fncMatPreloader("on");
				if(typeof fncSweetAlert === "function") fncSweetAlert("loading", "Cargando...", "");
			</script>';

			/*=============================================
			3. Petición a la API
			=============================================*/
			$url = "admins?login=true&suffix=admin";
			$method = "POST";
			$fields = array(
				"email_admin" => $email,
				"password_admin" => $passwordInput
			);

			$login = CurlController::request($url, $method, $fields);

			/*=============================================
			4. Validación estricta de la respuesta
			=============================================*/
			if (isset($login->status) && $login->status == 200 && !empty($login->results)) {

				/*=============================================
				SEGURIDAD CRÍTICA: Prevenir Session Fixation
				=============================================*/
				if (session_status() === PHP_SESSION_ACTIVE) {
					session_regenerate_id(true); // Borra la sesión vieja y crea un ID nuevo seguro
				}

				// Almacenar en sesión el objeto de usuario
				$_SESSION["admin"] = $login->results[0];

				// Sanitizar el token antes de inyectarlo en JS (XSS Protection)
				$token = htmlspecialchars($login->results[0]->token_admin ?? '', ENT_QUOTES, 'UTF-8');

				echo '<script>
					localStorage.setItem("token-admin", "' . $token . '");
					window.location = "/";
				</script>';
			} else {

				/*=============================================
				SEGURIDAD XSS: Sanitizar errores de la API
				=============================================*/
				$errorMsg = "Error en las credenciales.";

				if (isset($login->results) && is_string($login->results)) {
					// Escapar comillas y tags HTML para evitar inyección en el script JS
					$errorMsg = htmlspecialchars($login->results, ENT_QUOTES, 'UTF-8');
				}

				echo '<script>
					if(typeof fncFormatInputs === "function") fncFormatInputs();
					if(typeof fncMatPreloader === "function") fncMatPreloader("off");
					if(typeof fncToastr === "function") fncToastr("error", "' . $errorMsg . '");
				</script>';
			}
		}
	}
}
