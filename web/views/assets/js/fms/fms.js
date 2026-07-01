/*=============================================
CONFIGURACIÓN Y UTILIDADES SEGURAS (Namespace)
=============================================*/
const FMS_Utils = {
  // Prevención de DOM-based XSS
  escapeHTML: function (str) {
    if (!str) return "";
    return $("<div>").text(str).html();
  },
  // Parseo seguro de JSON para evitar que la app colapse
  safeJSON: function (data) {
    try {
      return typeof data === "object" ? data : JSON.parse(data);
    } catch (e) {
      console.error("Error al parsear respuesta del servidor:", data);
      return null;
    }
  },
  // Variable global segura para la cola de archivos
  queuedFiles: [],
  // Generador de ID único para evitar colisiones al borrar archivos
  generateUUID: () =>
    Date.now().toString(36) + Math.random().toString(36).substr(2),
};

/*=============================================
Cambiar de Listado a Cuadrícula
=============================================*/
$(document).on("click", ".changeView", function () {
  // Anti-patrón corregido: jQuery oculta/muestra colecciones nativamente
  $(".modules").hide();
  $("#" + $(this).attr("module")).show();

  $(".changeView")
    .removeClass("text-white bg-dark")
    .addClass("text-secondary bg-white");

  $(this).removeClass("text-secondary bg-white").addClass("text-white bg-dark");

  if ($(this).attr("module") === "grid") {
    imgAdjustGrid();
  }
});

/*=============================================
Zona Drag & Drop
=============================================*/
$("#dragFiles")
  .on("dragover dragenter", function (e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).addClass("bg-light");
  })
  .on("mouseleave", function (e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).removeClass("bg-light");
  })
  .on("drop", function (e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).removeClass("bg-light");

    if (
      e.originalEvent.dataTransfer &&
      e.originalEvent.dataTransfer.files.length
    ) {
      let t = new Date();
      let time =
        t.getFullYear() +
        "-" +
        ("0" + (t.getMonth() + 1)).slice(-2) +
        "-" +
        ("0" + t.getDate()).slice(-2) +
        ", " +
        t.toLocaleTimeString();
      uploadFiles(e.originalEvent.dataTransfer.files, "drag", time);
    }
  });

/*=============================================
Subir Archivos a la Cola
=============================================*/
function uploadFiles(event, type, time) {
  if (typeof fncMatPreloader === "function") fncMatPreloader("on");
  if (typeof fncSweetAlert === "function")
    fncSweetAlert("loading", "Cargando...", "");

  localStorage.setItem("listFolders", $(".listFolders").html());

  // Usar .prop() en lugar de .attr() para booleanos
  $(".check-fms").prop("type", "radio").prop("checked", false);
  $(".check-fms").first().prop("checked", true);

  let incomingFiles = type === "btn" ? event.target.files : event;

  $(".itemsUp").remove();

  Array.from(incomingFiles).forEach((file) => {
    let typeParts = file.type.split("/");

    if (
      ["image", "video", "audio", "application"].includes(typeParts[0]) ||
      ["pdf", "zip", "x-zip-compressed"].includes(typeParts[1])
    ) {
      // Sanitización y extracción de datos
      let rawName = file.name.split(".").slice(0, -1).join("_");
      let name = FMS_Utils.escapeHTML(rawName);
      let extension = FMS_Utils.escapeHTML(file.name.split(".").pop());
      let size = (Number(file.size) / 1000000).toFixed(2);

      // Generar ID único para gestionar el array de forma segura
      let fileId = FMS_Utils.generateUUID();
      FMS_Utils.queuedFiles.push({ id: fileId, file: file });

      let path;

      if (typeParts[0] === "image") {
        let reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = (e) => {
          paintFiles(
            e.target.result,
            name,
            extension,
            size,
            time,
            fileId,
            name,
          );
        };
      } else if (typeParts[0] === "video" && typeParts[1] === "mp4") {
        let canvas = document.createElement("canvas");
        let video = document.createElement("video");
        video.autoplay = true;
        video.muted = true;
        video.src = URL.createObjectURL(file);
        video.onloadeddata = () => {
          let ctx = canvas.getContext("2d");
          canvas.width = video.videoWidth;
          canvas.height = video.videoHeight;
          ctx.drawImage(video, 0, 0, video.videoWidth, video.videoHeight);
          video.pause();
          paintFiles(
            canvas.toDataURL("image/png"),
            name,
            extension,
            size,
            time,
            fileId,
            name,
          );
        };
      } else if (typeParts[1] === "pdf") {
        paintFiles(
          "/views/assets/img/pdf.jpeg",
          name,
          extension,
          size,
          time,
          fileId,
          name,
        );
      } else if (
        typeParts[1] === "zip" ||
        typeParts[1] === "x-zip-compressed"
      ) {
        paintFiles(
          "/views/assets/img/zip.jpg",
          name,
          extension,
          size,
          time,
          fileId,
          name,
        );
      } else {
        paintFiles(
          "/views/assets/img/multimedia.png",
          name,
          extension,
          size,
          time,
          fileId,
          name,
        );
      }
    } else {
      fncToastr("error", "Formato no permitido: " + file.name);
    }
  });
}

