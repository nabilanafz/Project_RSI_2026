<?php
// Salin menjadi database.local.php untuk pengaturan lokal (tidak dibagikan).
return [ 'host' => getenv('DB_HOST') ?: '127.0.0.1', 'port' => getenv('DB_PORT') ?: '3306', 'name' => getenv('DB_NAME') ?: 'rsi_filkom', 'user' => getenv('DB_USER') ?: 'root', 'password' => getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '', ];
