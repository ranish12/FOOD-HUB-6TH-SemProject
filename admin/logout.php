<?php
session_start();
session_destroy();
header('Location: ../customer/menu.php');
exit();
?> 