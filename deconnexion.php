<?php
session_start();
session_destroy();
header("Location: /master-money/connexion.php");
exit;
?>