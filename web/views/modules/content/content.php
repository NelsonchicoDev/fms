<?php
/*=============================================
SEGURIDAD: Bloquear acceso a invitados
=============================================*/
if (empty($_SESSION["admin"])) {
	// Si no hay administrador logueado, mostrar mensaje amigable y detener la carga de módulos.
	echo '<div class="container mt-5 text-center p-5">
            <h3 class="text-muted">
                <i class="bi bi-lock me-2"></i> Por favor, inicie sesión para acceder al administrador de archivos.
            </h3>
          </div>';
	return;
}

/*=============================================
1. Cargar lista de archivos inicial (Página 1)
=============================================*/
$url = "relations?rel=files,folders&type=file,folder&orderBy=id_file&orderMode=DESC&startAt=0&endAt=15";
$method = "GET";
$fields = array();

$filesReq = CurlController::request($url, $method, $fields);

$files = array();
$totalFiles = 0;

// Validar que la petición fue exitosa y trae resultados
if (isset($filesReq->status) && $filesReq->status == 200 && !empty($filesReq->results)) {

	$files = $filesReq->results;

	/*=============================================
    2. Traer el total de archivos existentes en BD
    =============================================*/
	$urlTotal = "files?select=id_file";
	$totalReq = CurlController::request($urlTotal, $method, $fields);

	// MANEJO DE ERRORES SEGURO: Verificar que la propiedad 'total' exista
	if (isset($totalReq->status) && $totalReq->status == 200 && isset($totalReq->total)) {
		// Asegurarnos de que sea un número entero
		$totalFiles = (int) ceil($totalReq->total / 15);
	}
}
?>

<div class="container-fluid p-4 min-vh-100" id="content">

	<div class="container bg-white border rounded shadow-sm">

		<div class="row py-4 px-4 pb-1">

			<?php
			include "modules/search/search.php";
			include "modules/buttons/buttons.php";
			?>

		</div>

		<div class="row pb-4 px-4 py-1">

			<?php
			include "modules/folders/folders.php";
			include "modules/filters/filters.php";
			?>

		</div>

		<?php
		include "modules/drag_drop/drag_drop.php";
		include "modules/list/list.php";
		include "modules/grid/grid.php";
		?>

		<div id="scrollControl" class="py-1"></div>

		<input type="hidden" id="totalPages" value="<?php echo htmlspecialchars($totalFiles, ENT_QUOTES, 'UTF-8'); ?>">
		<input type="hidden" id="currentPage" value="1">

	</div>
</div>