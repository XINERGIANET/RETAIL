<?php

return [
    // URL base de la API de Apisunat.
    'url' => env('APISUNAT_URL', 'https://back.apisunat.com'),
    // Persona ID global de respaldo (si la sucursal no tiene uno propio).
    'id' => env('APISUNAT_ID'),
    'token' => [
        'prod' => env('APISUNAT_TOKEN_PROD'),
    ],
    'series' => [
        'boleta' => env('APISUNAT_SERIES_BOLETA', 'B001'),
        'factura' => env('APISUNAT_SERIES_FACTURA', 'F001'),
    ],
];
