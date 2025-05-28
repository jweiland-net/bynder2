<?php
if (!defined('TYPO3')) {
    die ('Access denied.');
}

call_user_func(static function (): void {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers']['bynder2'] = [
        'class' => \JWeiland\Bynder2\Driver\BynderDriver::class,
        'shortName' => 'Bynder',
        'label' => 'Bynder',
        'flexFormDS' => 'FILE:EXT:bynder2/Configuration/FlexForms/Bynder.xml',
    ];

    // Add wizard/control to access_token in XML structure
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1654175345] = [
        'nodeName' => 'bynder2AuthorizationUrl',
        'priority' => '70',
        'class' => \JWeiland\Bynder2\Form\Element\BynderAuthorizationUrlElement::class,
    ];

    // Show bynder authentication status in file storage
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1653038580] = [
        'nodeName' => 'bynder2Status',
        'priority' => '70',
        'class' => \JWeiland\Bynder2\Form\Element\BynderStatusElement::class,
    ];

    if (!isset($GLOBALS['TYPO3_CONF_VARS']['LOG']['JWeiland']['Bynder2']['writerConfiguration'])) {
        $GLOBALS['TYPO3_CONF_VARS']['LOG']['JWeiland']['Bynder2']['writerConfiguration'] = [
            \Psr\Log\LogLevel::INFO => [
                \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [
                    'logFileInfix' => 'bynder2',
                ],
            ],
        ];
    }

    // Create a cache to speed up page navigation through the files
    $extConf = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \JWeiland\Bynder2\Configuration\ExtConf::class
    );
    if ($extConf->getUseTransientCache()) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['bynder2_pagenav']['backend']
            = \TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend::class;
    } else {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['bynder2_pagenav']['backend']
            = \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class;
    }

    // Create a cache to store the file information retrieved from Bynder API
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['bynder2_fileinfo']['backend']
        = \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class;

    // Remove document view in extended view of FileList
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Filelist\FileList::class]['className']
        = \JWeiland\Bynder2\Xclass\FileList::class;

    $extractorRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\Index\ExtractorRegistry::class);
    $extractorRegistry->registerExtractionService(\JWeiland\Bynder2\Resource\BynderExtractor::class);
});