function paintFiles(path, name, extension, size, time, fileId, originalName) {
  // LISTA
  $("#list table tbody").prepend(`
        <tr style="height:100px" class="itemsUp item-${fileId}">
            <td><img src="${path}" class="rounded" style="width:100px; height:100px; object-fit: cover; object-position: center;"></td>
            <td class="align-middle columnName${fileId}">
                <div class="input-group">
                    <input type="text" class="form-control" value="${name}" readonly>
                    <span class="input-group-text">.${extension}</span>
                </div>
            </td>
            <td class="align-middle">${size} MB</td>
            <td class="align-middle"><span class="badge bg-dark rounded px-3 py-2 text-white typeFolder">Server</span></td>
            <td class="align-middle progressList${fileId}" style="width:350px">
                <div class="progress-spinner"></div>
                <div class="progress mt-1" style="height:10px">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width:0%">0%</div>
                </div>
            </td>
            <td class="align-middle">${time}</td>
            <td class="align-middle columnAction${fileId}">
                <button type="button" class="btn btn-sm py-2 px-3 bg-light border fw-bold rounded clearFile" mode="list" data-id="${fileId}">
                    <i class="bi bi-x-circle"></i> Quitar
                </button>
            </td>
        </tr>
    `);

  // CUADRÍCULA
  $("#grid").prepend(`
        <div class="col itemsUp item-${fileId}">
            <div class="card rounded p-3 border-0 shadow my-3">
                <div class="card-header bg-white border-0 p-0">
                    <div class="d-flex justify-content-between mb-2">
                        <div class="bg-white w-50 progressGrid${fileId}">
                            <div class="progress-spinner"></div>
                            <div class="progress mt-1" style="height:10px">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width:0%">0%</div>
                            </div>
                        </div>
                        <div class="bg-white m-0 gridAction${fileId}">
                            <button type="button" class="btn btn-sm py-2 px-3 bg-light border fw-bold rounded clearFile" mode="grid" data-id="${fileId}">
                                <i class="bi bi-x-circle"></i> Quitar
                            </button>
                        </div>
                    </div>
                </div>
                <img src="${path}" class="card-img-top rounded w-100">
                <div class="card-body p-1">
                    <div class="card-text">
                        <div class="input-group gridName${fileId}">
                            <input type="text" class="form-control" value="${name}" readonly>
                            <span class="input-group-text">.${extension}</span>
                        </div>
                        <div class="d-flex justify-content-between mt-3">
                            <div class="bg-white"><p class="small mt-1">${size} MB</p></div>
                            <div class="bg-white m-0"><span class="badge bg-dark border rounded px-3 py-2 text-white typeFolder">Server</span></div>
                        </div>
                        <h6 class="float-end my-0 py-0"><small>${time}</small></h6>
                    </div>
                </div>
            </div>
        </div>
    `);

  imgAdjustGrid();
  fncMatPreloader("off");
  fncSweetAlert("close", "", "");
}

/*=============================================
Ajuste de imagen para el grid
=============================================*/
function imgAdjustGrid() {
  $(".card-img-top").each(function () {
    $(this).css({
      height: $(this).width() + "px",
      "object-fit": "cover",
      "object-position": "center",
    });
  });
}

