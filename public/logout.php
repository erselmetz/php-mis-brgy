<?php
session_start();
session_destroy();
header("Location: /navigator.php");
exit;
