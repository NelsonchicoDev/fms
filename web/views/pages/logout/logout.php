<?php 

session_destroy();

echo '<script>

localStorage.removeItem("token-admin");
window.location = "/";

</script>';

