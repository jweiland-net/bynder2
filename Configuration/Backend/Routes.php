<?php

declare(strict_types=1);

return [
    // Show Bynder Code after authorization Bynder App (redirectCallback)
    'bynder_authorization_code' => [
        'path' => '/bynder2/authorization/code',
        'access' => 'public',
        'target' => \JWeiland\Bynder2\Controller\AuthorizationUrlController::class . '::processRequest',
    ],
];
