<?php

define('DIR', __DIR__);

ini_set("display_errors", 1);
ini_set("log_errors", 1);
ini_set("error_log", DIR . "/php_error_log");

require_once "../controllers/template.controller.php";
require_once "../controllers/curl.controller.php";
require_once "../extensions/vendor/autoload.php";

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Api\Admin\AdminApi;

use Vimeo\Vimeo;
use Vimeo\Exceptions\VimeoUploadException;

class FilesController
{

	public $file;
	public $folder;
	public $token;

	/*=============================================
	Subir Archivos a los Servidores
	=============================================*/
	public function ajaxUploadFiles()
	{

		/*=============================================
		Traer info del folder
		=============================================*/
		$url = "folders?linkTo=id_folder&equalTo=" . $this->folder;
		$method = "GET";
		$fields = array();

		$folder = CurlController::request($url, $method, $fields);

		if ($folder->status != 200 || empty($folder->results)) {
			echo json_encode(["status" => 404, "error" => "Carpeta no encontrada."]);
			return;
		}

		$folder = $folder->results[0];

		/*=============================================
		Validar el peso máximo del archivo
		=============================================*/
		if ($this->file["size"] > $folder->max_upload_folder) {
			echo json_encode([
				"status" => 404,
				"error" => "Los archivos que pesan más de " . ($folder->max_upload_folder / 1000000) . "MB no suben al servidor " . $folder->name_folder
			]);
			return;
		}

		/*=============================================
		SEGURIDAD: Validar extensión y evitar RCE
		=============================================*/
		$originalName = basename($this->file["name"]);
		$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

		// Lista blanca de extensiones seguras (Ajustar según necesidad)
		$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'zip', 'mp4', 'mov', 'avi', 'doc', 'docx', 'xls', 'xlsx'];

		if (!in_array($extension, $allowedExtensions)) {
			echo json_encode(["status" => 403, "error" => "Tipo de archivo no permitido por seguridad."]);
			return;
		}

		/*=============================================
		Creamos el nombre seguro del archivo
		=============================================*/
		$fileName = uniqid() . "_" . time() . "." . $extension;
		$baseFileName = pathinfo($originalName, PATHINFO_FILENAME);

		/*=============================================
		Subiendo archivos al servidor propio (Carpeta 1)
		=============================================*/
		if ($this->folder == 1) {

			$path = "../views/assets/files/" . $fileName;

			if (move_uploaded_file($this->file["tmp_name"], $path)) {

				$link_file = str_replace("..", $folder->url_folder, $path);

				$this->saveFileToDB($this->folder, $extension, $baseFileName, $this->file["type"], $this->file["size"], $link_file);
			} else {
				echo json_encode(["status" => 500, "error" => "Error al guardar el archivo localmente."]);
			}
			return;
		}

		/*=============================================
		Subiendo archivos a AWS (Carpeta 2)
		=============================================*/
		if ($this->folder == 2) {

			$keys = explode("|", $folder->keys_folder);
			$s3 = new S3Client([
				'version' => 'latest',
				'region'  => 'us-east-2',
				'credentials' => [
					'key' => $keys[0] ?? '',
					'secret' => $keys[1] ?? ''
				]
			]);

			try {
				$result = $s3->putObject([
					'Bucket' => $folder->url_folder,
					'Key'    => $fileName,
					'Body'   => file_get_contents($this->file["tmp_name"]),
					'ContentType' => mime_content_type($this->file["tmp_name"]), // Mejor que confiar en el cliente
					'ACL'    => 'public-read'
				]);

				if ($result['@metadata']['statusCode'] == 200) {
					$this->saveFileToDB($this->folder, $extension, $baseFileName, $this->file["type"], $this->file["size"], $result['@metadata']['effectiveUri']);
				}
			} catch (S3Exception $e) {
				echo json_encode(["status" => 404, "error" => "Error de AWS S3: " . $e->getMessage()]);
			}
			return;
		}

