<?php
session_start();

// Tüm session verilerini temizle
session_unset();
session_destroy();

// index.html'e yönlendir
header("Location: index.html");
exit();
?>