/*=============================================
Cambio al seleccionar servidor
=============================================*/
$(document).on("change", ".check-fms", function () {
  if ($(this).prop("type") === "radio") {
    let folder = $(this).val().split("_")[1];
    $(".typeFolder").html(folder); // Aplica a todos de una vez
  }
});

/*=============================================
Quitar archivos antes de subir al servidor (BUGFIX)
=============================================*/
$(document).on("click", ".clearFile", function () {
  let fileId = $(this).attr("data-id");

  // Eliminar del DOM en ambas vistas simultáneamente
  $(".item-" + fileId).remove();

  // Eliminar del array lógico filtrando por ID único
  FMS_Utils.queuedFiles = FMS_Utils.queuedFiles.filter(
    (item) => item.id !== fileId,
  );
});

/*=============================================
Iniciar subida de archivos AJAX
=============================================*/
$(document).on("click", "#startAll", function () {
  let token = localStorage.getItem("token-admin");
  if (!token) {
    fncToastr("error", "Debe iniciar sesión para realizar esta acción");
    return;
  }

  if (FMS_Utils.queuedFiles.length === 0) {
    fncToastr(
      "error",
      "Debe añadir mínimo un archivo para realizar esta acción",
    );
    return;
  }

  let folderId = $(".check-fms:radio:checked").val();
  if (folderId) {
    uploadFilesAjax(folderId);
  }
});

function uploadFilesAjax(folder) {
  fncMatPreloader("on");
  let countFiles = 0;
  let totalToUpload = FMS_Utils.queuedFiles.length;

  FMS_Utils.queuedFiles.forEach((queuedItem) => {
    let fileId = queuedItem.id;
    let data = new FormData();
    data.append("file", queuedItem.file);
    data.append("folder", folder.split("_")[0]);
    data.append("token", localStorage.getItem("token-admin"));

    $.ajax({
      xhr: function () {
        let xhr = $.ajaxSettings.xhr();
        xhr.upload.onprogress = function (e) {
          if (e.lengthComputable) {
            let percent = (e.loaded / e.total) * 100;
            let formatPercent = percent.toFixed(2) + "%";

            let spinnerHtml = `<div class="spinner-border spinner-border-sm me-1"></div><small>Subiendo...</small>`;

            $(".progressList" + fileId)
              .find(".progress-spinner")
              .html(spinnerHtml);
            $(".progressList" + fileId)
              .find(".progress-bar")
              .css("width", formatPercent)
              .html(formatPercent);

            $(".progressGrid" + fileId)
              .find(".progress-spinner")
              .html(`<div class="spinner-border spinner-border-sm"></div>`);
            $(".progressGrid" + fileId)
              .find(".progress-bar")
              .css("width", formatPercent)
              .html(formatPercent);
          }
        };
        return xhr;
      },
      url: "/ajax/files.ajax.php",
      method: "POST",
      data: data,
      contentType: false,
      cache: false,
      processData: false,
      success: function (response) {
        let res = FMS_Utils.safeJSON(response);

        if (res && res.status == 200) {
          countFiles++;
          let safeLink = FMS_Utils.escapeHTML(res.link);
          let safeLinkShort = FMS_Utils.escapeHTML(res.reduce_link);
          let dbIdFile = parseInt(res.id_file);

          // Transformar vistas al estado 'subido'
          $(".columnName" + fileId)
            .removeClass("itemsUp columnName" + fileId)
            .find("input")
            .prop("readonly", false)
            .addClass("changeName")
            .attr("idFile", dbIdFile);
          $(".progressList" + fileId)
            .removeClass("progressList" + fileId)
            .html(
              `<a href="${safeLink}" target="_blank">${safeLinkShort} <i class="bi bi-box-arrow-up-right ps-2 btn"></i></a>`,
            );
          $(".columnAction" + fileId).removeClass("columnAction" + fileId)
            .html(`
                        <i class="bi bi-copy copyLink me-2" style="cursor:pointer" copy="${safeLink}"></i>
                        <i class="bi bi-trash btn p-0 deleteFile" idFile="${dbIdFile}" idFolder="${folder.split("_")[0]}" mode="list"></i>
                    `);

          $(".gridName" + fileId)
            .closest(".itemsUp")
            .removeClass("itemsUp");
          $(".gridName" + fileId)
            .removeClass("gridName" + fileId)
            .find("input")
            .prop("readonly", false)
            .addClass("changeName")
            .attr("idFile", dbIdFile);
          $(".progressGrid" + fileId)
            .removeClass("progressGrid" + fileId)
            .html(
              `<a href="${safeLink}" target="_blank"><i class="bi bi-box-arrow-up-right ps-2 btn"></i></a>`,
            );
          $(".gridAction" + fileId).removeClass("gridAction" + fileId).html(`
                        <i class="bi bi-copy copyLink me-2" style="cursor:pointer" copy="${safeLink}"></i>
                        <i class="bi bi-trash btn p-0 deleteFile" idFile="${dbIdFile}" idFolder="${folder.split("_")[0]}" mode="grid"></i>
                    `);

          if (countFiles === totalToUpload) {
            $.post(
              "/ajax/files.ajax.php",
              {
                idFolder: folder.split("_")[0],
                token: localStorage.getItem("token-admin"),
              },
              function (resp) {
                if (resp == 200) {
                  $(".listFolders").html(localStorage.getItem("listFolders"));
                  fncMatPreloader("off");
                  fncToastr("success", "Archivos subidos exitosamente");
                  FMS_Utils.queuedFiles = []; // Limpiar cola
                }
              },
            );
          }
        } else {
          fncMatPreloader("off");
          fncToastr("error", res ? res.error : "Error de servidor");
          $(".progressList" + fileId + ", .progressGrid" + fileId)
            .find(".progress-bar")
            .css("width", "0%")
            .html("0%");
          $(".progressList" + fileId + ", .progressGrid" + fileId)
            .find(".progress-spinner")
            .html("");
        }
      },
      error: function () {
        fncToastr("error", "Fallo de conexión al subir el archivo.");
      },
    });
  });
}

