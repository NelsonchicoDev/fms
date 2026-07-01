<?php

class TemplateController
{

	/*=============================================
	Traemos la Vista Principal de la plantilla
	=============================================*/
	public function index()
	{
		include "views/template.php";
	}

	/*=============================================
	Función Reducir texto (Soporte seguro UTF-8)
	=============================================*/
	static public function reduceText($value, $limit)
	{
		// mb_strlen y mb_substr manejan correctamente acentos y caracteres especiales
		if (mb_strlen($value, 'UTF-8') > $limit) {
			$value = mb_substr($value, 0, $limit, 'UTF-8') . "...";
		}
		return $value;
	}

	/*=============================================
	Devuelva la miniatura de la lista
	=============================================*/
	static public function returnThumbnailList($value)
	{
		$cssClasses = "rounded";
		$inlineStyles = "width:100px; height:100px; object-fit: cover; object-position: center;";

		return self::generateThumbnail($value, $cssClasses, $inlineStyles);
	}

	/*=============================================
	Devuelva la miniatura de la cuadrícula
	=============================================*/
	static public function returnThumbnailGrid($value)
	{
		$cssClasses = "rounded card-img-top w-100";
		$inlineStyles = ""; // La cuadrícula usa clases de Bootstrap en lugar de estilos en línea

		return self::generateThumbnail($value, $cssClasses, $inlineStyles);
	}

	/*=============================================
	Core: Generador de Miniaturas unificado
	=============================================*/
	static private function generateThumbnail($value, $classes, $styles)
	{

		// 1. Sanitizar variables críticas para evitar XSS en atributos HTML
		$link_file = htmlspecialchars($value->link_file ?? '', ENT_QUOTES, 'UTF-8');
		$type_file = htmlspecialchars($value->type_file ?? '', ENT_QUOTES, 'UTF-8');
		$thumbnail_vimeo = htmlspecialchars($value->thumbnail_vimeo_file ?? '', ENT_QUOTES, 'UTF-8');
		$id_folder = $value->id_folder_file ?? 0;

		// Preparar atributos de diseño
		$styleAttr = !empty($styles) ? ' style="' . $styles . '"' : '';
		$classAttr = 'class="' . $classes . '"';

		// 2. Extraer limpiamente el tipo y subtipo de archivo (sin repetir explode)
		$mimeParts = explode("/", $type_file);
		$primaryType = $mimeParts[0] ?? 'unknown';
		$subType = $mimeParts[1] ?? 'unknown';

		// 3. Lógica de renderizado optimizada usando un Switch o condicionales limpios
		if ($primaryType === "image") {

			return '<img src="' . $link_file . '" ' . $classAttr . $styleAttr . ' alt="Image">';
		} elseif ($primaryType === "video") {

			if ($id_folder == 4 && !empty($thumbnail_vimeo)) {
				// Es Vimeo
				return '<img src="' . $thumbnail_vimeo . '" ' . $classAttr . $styleAttr . ' alt="Vimeo Video">';
			} elseif ($subType === "mp4") {
				// Es MP4 local o Cloudinary
				return '<video ' . $classAttr . $styleAttr . ' muted>
							<source src="' . $link_file . '" type="' . $type_file . '">
						</video>';
			} else {
				// Otro formato de video
				return '<img src="/views/assets/img/multimedia.png" ' . $classAttr . $styleAttr . ' alt="Video">';
			}
		} elseif ($primaryType === "audio") {

			return '<img src="/views/assets/img/multimedia.png" ' . $classAttr . $styleAttr . ' alt="Audio">';
		} elseif ($subType === "pdf") {

			return '<img src="/views/assets/img/pdf.jpeg" ' . $classAttr . $styleAttr . ' alt="PDF">';
		} elseif ($subType === "zip" || $subType === "x-zip-compressed") {

			return '<img src="/views/assets/img/zip.jpg" ' . $classAttr . $styleAttr . ' alt="ZIP">';
		}

		// 4. FALLBACK: Si no coincide con ninguno (ej: docx, txt, excel), mostrar un icono por defecto
		return '<img src="/views/assets/img/multimedia.png" ' . $classAttr . $styleAttr . ' alt="File">';
	}
}