		/*=============================================
		Subiendo archivos a Cloudinary (Carpeta 3)
		=============================================*/
		if ($this->folder == 3) {

			$keys = explode("|", $folder->keys_folder);
			Configuration::instance('cloudinary://' . $keys[0] . ':' . $keys[1] . '@' . $folder->url_folder . '?secure=true');

			try {
				$result = (new UploadApi())->upload($this->file["tmp_name"], ['resource_type' => 'auto']);

				if (isset($result['secure_url'])) {
					$this->saveFileToDB($this->folder, $extension, $baseFileName, $this->file["type"], $this->file["size"], $result['secure_url']);
				} else {
					echo json_encode(["status" => 404, "error" => "Error desconocido en Cloudinary."]);
				}
			} catch (Exception $e) {
				echo json_encode(["status" => 404, "error" => "Error de Cloudinary: " . $e->getMessage()]);
			}
			return;
		}

		/*=============================================
		Subiendo archivos a Vimeo (Carpeta 4)
		=============================================*/
		if ($this->folder == 4) {

			$mime = mime_content_type($this->file["tmp_name"]);
			if (strpos($mime, 'video') === false) {
				echo json_encode(["status" => 404, "error" => "Solo para Vimeo se permiten archivos de Video."]);
				return;
			}

			$keys = explode("|", $folder->keys_folder);
			$lib = new Vimeo($keys[0], $keys[1], $keys[2]);

			try {
				$uri = $lib->upload($this->file["tmp_name"], array(
					'name' => $fileName,
					'description' => $originalName
				));

				if ($uri && explode("/", $uri)[1] == "videos") {
					$idVimeo = explode("/", $uri)[2];

					// Guardar en BD con Thumbnail
					$url = "files?token=" . $this->token . "&table=admins&suffix=admin";
					$fields = array(
						"id_folder_file" => $this->folder,
						"extension_file" => $extension,
						"name_file" => $baseFileName,
						"type_file" => $this->file["type"],
						"size_file" => $this->file["size"],
						"link_file" => "https://player.vimeo.com/video/" . $idVimeo,
						"thumbnail_vimeo_file" => CurlController::getThumbnailVimeo($idVimeo),
						"date_created_file" => date("Y-m-d")
					);

					$uploadData = CurlController::request($url, "POST", $fields);
					$this->respondSuccess($uploadData, $fields["link_file"]);
				}
			} catch (VimeoUploadException $e) {
				echo json_encode(["status" => 404, "error" => "Error de Vimeo: " . $e->getMessage()]);
			}
			return;
		}

