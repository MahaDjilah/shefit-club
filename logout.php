<?php
session_start();
session_destroy();
setcookie('remember_token', '', -1, '/');
header("Location: home.php");
exit;
