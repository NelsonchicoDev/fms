/*=============================================
Instancias Globales (Patrón Singleton)
Mejora el rendimiento evitando instanciar plugins repetidamente
=============================================*/

const FMS_Preloader = new $.materialPreloader({
  position: "top",
  height: "5px",
  col_1: "#159756",
  col_2: "#da4733",
  col_3: "#3b78e7",
  col_4: "#fdba2c",
  fadeIn: 200,
  fadeOut: 200,
});

const FMS_Toast = Swal.mixin({
  toast: true,
  position: "top-end",
  showConfirmButton: false,
  timer: 2000,
  timerProgressBar: true,
  didOpen: (toast) => {
    toast.addEventListener("mouseenter", Swal.stopTimer);
    toast.addEventListener("mouseleave", Swal.resumeTimer);
  },
});

/*=============================================
Formatear envío de formulario lado servidor
=============================================*/
function fncFormatInputs() {
  if (window.history.replaceState) {
    window.history.replaceState(null, null, window.location.href);
  }
}

/*=============================================
Alerta Línea Precarga
=============================================*/
function fncMatPreloader(type) {
  if (type === "on") {
    FMS_Preloader.on();
  } else if (type === "off") {
    // Se oculta con fadeOut en lugar de borrar el nodo del DOM con remove()
    $(".load-bar-container").fadeOut(200);
  }
}

/*=============================================
Alerta Toastr
=============================================*/
function fncToastr(type, text) {
  FMS_Toast.fire({
    icon: type,
    title: text,
  });
}

/*=============================================
Alerta SweetAlert
=============================================*/
function fncSweetAlert(type, text, url = "") {
  switch (type) {
    // Refactorización DRY: Agrupamos success y error
    case "success":
    case "error":
      let titleStr = type === "success" ? "Correcto" : "Error";

      Swal.fire({
        icon: type,
        title: titleStr,
        text: text,
      }).then((result) => {
        if (result.value && url !== "") {
          /*=============================================
                    SEGURIDAD: Prevenir Open Redirect
                    Solo permitimos redirecciones relativas (internas)
                    o al mismo origen exacto.
                    =============================================*/
          if (url.startsWith("/") || url.startsWith(window.location.origin)) {
            window.open(url, "_top");
          } else {
            console.error(
              "Redirección bloqueada por seguridad (Posible Open Redirect).",
            );
          }
        }
      });
      break;

    case "loading":
      Swal.fire({
        allowOutsideClick: false,
        icon: "info",
        text: text,
      });
      Swal.showLoading();
      break;

    case "confirm":
      return new Promise((resolve) => {
        Swal.fire({
          text: text,
          icon: "warning",
          showCancelButton: true,
          confirmButtonColor: "#3085d6",
          cancelButtonColor: "#d33",
          confirmButtonText: "¡Sí, continuar!",
          cancelButtonText: "No",
        }).then((result) => {
          resolve(result.value);
        });
      });
      break; // Siempre es buena práctica cerrar los cases con break

    case "close":
      Swal.close();
      break;
  }
}