		/*=============================================
		Subiendo archivos a Mailchimp (Carpeta 5)
		=============================================*/
		if ($this->folder == 5) {

			$mime = mime_content_type($this->file["tmp_name"]);
			if (strpos($mime, 'image') === false) {
				echo json_encode(["status" => 404, "error" => "Solo para Mailchimp se permiten archivos de Imagen."]);
				return;
			}

			$keys = explode("|", $folder->keys_folder);
			$client = new MailchimpMarketing\ApiClient();
			$client->setConfig([
				'apiKey' => $keys[0],
				'server' => $keys[1],
			]);

			try {
				$imageBase64 = base64_encode(file_get_contents($this->file["tmp_name"]));
				$response = $client->fileManager->upload([
					"name" => $fileName,
					"file_data" => $imageBase64,
				]);

				if (!empty($response->id)) {
					$url = "files?token=" . $this->token . "&table=admins&suffix=admin";
					$fields = array(
						"id_folder_file" => $this->folder,
						"extension_file" => $extension,
						"name_file" => $baseFileName,
						"type_file" => $this->file["type"],
						"size_file" => $this->file["size"],
						"link_file" => $response->full_size_url,
						"id_mailchimp_file" => $response->id,
						"date_created_file" => date("Y-m-d")
					);

					$uploadData = CurlController::request($url, "POST", $fields);
					$this->respondSuccess($uploadData, $fields["link_file"]);
				}
			} catch (Exception $e) {
				echo json_encode(["status" => 404, "error" => "Error en Mailchimp: " . $e->getMessage()]);
			}
			return;
		}
	}

	/*=============================================
	Helper para Guardar en BD común
	=============================================*/
	private function saveFileToDB($folder, $extension, $name, $type, $size, $link)
	{
		$url = "files?token=" . $this->token . "&table=admins&suffix=admin";
		$method = "POST";
		$fields = array(
			"id_folder_file" => $folder,
			"extension_file" => $extension,
			"name_file" => $name,
			"type_file" => $type,
			"size_file" => $size,
			"link_file" => $link,
			"date_created_file" => date("Y-m-d")
		);

		$uploadData = CurlController::request($url, $method, $fields);
		$this->respondSuccess($uploadData, $link);
	}

	/*=============================================
	Helper para responder JSON de éxito
	=============================================*/
	private function respondSuccess($uploadData, $link)
	{
		if ($uploadData->status == 200) {
			echo json_encode([
				"status" => 200,
				"id_file" => $uploadData->results->lastId,
				"link" => $link,
				"reduce_link" => TemplateController::reduceText($link, 35) . "...",
				"date" => date("Y-m-d, H:i:s")
			]);
		} else {
			echo json_encode(["status" => 500, "error" => "Error al guardar registro en Base de Datos."]);
		}
	}

	/*=============================================
	Calcular el peso total de archivos de un folder
	=============================================*/
	public $idFolder;

	public function updateServer()
	{

		$url = "files?linkTo=id_folder_file&equalTo=" . $this->idFolder . "&select=size_file";
		$files = CurlController::request($url, "GET", array());

		if ($files->status == 200) {

			$totalSize = 0;
			// OPTIMIZACIÓN: Solo sumamos. Sacamos la petición a la BD fuera del loop.
			foreach ($files->results as $value) {
				$totalSize += $value->size_file;
			}

			/*=============================================
			Actualizar Folders (Se hace UNA sola vez)
			=============================================*/
			$url = "folders?id=" . $this->idFolder . "&nameId=id_folder&token=" . $this->token . "&table=admins&suffix=admin";
			$folders = CurlController::request($url, "PUT", "total_folder=" . $totalSize);

			if ($folders->status == 200) {
				echo $folders->status;
			}
		}
	}

	/*=============================================
	Eliminar archivo del servidor y de la BD
	=============================================*/
	public $idFileDelete;
	public $idFolderDelete;

	public function deleteFile()
	{

		$url = "files?linkTo=id_file&equalTo=" . $this->idFileDelete;
		$getFile = CurlController::request($url, "GET", array());
		if ($getFile->status == 200) {
			$getFile = $getFile->results[0];
		} else {
			return;
		}

		$url = "folders?linkTo=id_folder&equalTo=" . $this->idFolderDelete;
		$getFolder = CurlController::request($url, "GET", array());
		if ($getFolder->status == 200) {
			$getFolder = $getFolder->results[0];
		} else {
			return;
		}

		/*=============================================
		Eliminando archivo del servidor local
		=============================================*/
		if ($this->idFolderDelete == 1) {
			// SEGURIDAD: Evitar Directory Traversal
			$filePath = str_replace($_SERVER["HTTP_ORIGIN"] ?? '', "..", $getFile->link_file);
			$realPath = realpath($filePath);
			$baseDir = realpath("../views/assets/files/");

			if ($realPath && strpos($realPath, $baseDir) === 0 && file_exists($realPath)) {
				unlink($realPath);
			}
		}

		/*=============================================
		Otras plataformas (AWS, Cloudinary, Vimeo, Mailchimp)
		=============================================*/
		$keys = explode("|", $getFolder->keys_folder);
		$arrayLink = explode("/", $getFile->link_file);

		if ($this->idFolderDelete == 2) {
			$s3 = new S3Client([
				'version' => 'latest',
				'region'  => 'us-east-1',
				'credentials' => ['key' => $keys[0], 'secret' => $keys[1]]
			]);
			try {
				$s3->deleteObject(['Bucket' => $getFolder->url_folder, 'Key' => end($arrayLink)]);
			} catch (S3Exception $e) {
			}
		}

		if ($this->idFolderDelete == 3) {
			Configuration::instance('cloudinary://' . $keys[0] . ':' . $keys[1] . '@' . $getFolder->url_folder . '?secure=true');
			$public_ids = [pathinfo(end($arrayLink), PATHINFO_FILENAME)];
			(new AdminApi())->deleteAssets($public_ids, ["resource_type" => $arrayLink[4] ?? 'image', "type" => "upload"]);
		}

		if ($this->idFolderDelete == 4) {
			$lib = new Vimeo($keys[0], $keys[1], $keys[2]);
			$lib->request("/videos/" . end($arrayLink), array(), 'DELETE');
		}

		if ($this->idFolderDelete == 5) {
			$client = new MailchimpMarketing\ApiClient();
			$client->setConfig(['apiKey' => $keys[0], 'server' => $keys[1]]);
			if (isset($getFile->id_mailchimp_file)) {
				$client->fileManager->deleteFile($getFile->id_mailchimp_file);
			}
		}

		/*=============================================
		Actualizar capacidad total y BD
		=============================================*/
		$newSize = max(0, $getFolder->total_folder - $getFile->size_file);
		$url = "folders?id=" . $this->idFolderDelete . "&nameId=id_folder&token=" . $this->token . "&table=admins&suffix=admin";
		$updateFolder = CurlController::request($url, "PUT", "total_folder=" . $newSize);

		$url = "files?id=" . $this->idFileDelete . "&nameId=id_file&token=" . $this->token . "&table=admins&suffix=admin";
		$deleteFile = CurlController::request($url, "DELETE", array());

		if ($updateFolder->status == 200 && $deleteFile->status == 200) {
			echo $deleteFile->status;
		}
	}

	/*=============================================
	Actualizar el nombre del Archivo
	=============================================*/
	public $name;
	public $idFile;

	public function updateName()
	{
		$url = "files?id=" . $this->idFile . "&nameId=id_file&token=" . $this->token . "&table=admins&suffix=admin";
		// SEGURIDAD: Limpiar el nombre
		$safeName = htmlspecialchars(strip_tags($this->name), ENT_QUOTES, 'UTF-8');
		$update = CurlController::request($url, "PUT", "name_file=" . $safeName);

		if ($update->status == 200) {
			echo $update->status;
		}
	}

	/*=============================================
	Función para cargar archivos
	=============================================*/
	public $search;
	public $sortBy;
	public $filterBy;
	public $arrayFolders;
	public $startAt;
	public $endAt;

	public function loadFiles()
	{
		$htmlList = "";
		$htmlGrid = "";
		$load = array();

		$sortParts = explode("-", $this->sortBy);
		$orderBy = $sortParts[0] ?? 'id_file';
		$orderMode = $sortParts[1] ?? 'DESC';

		$arrFolders = json_decode($this->arrayFolders, true) ?: [];

		if (count($arrFolders) == 5) {
			if ($this->filterBy == "ALL") {
				$url = !empty($this->search)
					? "relations?rel=files,folders&type=file,folder&linkTo=name_file&search=" . urlencode($this->search) . "&orderBy=" . $orderBy . "&orderMode=" . $orderMode . "&startAt=" . $this->startAt . "&endAt=" . $this->endAt
					: "relations?rel=files,folders&type=file,folder&orderBy=" . $orderBy . "&orderMode=" . $orderMode . "&startAt=" . $this->startAt . "&endAt=" . $this->endAt;
			} else {
				$url = !empty($this->search)
					? "relations?rel=files,folders&type=file,folder&linkTo=name_file,type_file&search=" . urlencode($this->search) . "," . urlencode($this->filterBy) . "&orderBy=" . $orderBy . "&orderMode=" . $orderMode . "&startAt=" . $this->startAt . "&endAt=" . $this->endAt
					: "relations?rel=files,folders&type=file,folder&linkTo=type_file&equalTo=" . urlencode($this->filterBy) . "&orderBy=" . $orderBy . "&orderMode=" . $orderMode . "&startAt=" . $this->startAt . "&endAt=" . $this->endAt;
			}

			$loadFolders = CurlController::request($url, "GET", array());
			if ($loadFolders->status == 200) {
				$load = $loadFolders->results;
			}
		} else {
			foreach ($arrFolders as $value) {
				if ($this->filterBy == "ALL") {
					$url = !empty($this->search)
						? "relations?rel=files,folders&type=file,folder&linkTo=name_file,id_folder&search=" . urlencode($this->search) . "," . $value . "&orderBy=" . $orderBy . "&orderMode=" . $orderMode . "&startAt=" . $this->startAt . "&endAt=" . $this->endAt
						: "relations?rel=files,folders&type=file,folder&linkTo=id_folder&equalTo=" . $value . "&orderBy=" . $orderBy . "&orderMode=" . $orderMode . "&startAt=" . $this->startAt . "&endAt=" . $this->endAt;
				} else {
					$url = !empty($this->search)
						? "relations?rel=files,folders&type=file,folder&linkTo=name_file,type_file,id_folder&search=" . urlencode($this->search) . "," . urlencode($this->filterBy) . "," . $value . "&orderBy=" . $orderBy . "&orderMode=" . $orderMode . "&startAt=" . $this->startAt . "&endAt=" . $this->endAt
						: "relations?rel=files,folders&type=file,folder&linkTo=type_file,id_folder&equalTo=" . urlencode($this->filterBy) . "," . $value . "&orderBy=" . $orderBy . "&orderMode=" . $orderMode . "&startAt=" . $this->startAt . "&endAt=" . $this->endAt;
				}

				$loadFolders = CurlController::request($url, "GET", array());
				if ($loadFolders->status == 200) {
					$load = array_merge($load, $loadFolders->results);
				}
			}
		}

		if (!empty($load)) {
			foreach ($load as $value) {

				/*=============================================
				SEGURIDAD XSS: Escapar variables que van al HTML
				=============================================*/
				$s_name_file = htmlspecialchars($value->name_file ?? '', ENT_QUOTES, 'UTF-8');
				$s_ext_file = htmlspecialchars($value->extension_file ?? '', ENT_QUOTES, 'UTF-8');
				$s_name_folder = htmlspecialchars($value->name_folder ?? '', ENT_QUOTES, 'UTF-8');
				$s_link_file = htmlspecialchars($value->link_file ?? '', ENT_QUOTES, 'UTF-8');
				$s_date = htmlspecialchars($value->date_updated_file ?? '', ENT_QUOTES, 'UTF-8');
				$size_mb = number_format(($value->size_file ?? 0) / 1000000, 2);
				$short_link = TemplateController::reduceText($s_link_file, 35) . '...';

				$pathList = TemplateController::returnThumbnailList($value);
				$pathGrid = TemplateController::returnThumbnailGrid($value);

				/*=============================================
				HTML Lista
				=============================================*/
				$htmlList .= '<tr style="height:100px">
						<td>' . $pathList . '</td>
						<td class="align-middle">
							<div class="input-group">
								<input type="text" class="form-control changeName" value="' . $s_name_file . '" idFile="' . $value->id_file . '">
								<span class="input-group-text">.' . $s_ext_file . '</span>
							</div>
						</td>
						<td class="align-middle">' . $size_mb . ' MB</td>
						<td class="align-middle"><span class="badge bg-dark rounded px-3 py-2 text-white">' . $s_name_folder . '</span></td>
						<td class="align-middle">
							<a href="' . $s_link_file . '" target="_blank">' . $short_link . '<i class="bi bi-box-arrow-up-right ps-2 btn"></i></a>
						</td>
						<td class="align-middle">' . $s_date . '</td>
						<td class="align-middle">
						  <svg class="bi bi-copy copyLink" copy="' . $s_link_file . '" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="cursor:pointer">
							  <path fill-rule="evenodd" d="M4 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1zM2 5a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-1h1v1a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h1v1z"/>
							</svg>
						  <i class="bi bi-trash ps-2 btn deleteFile" idFile="' . $value->id_file . '" idFolder="' . $value->id_folder . '" mode="list"></i>
						</td>
					</tr>';

				/*=============================================
				HTML Cuadrícula
				=============================================*/
				$htmlGrid .= '<div class="col">
				 			<div class="card rounded p-3 border-0 shadow my-3">
				 				<div class="card-header bg-white border-0 p-0">
				 					<div class="d-flex justify-content-between mb-2">
				 						<div class="bg-white">
				 							<a href="' . $s_link_file . '" target="_blank">
											<i class="bi bi-box-arrow-up-right ps-2 btn p-0"></i>
											</a>
										</div>
										<div class="bg-white m-0">
											<svg class="bi bi-copy copyLink" copy="' . $s_link_file . '" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="cursor:pointer">
												<path fill-rule="evenodd" d="M4 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1zM2 5a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-1h1v1a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h1v1z"/>
											</svg>
											<i class="bi bi-trash p-0 ps-2 btn deleteFile" idFile="' . $value->id_file . '" idFolder="' . $value->id_folder . '" mode="grid"></i>
										</div>
				 					</div>
				 				</div>
				 				' . $pathGrid . '
				 				<div class="card-body p-1">
				 					<div class="card-text">
				 						<div class="input-group">
											<input type="text" class="form-control changeName" value="' . $s_name_file . '" idFile="' . $value->id_file . '">
											<span class="input-group-text">.' . $s_ext_file . '</span>
										</div>
										<div class="d-flex justify-content-between mt-3">
											<div class="bg-white">
												<p class="small mt-1">' . $size_mb . ' MB</p>
											</div>
											<div class="bg-white m-0">
												<span class="badge bg-dark border rounded px-3 py-2 text-white">' . $s_name_folder . '</span>
											</div>
										</div>
										<h6 class="float-end my-0 py-0">
											<small>' . $s_date . '</small>
										</h6>
				 					</div>
				 				</div>
				 			</div>
				 		</div>';
			}
		}

		echo json_encode(["htmlList" => $htmlList, "htmlGrid" => $htmlGrid]);
	}
}

