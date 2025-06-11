<?php

if (!defined('TYPO3')) {
    die('Access denied.');
}

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

ExtensionManagementUtility::addTCAcolumns(
    'sys_file_metadata',
    [
        'bynder2_thumb_mini' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bynder2/Resources/Private/Language/locallang_db.xlf:bynder2_thumb_mini',
            'description' => 'LLL:EXT:bynder2/Resources/Private/Language/locallang_db.xlf:bynder2_thumb_mini.description',
            'config' => [
                'type' => 'link',
                'size' => 20,
                'readOnly' => true,
            ],
        ],
        'bynder2_thumb_thul' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bynder2/Resources/Private/Language/locallang_db.xlf:bynder2_thumb_thul',
            'description' => 'LLL:EXT:bynder2/Resources/Private/Language/locallang_db.xlf:bynder2_thumb_thul.description',
            'config' => [
                'type' => 'link',
                'size' => 20,
                'readOnly' => true,
            ],
        ],
        'bynder2_thumb_webimage' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:bynder2/Resources/Private/Language/locallang_db.xlf:bynder2_thumb_webimage',
            'description' => 'LLL:EXT:bynder2/Resources/Private/Language/locallang_db.xlf:bynder2_thumb_webimage.description',
            'config' => [
                'type' => 'link',
                'size' => 20,
                'readOnly' => true,
            ],
        ],
    ],
);

ExtensionManagementUtility::addToAllTCAtypes(
    'sys_file_metadata',
    '--div--;LLL:EXT:bynder2/Resources/Private/Language/locallang_db.xlf:bynder2_tab,bynder2_thumb_mini,bynder2_thumb_thul,bynder2_thumb_webimage',
);
