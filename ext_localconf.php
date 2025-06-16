<?php

if (!defined('TYPO3')) {
    die('Access denied.');
}

use JWeiland\Bynder2\Driver\BynderDriver;
use JWeiland\Bynder2\Form\Element\BynderAuthorizationUrlElement;
use JWeiland\Bynder2\Form\Element\BynderStatusElement;
use JWeiland\Bynder2\Resource\BynderExtractor;
use JWeiland\Bynder2\Resource\Processing\BynderBackendProcessor;
use JWeiland\Bynder2\Resource\Processing\BynderFrontendProcessor;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend;
use TYPO3\CMS\Core\Log\Writer\FileWriter;
use TYPO3\CMS\Core\Resource\Index\ExtractorRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

$GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers']['bynder2'] = [
    'class' => BynderDriver::class,
    'shortName' => 'Bynder',
    'label' => 'Bynder',
    'flexFormDS' => 'FILE:EXT:bynder2/Configuration/FlexForms/Bynder.xml',
];

// Add wizard/control to access_token in XML structure
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1654175345] = [
    'nodeName' => 'bynder2AuthorizationUrl',
    'priority' => '70',
    'class' => BynderAuthorizationUrlElement::class,
];

// Show bynder authentication status in file storage
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1653038580] = [
    'nodeName' => 'bynder2Status',
    'priority' => '70',
    'class' => BynderStatusElement::class,
];

if (!isset($GLOBALS['TYPO3_CONF_VARS']['LOG']['JWeiland']['Bynder2']['writerConfiguration'])) {
    $GLOBALS['TYPO3_CONF_VARS']['LOG']['JWeiland']['Bynder2']['writerConfiguration'] = [
        LogLevel::INFO => [
            FileWriter::class => [
                'logFileInfix' => 'bynder2',
            ],
        ],
    ];
}

// Create a cache to speed up page navigation through the files
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['bynder2_file_response']['backend']
    = TransientMemoryBackend::class;

$extractorRegistry = GeneralUtility::makeInstance(ExtractorRegistry::class);
$extractorRegistry->registerExtractionService(BynderExtractor::class);

$GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['processors']['BynderBackendProcessor'] = [
    'className' => BynderBackendProcessor::class,
    'before' => [
        'DeferredBackendImageProcessor',
    ],
    'after' => [
        'SvgImageProcessor',
    ],
];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['processors']['BynderFrontendProcessor'] = [
    'className' => BynderFrontendProcessor::class,
    'before' => [
        'LocalImageProcessor',
    ],
    'after' => [
        'OnlineMediaPreviewProcessor',
    ],
];
