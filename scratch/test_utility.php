<?php
require __DIR__ . '/../config/bootstrap.php';

Utility::safeQuery('SELECT 1 AS ok', [], 'SELECT', true);
echo "Utility alias works\n";
