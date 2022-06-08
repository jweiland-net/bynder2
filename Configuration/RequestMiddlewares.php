<?php

declare(strict_types=1);

return [
    'backend' => [
        'ext-bynder2/show-bynder-authorization-code' => [
            'target' => \JWeiland\Bynder2\Middleware\ShowBynderAuthorizationCodeMiddleware::class,
        ],
    ],
];
