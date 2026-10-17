<?php
require_once __DIR__ . '/../app/lib/auth.php';
logout_user();
header('Location: index.php');
exit;
