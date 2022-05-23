<?php
if (!defined('TYPO3')) {
    die ('Access denied.');
}

call_user_func(static function (): void {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers']['fal_bynder'] = [
        'class' => \JWeiland\FalBynder\Driver\BynderDriver::class,
        'shortName' => 'Bynder',
        'label' => 'Bynder',
        'flexFormDS' => 'FILE:EXT:fal_bynder/Configuration/FlexForms/Bynder.xml',
    ];

    // Show dropbox status in file storage
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1653038580] = [
        'nodeName' => 'bynderStatus',
        'priority' => '70',
        'class' => \JWeiland\FalBynder\Form\Element\BynderStatusElement::class
    ];

    // create a temporary cache
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['fal_bynder']['backend']
        = \TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend::class;

    // Remove document view in extended view of FileList
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Filelist\FileList::class]['className']
        = \JWeiland\FalBynder\Xclass\FileList::class;

    $extractorRegistry = \TYPO3\CMS\Core\Resource\Index\ExtractorRegistry::getInstance();
    $extractorRegistry->registerExtractionService(\JWeiland\FalBynder\Resource\BynderExtractor::class);
});