/*=============================================
ENRUTADOR DE SOLICITUDES POST
=============================================*/

if (isset($_FILES["file"])) {
	$ajax = new FilesController();
	$ajax->file = $_FILES["file"];
	$ajax->folder = $_POST["folder"] ?? '';
	$ajax->token = $_POST["token"] ?? '';
	$ajax->ajaxUploadFiles();
}

if (isset($_POST["idFolder"]) && isset($_POST["token"])) {
	$ajax = new FilesController();
	$ajax->idFolder = $_POST["idFolder"];
	$ajax->token = $_POST["token"];
	$ajax->updateServer();
}

if (isset($_POST["idFolderDelete"]) && isset($_POST["idFileDelete"])) {
	$ajax = new FilesController();
	$ajax->idFileDelete = $_POST["idFileDelete"];
	$ajax->idFolderDelete = $_POST["idFolderDelete"];
	$ajax->token = $_POST["token"];
	$ajax->deleteFile();
}

if (isset($_POST["name"]) && isset($_POST["idFile"])) {
	$ajax = new FilesController();
	$ajax->name = $_POST["name"];
	$ajax->idFile = $_POST["idFile"];
	$ajax->token = $_POST["token"] ?? '';
	$ajax->updateName();
}

if (isset($_POST["search"]) || isset($_POST["sortBy"])) {
	$ajax = new FilesController();
	$ajax->search = $_POST["search"] ?? '';
	$ajax->sortBy = $_POST["sortBy"] ?? 'id_file-DESC';
	$ajax->filterBy = $_POST["filterBy"] ?? 'ALL';
	$ajax->arrayFolders = $_POST["arrayFolders"] ?? '[]';
	$ajax->startAt = $_POST["startAt"] ?? 0;
	$ajax->endAt = $_POST["endAt"] ?? 10;
	$ajax->loadFiles();
}
