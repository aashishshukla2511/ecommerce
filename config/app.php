<?php

return [
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    'base_url' => rtrim($_ENV['APP_BASE_URL'] ?? 'http://localhost:8000', '/'),
];
