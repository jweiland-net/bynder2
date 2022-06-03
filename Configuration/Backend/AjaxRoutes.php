<?php

declare(strict_types = 1);

return [
    // Expand or toggle in legacy file tree
    'get_bynder2_authorization_url' => [
        'path' => '/ext/bynder2/authorization',
        'target' => \JWeiland\Bynder2\Controller\AjaxController::class . '::processAjaxRequest',
    ],
];
