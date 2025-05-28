<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/bynder2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Bynder2\Driver;

use Bynder\Api\BynderClient;
use JWeiland\Bynder2\Service\BynderClientFactory;
use JWeiland\Bynder2\Service\BynderService;
use JWeiland\Bynder2\Utility\OrderingUtility;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\Driver\AbstractDriver;
use TYPO3\CMS\Core\Resource\Exception\InvalidFileNameException;
use TYPO3\CMS\Core\Resource\Exception\InvalidPathException;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ResourceStorageInterface;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/*
 * Class which contains all methods to arrange files and folders
 */
class BynderDriver extends AbstractDriver
{
    private const UNSAFE_FILENAME_CHARACTER_EXPRESSION = '\\x00-\\x2C\\/\\x3A-\\x3F\\x5B-\\x60\\x7B-\\xBF';

    protected BynderService $bynderService;

    protected BynderClient $bynderClient;

    protected FlashMessageService $flashMessageService;

    protected FrontendInterface $fileInfoCache;

    protected FrontendInterface $pageNavCache;

    private const DEFAULT_PROPERTIES_TO_EXTRACT = [
        'size',
        'extension',
        'atime',
        'mtime',
        'ctime',
        'mimetype',
        'name',
        'identifier',
        'identifier_hash',
        'storage',
        'folder_hash',
    ];

    public function processConfiguration(): void
    {
        // no need to configure something.
    }

    public function initialize(): void
    {
        $this->bynderService = GeneralUtility::makeInstance(BynderService::class);
        $this->flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);

        try {
            $this->fileInfoCache = GeneralUtility::makeInstance(CacheManager::class)
                ->getCache('bynder2_fileinfo');
            $this->pageNavCache = GeneralUtility::makeInstance(CacheManager::class)
                ->getCache('bynder2_pagenav');
        } catch (NoSuchCacheException $noSuchCacheException) {
            $this->addFlashMessage(
                'Cache for file information of bynder files could not be created. Please check cache configuration of DB tables',
                'Cache error',
                ContextualFeedbackSeverity::ERROR
            );
        }

