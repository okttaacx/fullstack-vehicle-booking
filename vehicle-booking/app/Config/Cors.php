public array $default = [
    'allowedOrigins' => [
        'http://localhost:4200',
    ],

    'allowedOriginsPatterns' => [],

    'supportsCredentials' => false,

    'allowedHeaders' => [
        'Content-Type',
        'Authorization',
    ],

    'exposedHeaders' => [],

    'allowedMethods' => [
        'GET',
        'POST',
        'PUT',
        'DELETE',
        'OPTIONS',
    ],

    'maxAge' => 7200,
];