/*=============================================
Eliminar Archivo (BD)
=============================================*/
$(document).on("click", ".deleteFile", function () {
  if (!localStorage.getItem("token-admin"))
    return fncToastr("error", "Sesión inválida");

  fncSweetAlert("confirm", "¿Está seguro de eliminar este archivo?", "").then(
    (resp) => {
      if (resp) {
        fncMatPreloader("on");
        let idFile = $(this).attr("idFile");
        let idFolder = $(this).attr("idFolder");

        // Remover del DOM inmediatamente (Ambas vistas simultáneas)
        $(".deleteFile[idFile='" + idFile + "']")
          .closest("tr, .col")
          .remove();

        let data = new FormData();
        data.append("idFileDelete", idFile);
        data.append("idFolderDelete", idFolder);
        data.append("token", localStorage.getItem("token-admin"));

        $.ajax({
          url: "/ajax/files.ajax.php",
          method: "POST",
          data: data,
          contentType: false,
          processData: false,
          success: (response) => {
            if (parseInt(response) === 200) {
              fncMatPreloader("off");
              fncToastr("success", "Archivo eliminado");
            }
          },
        });
      }
    },
  );
});

/*=============================================
Copiar al Portapapeles (API Moderna)
=============================================*/
$(document).on("click", ".copyLink", function () {
  let link = $(this).attr("copy");

  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard
      .writeText(link)
      .then(() => {
        fncToastr("success", "Link copiado");
      })
      .catch((err) => {
        fncToastr("error", "Error al copiar");
      });
  } else {
    // Fallback para navegadores antiguos o sin HTTPS
    let textArea = document.createElement("textarea");
    textArea.value = link;
    textArea.style.position = "fixed";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    try {
      document.execCommand("copy");
      fncToastr("success", "Link copiado (Fallback)");
    } catch (err) {
      fncToastr("error", "No soportado en este navegador");
    }
    document.body.removeChild(textArea);
  }
});