        try {
            $this->bynderClient = $this->getBynderClientFactory()->createClient($this->configuration ?? []);
        } catch (\Exception $exception) {
            $this->addFlashMessage(
                $exception->getMessage(),
                'Bynder configuration error',
                ContextualFeedbackSeverity::ERROR
            );
        }
    }

    public function getCapabilities(): int
    {
        // If PUBLIC is available, each file will initiate a request to Bynder-Api to retrieve a public share link
        // this is extremely slow.

        return ResourceStorageInterface::CAPABILITY_BROWSABLE + ResourceStorageInterface::CAPABILITY_WRITABLE;
    }

    public function mergeConfigurationCapabilities($capabilities): int
    {
        $this->capabilities &= $capabilities;

        return $this->capabilities;
    }

    public function getRootLevelFolder(): string
    {
        return '/';
    }

    public function getDefaultFolder(): string
    {
        // Bynder does not work with folder structures. So just return root folder
        return '/';
    }

    public function getParentFolderIdentifierOfIdentifier($fileIdentifier): string
    {
        // Bynder has no parent folders. So just return the root folder
        return '/';
    }

    /**
     * This driver is marked as non-public, so this will never be called:
     */
    public function getPublicUrl($identifier): string
    {
        return '';
    }

    public function createFolder($newFolderName, $parentFolderIdentifier = '', $recursive = false): string
    {
        // Bynder has no folders and can not create any folders. Do nothing, but give a valid feedback to FAL
        return '';
    }

    public function renameFolder($folderIdentifier, $newName): array
    {
        // Bynder has no folders and cannot rename any folders. Do nothing, but give a valid feedback to FAL
        return [];
    }

    public function deleteFolder($folderIdentifier, $deleteRecursively = false): bool
    {
        // Bynder has no folders and cannot delete any folders. Do nothing but give TRUE as feedback to FAL
        return true;
    }

    public function fileExists($fileIdentifier): bool
    {
        // Early return for root folder "/"
        if ($fileIdentifier === '/') {
            return true;
        }

        return $this->fileInfoCache->has($this->getFileCacheIdentifier($fileIdentifier));
    }

    public function folderExists($folderIdentifier): bool
    {
        // Bynder does not work with folder structures. So just return true for root folder. Else false
        return $folderIdentifier === '/';
    }

    public function isFolderEmpty($folderIdentifier): bool
    {
        // Just count the files in the root folder
        return (bool)$this->countFilesInFolder('/');
    }

    public function addFile($localFilePath, $targetFolderIdentifier, $newFileName = '', $removeOriginal = true): string
    {
        // @ToDo: To be implemented
        return '';
    }

    public function createFile($fileName, $parentFolderIdentifier): string
    {
        // @ToDo: To be implemented
        return '';
    }

    public function copyFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $fileName): string
    {
        // Bynder works with just ONE folder. So, there is no need to copy file within the same folder.
        return $fileIdentifier;
    }

    public function renameFile($fileIdentifier, $newName): string
    {
        // @ToDo: To be implemented
        return '';
    }

    public function replaceFile($fileIdentifier, $localFilePath): bool
    {
        // @ToDo: To be implemented
        return false;
    }

    public function deleteFile($fileIdentifier): bool
    {
        // @ToDo: To be implemented
        return false;
    }

    public function hash($fileIdentifier, $hashAlgorithm): string
    {
        // All core calls were done with sha1. No need to check any other $hashAlgorithm
        return sha1($fileIdentifier);
    }

    public function moveFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $newFileName): string
    {
        // Bynder works with just ONE folder, so files can't be moved.
        return $fileIdentifier;
    }

    public function moveFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName): array
    {
        // Bynder does not work with folder structures. So just return a valid value for FAL
        return [];
    }

    public function copyFolderWithinStorage($sourceFolderIdentifier, $targetFolderIdentifier, $newFolderName): bool
    {
        // Bynder does not work with folder structures. So just return TRUE for FAL
        return true;
    }

    public function getFileContents($fileIdentifier): string
    {
        if ($cdnDownloadUrl = $this->bynderService->getCdnDownloadUrl($this->bynderClient, $fileIdentifier)) {
            return file_get_contents($cdnDownloadUrl);
        }

        return '';
    }

    public function setFileContents($fileIdentifier, $contents): int
    {
        // @ToDo: To be implemented
        return 0;
    }

    public function fileExistsInFolder($fileName, $folderIdentifier): bool
    {
        \TYPO3\CMS\Core\Utility\DebugUtility::debug($fileName, 'fileExistsInFolder was called with fileName: ' . $fileName);
        return $this->fileExists($folderIdentifier . $fileName);
    }

    public function folderExistsInFolder($folderName, $folderIdentifier): bool
    {
        // Bynder has only ONE folder. So there never can be a folder IN another folder
        return false;
    }

    public function getFileForLocalProcessing($fileIdentifier, $writable = true): string
    {
        return $this->copyFileToTemporaryPath($fileIdentifier);
    }

    public function getPermissions($identifier): array
    {
        // Currently, only READ permission is implemented
        return [
            'r' => true,
            'w' => false,
        ];
    }

    public function dumpFileContents($identifier): void
    {
        $handle = fopen('php://output', 'wb');
        fwrite($handle, $this->getFileContents($identifier));
        fclose($handle);
    }

    public function isWithin($folderIdentifier, $identifier): bool
    {
        // As Bynder just has only ONE folder, this is always true
        return $folderIdentifier === '/' || $folderIdentifier === '';
    }

    public function getFileInfoByIdentifier($fileIdentifier, array $propertiesToExtract = []): array
    {
        // Early return for "/"
        if ($fileIdentifier === '/') {
            return [
                'size' => 0,
                'atime' => time(),
                'mtime' => time(),
                'ctime' => time(),
                'name' => '/',
                'identifier' => '/',
                'mimetype' => '',
                'identifier_hash' => $this->hashIdentifier('/'),
                'storage' => (string)$this->storageUid,
                'folder_hash' => $this->hashIdentifier('/'),
            ];
        }

        $fileCacheIdentifier = $this->getFileCacheIdentifier($fileIdentifier);
        if ($this->fileInfoCache->has($fileCacheIdentifier)) {
            $fileInformation = $this->fileInfoCache->get($fileCacheIdentifier);
        } else {
            $fileInformation = [];
        }

        $properties = [];
        if ($fileInformation !== []) {
            if ($propertiesToExtract === []) {
                $propertiesToExtract = self::DEFAULT_PROPERTIES_TO_EXTRACT;
            }

            foreach ($propertiesToExtract as $property) {
                $properties[$property] = $this->getSpecificFileInformation($fileInformation, $property);
            }
        }

        return $properties;
    }

    public function getSpecificFileInformation($fileInfoResponse, $property): string
    {
        switch ($property) {
            case 'size':
                return (string)$fileInfoResponse['fileSize'];
            case 'mtime':
            case 'atime':
                $date = \DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $fileInfoResponse['dateModified']);
                return $date instanceof \DateTime ? $date->format('U') : '0';
            case 'ctime':
                $date = \DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $fileInfoResponse['dateCreated']);
                return $date instanceof \DateTime ? $date->format('U') : '0';
            case 'name':
                $fileName = $this->sanitizeFileName($fileInfoResponse['name']);
                // In most cases, Bynder does not contain the fileExt in name.
                // As FAL extracts fileExt from name, we have to append it manually
                $fileExt = strtolower($fileInfoResponse['extension'][0] ?? '');
                if (!str_ends_with(strtolower($fileName), '.' . $fileExt)) {
                    $fileName .= '.' . $fileExt;
                }
                return $this->sanitizeFileName($fileName);
            case 'extension':
                return strtolower($fileInfoResponse['extension'][0] ?? '');
            case 'mimetype':
                $fileExt = strtolower($fileInfoResponse['extension'][0] ?? '');
                if (in_array($fileExt, ['jpg', 'jpeg', 'bmp', 'svg', 'ico', 'pdf', 'png', 'tiff'])) {
                    return 'image/' . $fileExt;
                }
                return 'text/' . $fileExt;
            case 'identifier':
                return $fileInfoResponse['id'];
            case 'storage':
                return (string)$this->storageUid;
            case 'identifier_hash':
                // Do not use Bynder hash. Else TYPO3 will not find records from sys_file
                return $this->hashIdentifier($fileInfoResponse['id'] ?? '');
            case 'folder_hash':
                return $this->hashIdentifier('/');
            case 'title':
                return $fileInfoResponse['name'] ?? '';
            case 'description':
                return $fileInfoResponse['description'] ?? '';
            case 'width':
                return (string)($fileInfoResponse['width'] ?? '0');
            case 'height':
                return (string)($fileInfoResponse['height'] ?? '0');
            case 'copyright':
                return (string)($fileInfoResponse['copyright'] ?? '');
            case 'keywords':
                return implode(', ', $fileInfoResponse['tags'] ?? []);
            default:
                if (isset($fileInfoResponse[$property])) {
                    return $fileInfoResponse[$property];
                }
        }
        throw new \InvalidArgumentException(sprintf('The information "%s" is not available.', $property));
    }

    public function getFolderInfoByIdentifier($folderIdentifier): array
    {
        // Bynder has just ONE root folder
        return [
            'identifier' => '/',
            'name' => 'Bynder Root Folder',
            'storage' => $this->storageUid,
        ];
    }

    public function getFileInFolder($fileName, $folderIdentifier): string
    {
        return $this->canonicalizeAndCheckFileIdentifier('/' . $fileName);
    }

    public function getFilesInFolder(
        $folderIdentifier,
        $start = 0,
        $numberOfItems = 0,
        $recursive = false,
        array $filenameFilterCallbacks = [],
        $sort = '',
        $sortRev = false
    ): array {
        $orderBy = OrderingUtility::getOrdering($sort, $sortRev);
        $pageCacheIdentifier = $this->getPageNavCacheIdentifier($start, $numberOfItems, $orderBy);

        // Early return, if file identifiers were found in the cache
        if ($this->pageNavCache->has($pageCacheIdentifier)) {
            return $this->pageNavCache->get($pageCacheIdentifier);
        }

        // We use timestamp as a cache tag. That way we can remove old/deleted files (at bynder) in TYPO3 CMS, too.
        $timestamp = time();
        $lifetime = 86400;
        $fileIdentifiers = [];
        foreach ($this->bynderService->getFiles($this->bynderClient, $start, $numberOfItems, $orderBy) as $file) {
            $fileIdentifiers[] = $file['id'];
            $this->fileInfoCache->set($this->getFileCacheIdentifier($file['id']), $file, [$timestamp], $lifetime);
        }

        $this->pageNavCache->set($pageCacheIdentifier, $fileIdentifiers, [$timestamp], $lifetime);

        return $fileIdentifiers;
    }

    public function getFolderInFolder($folderName, $folderIdentifier): string
    {
        // As Bynder does not have subfolders, this method should never be called.
        return '';
    }

    public function getFoldersInFolder(
        $folderIdentifier,
        $start = 0,
        $numberOfItems = 0,
        $recursive = false,
        array $folderNameFilterCallbacks = [],
        $sort = '',
        $sortRev = false
    ): array {
        // Bynder does not work folder-based. So just return an empty array for 0 folders.
        return [];
    }

    public function countFilesInFolder($folderIdentifier, $recursive = false, array $filenameFilterCallbacks = []): int
    {
        static $amountOfFiles = null;

        // Bynder does not work folder-based. So just return the number of all files.
        if ($amountOfFiles === null) {
            $amountOfFiles = $this->bynderService->countFiles($this->bynderClient);
        }

        return $amountOfFiles;
    }

    public function countFoldersInFolder(
        $folderIdentifier,
        $recursive = false,
        array $folderNameFilterCallbacks = []
    ): int {
        // Bynder does not work folder-based. So just return 0.
        return 0;
    }

    /**
     * @throws InvalidPathException
     */
    protected function canonicalizeAndCheckFilePath($filePath): string
    {
        $filePath = PathUtility::getCanonicalPath($filePath);

        // filePath must be valid
        // a Special case is required by vfsStream in Unit Test context
        if (!GeneralUtility::validPathStr($filePath)) {
            throw new InvalidPathException('File ' . $filePath . ' is not valid (".." and "//" is not allowed in path).', 1320286857);
        }

        return $filePath;
    }

    protected function canonicalizeAndCheckFileIdentifier($fileIdentifier): string
    {
        if ($fileIdentifier !== '') {
            $fileIdentifier = $this->canonicalizeAndCheckFilePath($fileIdentifier);
            $fileIdentifier = '/' . ltrim($fileIdentifier, '/');
            if (!$this->isCaseSensitiveFileSystem()) {
                $fileIdentifier = strtolower($fileIdentifier);
            }
        }

        return $fileIdentifier;
    }

    protected function canonicalizeAndCheckFolderIdentifier($folderIdentifier): string
    {
        if ($folderIdentifier === '/') {
            return $folderIdentifier;
        }

        return rtrim($this->canonicalizeAndCheckFileIdentifier($folderIdentifier), '/') . '/';
    }

    /**
     * Checks if a resource exists - does not care for the type (file or folder).
     */
    public function resourceExists(string $identifier): bool
    {
        // As Bynder does not work with folders, we don't need to check for any sub-folder identifiers
        if ($identifier === '') {
            throw new \InvalidArgumentException('Resource path cannot be empty');
        }

        if ($identifier === '/') {
            return true;
        }

        return $this->fileExists($identifier);
    }

    /*
     * Copies a file to a temporary path and returns that path.
     */
    protected function copyFileToTemporaryPath(string $fileIdentifier): string
    {
        $temporaryPath = $this->getTemporaryPathForFile($fileIdentifier);
        file_put_contents(
            $temporaryPath,
            $this->getFileContents($fileIdentifier)
        );

        return $temporaryPath;
    }

    /**
     * We have to override TYPO3's version of this method, as Bynder identifiers do not have an appended
     * file extension.
     */
    protected function getTemporaryPathForFile($fileIdentifier): string
    {
        // Fallback to "jpg". FAL needs an extension, else img processing will not work
        $fileExtension = $this->getFileInfoByIdentifier($fileIdentifier, ['extension'])['extension'] ?? 'jpg';

        return GeneralUtility::tempnam(
            'bynder-tempfile-',
            '.' . $fileExtension
        );
    }

    /**
     * Bynder delivers 3 pre-configured thumbnails over its CDN.
     * Check, if we can use them, for faster rendering.
     *
     * Must be public as it was used by our EventListeners
     */
    public function getProcessingUrl(FileInterface $file, array $configuration, array $fileInformation = []): string
    {
        // Please try to assign $fileInformation to prevent multiple API calls
        if ($fileInformation === []) {
            if ($this->fileInfoCache->has($file->getIdentifier())) {
                $fileInformation = $this->fileInfoCache->get($file->getIdentifier());
            }

            // If still empty, an exception was thrown. File not found. Use fallback.
            if ($fileInformation === []) {
                return 'EXT:bynder/Resources/Public/Icons/ImageUnavailable.svg';
            }
        }

        // If cropping was not configured, we can return CDN URIs
        $processingUrl = '';
        if (!$this->hasCroppingConfiguration($configuration)) {
            if ($configuration['width'] <= 80) {
                $processingUrl = $fileInformation['thumbnails']['mini'] ?? '';
            } elseif ($configuration['width'] <= 250) {
                $processingUrl = $fileInformation['thumbnails']['thul'] ?? '';
            } elseif ($configuration['width'] <= 800) {
                $processingUrl = $fileInformation['thumbnails']['webimage'] ?? '';
            }
        }

        return $processingUrl;
    }

    protected function hasCroppingConfiguration(array $configuration): bool
    {
        return isset($configuration['crop'])
            && $configuration['crop'] instanceof Area;
    }

    protected function getPageNavCacheIdentifier(int $start, int $numberOfFiles, string $orderBy): string
    {
        return sprintf(
            'page-%d-%d-%s',
            $start,
            $numberOfFiles,
            str_replace(' ', '-', $orderBy)
        );
    }

    public function getFileCacheIdentifier(string $fileIdentifier): string
    {
        return $this->getSanitizedCacheIdentifier('file-' . $fileIdentifier);
    }

    protected function getSanitizedCacheIdentifier(string $cacheIdentifier): string
    {
        return sha1($this->storageUid . ':' . trim($cacheIdentifier, '/'));
    }

    /**
     * This is a copy of LocalDriver
     */
    public function sanitizeFileName($fileName, $charset = 'utf-8'): string
    {
        // Handle UTF-8 characters
        if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['UTF8filesystem']) {
            // Allow ".", "-", 0-9, a-z, A-Z and everything beyond U+C0 (latin capital letter a with grave)
            $cleanFileName = (string)preg_replace('/[' . self::UNSAFE_FILENAME_CHARACTER_EXPRESSION . ']/u', '_', trim($fileName));
        } else {
            $fileName = GeneralUtility::makeInstance(CharsetConverter::class)->specCharsToASCII($charset, $fileName);
            // Replace unwanted characters with underscores
            $cleanFileName = (string)preg_replace('/[' . self::UNSAFE_FILENAME_CHARACTER_EXPRESSION . '\\xC0-\\xFF]/', '_', trim($fileName));
        }

        // Strip trailing dots and return
        $cleanFileName = rtrim($cleanFileName, '.');
        if ($cleanFileName === '') {
            throw new InvalidFileNameException(
                'File name ' . $fileName . ' is invalid.',
                1320288991
            );
        }

        return $cleanFileName;
    }

    protected function addFlashMessage(
        string $message,
        string $title = '',
        ContextualFeedbackSeverity $severity = ContextualFeedbackSeverity::OK
    ): void {
        // We activate storeInSession so that messages can be displayed when click on the Save&Close button.
        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            $message,
            $title,
            $severity,
            true
        );

        $this->getFlashMessageQueue()->enqueue($flashMessage);
    }

    protected function getFlashMessageQueue(): FlashMessageQueue
    {
        return $this->flashMessageService->getMessageQueueByIdentifier();
    }

    protected function getBynderClientFactory(): BynderClientFactory
    {
        return GeneralUtility::makeInstance(BynderClientFactory::class);
    }
}
