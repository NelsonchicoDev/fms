<nav class="navbar navbar-expand-sm bg-dark navbar-dark shadow-sm" id="top" aria-label="Navegación principal">
  <div class="container">

    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link active fw-bold" href="/" aria-current="page">
          <i class="bi bi-folder2-open me-1"></i> FMS
        </a>
      </li>
    </ul>

    <div class="d-flex align-items-center">
      <?php
      /*=============================================
      Validamos de forma estricta que la sesión exista y tenga datos
      =============================================*/
      if (!empty($_SESSION["admin"])):
      ?>

        <span class="text-secondary me-3 d-none d-sm-block">
          <small><i class="bi bi-person-circle"></i> Admin</small>
        </span>

        <a href="/logout" class="btn btn-outline-light btn-sm ms-auto px-3" title="Cerrar Sesión">
          <i class="bi bi-box-arrow-right me-1"></i> Salir
        </a>

      <?php else: ?>

        <a href="#myLogin" class="btn btn-primary btn-sm ms-auto px-3 shadow-sm" data-bs-toggle="modal" title="Iniciar Sesión">
          <i class="bi bi-box-arrow-in-right me-1"></i> Entrar
        </a>

      <?php endif; ?>
    </div>

  </div>
</nav>