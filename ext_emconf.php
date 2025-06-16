<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'FAL Bynder',
    'description' => 'Bynder FAL driver for TYPO3 CMS',
    'category' => 'plugin',
    'author' => 'Stefan Froemken',
    'author_email' => 'projects@jweiland.net',
    'author_company' => 'jweiland.net',
    'state' => 'stable',
    'version' => '4.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.12-13.4.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'JWeiland\\Bynder2\\' => 'Classes',
            'League\\OAuth2\\Client\\' => 'Resources/Private/PHP/thephpleague/oauth2-client/src',
            'Bynder\\' => 'Resources/Private/PHP/bynder/bynder-php-sdk/src/Bynder',
        ],
    ],
];