/*=============================================
Cambiar el nombre del archivo
=============================================*/
$(document).on("change", ".changeName", function () {
  if (!localStorage.getItem("token-admin"))
    return fncToastr("error", "Sesión inválida");

  let data = new FormData();
  data.append("name", $(this).val());
  data.append("idFile", $(this).attr("idFile"));
  data.append("token", localStorage.getItem("token-admin"));

  $.ajax({
    url: "/ajax/files.ajax.php",
    method: "POST",
    data: data,
    contentType: false,
    processData: false,
    success: (response) => {
      if (parseInt(response) === 200)
        fncToastr("success", "Nombre actualizado");
    },
  });
});

/*=============================================
Buscador de archivos (OPTIMIZADO CON DEBOUNCE)
=============================================*/
let searchTimeout;
$("#searchFiles").on("input", function (e) {
  e.preventDefault();
  clearTimeout(searchTimeout);

  // Esperar 500ms después de que el usuario deja de escribir para hacer la petición
  searchTimeout = setTimeout(() => {
    let search = fncSearch($(this).val().toLowerCase());
    let sortBy = $("#sortBy").val();
    let filterBy = $("#filterBy").val();
    let folders = $(".check-fms:checkbox");
    loadFiles(search, sortBy, filterBy, folders, 0, 15);
  }, 500);
});

function fncSearch(search) {
  return search
    .replace(/[#\\;\\$\\&\\%\\=\\(\\)\\:\\,\\'\\"\\.\\¿\\¡\\!\\?\\]/g, "")
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "") // Remueve acentos correctamente
    .replace(/[ ]/g, "_");
}

$(document).on("change", ".changeFilters, .changeFolders", function () {
  let search = fncSearch($("#searchFiles").val().toLowerCase());
  let sortBy = $("#sortBy").val();
  let filterBy = $("#filterBy").val();
  let folders = $(".check-fms:checkbox");
  loadFiles(search, sortBy, filterBy, folders, 0, 15);
});

/*=============================================
Scroll Infinito (Paginación Optimizada)
=============================================*/
let isFetching = false;

$(window).on("scroll", function () {
  if (isFetching) return; // Si ya está cargando, bloquear múltiples peticiones

  let scrollPosition = $(window).height() + $(window).scrollTop();
  let scrollHeight = $(document).height();

  // Margen de 50px antes del final para cargar de forma más fluida
  if (scrollHeight - scrollPosition <= 50) {
    let currentPage = Number($("#currentPage").val());
    let totalPages = Number($("#totalPages").val());

    if (currentPage < totalPages) {
      isFetching = true;
      $("#scrollControl").html(
        `<div class="text-center"><div class="spinner-border mb-4 text-primary"></div></div>`,
      );

      let nextPage = currentPage + 1;
      $("#currentPage").val(nextPage);

      let search = fncSearch($("#searchFiles").val().toLowerCase());
      let sortBy = $("#sortBy").val();
      let filterBy = $("#filterBy").val();
      let folders = $(".check-fms:checkbox");
      let startAt = nextPage * 15 - 15;

      loadFiles(search, sortBy, filterBy, folders, startAt, 15);
    } else {
      $("#scrollControl").html("");
    }
  }
});

/*=============================================
Función para cargar DOM desde AJAX
=============================================*/
function loadFiles(search, sortBy, filterBy, folders, startAt, endAt) {
  let arrayFolders = [];

  if (folders.length === 0) folders = $(".check-fms:radio");

  folders.each(function () {
    if ($(this).prop("checked")) arrayFolders.push($(this).val().split("_")[0]);
  });

  let data = new FormData();
  data.append("search", search);
  data.append("sortBy", sortBy);
  data.append("filterBy", filterBy);
  data.append("arrayFolders", JSON.stringify(arrayFolders));
  data.append("startAt", startAt);
  data.append("endAt", endAt);

  $.ajax({
    url: "/ajax/files.ajax.php",
    method: "POST",
    data: data,
    contentType: false,
    processData: false,
    success: function (response) {
      let res = FMS_Utils.safeJSON(response);
      if (!res) return;

      if (startAt === 0) {
        $("#list table tbody").empty();
        $("#grid").empty();
      }

      $("#list table tbody").append(res.htmlList);
      $("#grid").append(res.htmlGrid);

      imgAdjustGrid();
      isFetching = false; // Liberar el candado del scroll
      $("#scrollControl").html("");
    },
  });
}
