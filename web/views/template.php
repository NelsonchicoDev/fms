<?php 

/*=============================================
Iniciar variables de sesión
=============================================*/

ob_start();
session_start();

/*=============================================
Zona Horaria
=============================================*/

date_default_timezone_set("America/Bogota");

/*=============================================
Capturar las rutas de la URL 
=============================================*/

$routesArray = explode("/",$_SERVER["REQUEST_URI"]);
array_shift($routesArray);

foreach ($routesArray as $key => $value) {
	
	$routesArray[$key] = explode("?",$value)[0];
	
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>FMS | File Manager System</title>
	<link rel="icon" href="https://cdn.filestackcontent.com/1LIBQrPFRuGylthi5tUp">

	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Lato:300,400,600,500,700">

	<link rel="stylesheet" href="/views/assets/plugins/bootstrap5/bootstrap.min.css">

	<!-- https://icons.getbootstrap.com/ -->
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.3/font/bootstrap-icons.min.css">

	<!-- https://fontawesome.com/v5/search -->
	<link rel="stylesheet" href="/views/assets/plugins/fontawesome-free/css/all.min.css"> 

	<link rel="stylesheet" href="/views/assets/plugins/jquery-ui/jquery-ui.css">

	<link rel="stylesheet" href="/views/assets/plugins/material-preloader/material-preloader.css">

	<link rel="stylesheet" href="/views/assets/plugins/toastr/toastr.min.css">

	<link rel="stylesheet" href="/views/assets/css/fms/fms.css">

	<script src="/views/assets/plugins/jquery/jquery.min.js"></script>

	<script src="/views/assets/plugins/jquery-ui/jquery-ui.js"></script>

	<script src="/views/assets/plugins/bootstrap5/bootstrap.bundle.min.js"></script>

	<script src="/views/assets/js/alerts/alerts.js"></script>

	<script src="/views/assets/plugins/sweetalert/sweetalert.min.js"></script>

	<script src="/views/assets/plugins/material-preloader/material-preloader.js"></script>

	<script src="/views/assets/plugins/toastr/toastr.min.js"></script>

</head>
<body>

	<?php if (!empty($routesArray[0])): ?>

		<?php 

		if ($routesArray[0] == "logout"){

			include "pages/".$routesArray[0]."/".$routesArray[0].".php";

		}else{
	
			include "modules/top/top.php";
			include "modules/content/content.php";
			include "modules/up/up.php";
			include "modules/modal/modal.php";

		}
	
		?>
		
	<?php else: ?>

	<?php 

		include "modules/top/top.php";
		include "modules/content/content.php";
		include "modules/up/up.php";
		include "modules/modal/modal.php";

	 ?>
	
	<script src="/views/assets/js/fms/fms.js"></script>

	<?php endif ?>
	
</body>
</html>