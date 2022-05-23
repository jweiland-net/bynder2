<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/fal-bynder.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\FalBynder\Driver;

use Bynder\Api\BynderApiFactory;
use Bynder\Api\Impl\BynderApi;
use In2code\Powermail\Utility\StringUtility;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\Driver\AbstractDriver;
use TYPO3\CMS\Core\Resource\Exception\InvalidFileNameException;
use TYPO3\CMS\Core\Resource\Exception\InvalidPathException;
use TYPO3\CMS\Core\Resource\ResourceStorageInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Class which contains all methods to arrange files and folders
 */
class BynderDriver extends AbstractDriver
{
    /**
     * @var string
     */
    const UNSAFE_FILENAME_CHARACTER_EXPRESSION = '\\x00-\\x2C\\/\\x3A-\\x3F\\x5B-\\x60\\x7B-\\xBF';

    /**
     * @var FrontendInterface
     */
    protected $cache;

    /**
     * @var BynderApi|null
     */
    protected $bynderClient = null;

    /**
     * @var FlashMessageService
     */
    protected $flashMessageService;

    /**
     * @var array
     */
    protected $settings = [];

    /**
     * A list of all supported hash algorithms, written all lower case.
     *
     * @var array
     */
    protected $supportedHashAlgorithms = ['sha1', 'md5'];

    public function processConfiguration(): void
    {
        // no need to configure something.
    }

    public function initialize(): void
    {
        $this->cache = GeneralUtility::makeInstance(CacheManager::class)
            ->getCache('fal_bynder');

        // define('BYNDER_INTEGRATION_ID', '8517905e-6c2f-47c3-96ca-0312027bbc95');

        if (
            isset(
                $this->configuration['consumer_key'],
                $this->configuration['consumer_secret'],
                $this->configuration['token_key'],
                $this->configuration['token_secret']
            )
        ) {
            $this->bynderClient = BynderApiFactory::create(
                [
                    'consumerKey' => $this->configuration['consumer_key'],
                    'consumerSecret' => $this->configuration['consumer_secret'],
                    'token' => $this->configuration['token_key'],
                    'tokenSecret' => $this->configuration['token_secret'],
                    'baseUrl' => $this->configuration['url']
                ]
            );
        }

        $this->flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
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
        // Bynder has no folders and can not rename any folders. Do nothing, but give a valid feedback to FAL
        return [];
    }

    public function deleteFolder($folderIdentifier, $deleteRecursively = false): bool
    {
        // Bynder has no folders and can not delete any folders. Do nothing, but give a TRUE feedback to FAL
        return true;
    }

    public function fileExists($fileIdentifier): bool
    {
        // Early return for root folder "/"
        if ($fileIdentifier === '/') {
            return true;
        }

        // Early, if file is in cache
        $fileCacheIdentifier = $this->getFileCacheIdentifier($fileIdentifier);
        if ($this->cache->has($fileCacheIdentifier)) {
            return true;
        }

        $mediaAvailableResponse = $this->bynderClient->getAssetBankManager()->getMediaInfo(
            $fileIdentifier
        )->wait();

        return ($mediaAvailableResponse['statuscode'] ?? '200') === '200';
    }

    public function folderExists($folderIdentifier): bool
    {
        // Bynder does not work with folder structures. So just return true for root folder. Else false
        return $folderIdentifier === '/';
    }

    public function isFolderEmpty($folderIdentifier): bool
    {
        // Just count the files in root folder
        return (bool)$this->countFilesInFolder('/');
    }

    public function addFile($localFilePath, $targetFolderIdentifier, $newFileName = '', $removeOriginal = true): string
    {
        $localFilePath = $this->canonicalizeAndCheckFilePath($localFilePath);
        $newFileIdentifier = $this->canonicalizeAndCheckFolderIdentifier($targetFolderIdentifier) . $newFileName;

        $this->bynderClient->upload(
            $newFileIdentifier,
            file_get_contents($localFilePath),
            'overwrite'
        );

        if ($removeOriginal) {
            unlink($localFilePath);
        }

        $this->cache->flush();

        return $newFileIdentifier;
    }

    public function createFile($fileName, $parentFolderIdentifier): string
    {
        $parentFolderIdentifier = $this->canonicalizeAndCheckFolderIdentifier($parentFolderIdentifier);
        $fileIdentifier =  $this->canonicalizeAndCheckFileIdentifier(
            $parentFolderIdentifier . $this->sanitizeFileName(ltrim($fileName, '/'))
        );

        $this->bynderClient->upload(
            $fileIdentifier,
            ''
        );

        $this->cache->flush();

        return $fileIdentifier;
    }

    public function copyFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $fileName): string
    {
        $fileIdentifier = $this->canonicalizeAndCheckFileIdentifier($fileIdentifier);
        $targetFileIdentifier = $this->canonicalizeAndCheckFileIdentifier($targetFolderIdentifier . '/' . $fileName);

        // Bynder don't like slashes at the end of identifier
        $this->bynderClient->copy($fileIdentifier, $targetFileIdentifier);
        $this->cache->flush();

        return $targetFileIdentifier;
    }

    public function renameFile($fileIdentifier, $newName): string
    {
        $fileIdentifier = $this->canonicalizeAndCheckFileIdentifier($fileIdentifier);
        $newName = $this->sanitizeFileName($newName);

        $targetIdentifier = PathUtility::dirname($fileIdentifier) . '/' . $newName;
        $targetIdentifier = $this->canonicalizeAndCheckFileIdentifier($targetIdentifier);

        $this->bynderClient->move($fileIdentifier, $targetIdentifier);

        $this->cache->flush();

        return $targetIdentifier;
    }

    public function replaceFile($fileIdentifier, $localFilePath): bool
    {
        try {
            if (is_uploaded_file($localFilePath)) {
                $this->setFileContents(
                    $fileIdentifier,
                    file_get_contents($localFilePath)
                );
            } else {
                $parts = GeneralUtility::split_fileref($localFilePath);
                $this->renameFile($fileIdentifier, $parts['info']);
            }
            $this->cache->flush();

            return true;
        } catch (\Exception $e) {
        }

        return false;
    }

    public function deleteFile($fileIdentifier): bool
    {
        try {
            $this->bynderClient->delete($fileIdentifier);
            return true;
        } catch (\Exception $e) {
        }

        return false;
    }

    public function hash($fileIdentifier, $hashAlgorithm): string
    {
        if (!in_array($hashAlgorithm, $this->supportedHashAlgorithms, true)) {
            throw new \InvalidArgumentException('Hash algorithm "' . $hashAlgorithm . '" is not supported.', 1304964032);
        }

        switch ($hashAlgorithm) {
            case 'sha1':
                $hash = sha1($fileIdentifier);
                break;
            case 'md5':
                $hash = md5($fileIdentifier);
                break;
            default:
                throw new \RuntimeException('Hash algorithm ' . $hashAlgorithm . ' is not implemented.', 1329644451);
        }

        return $hash;
    }

    public function moveFileWithinStorage($fileIdentifier, $targetFolderIdentifier, $newFileName): string
    {
        $fileIdentifier = $this->canonicalizeAndCheckFileIdentifier($fileIdentifier);
        $targetFileIdentifier = $this->canonicalizeAndCheckFileIdentifier($targetFolderIdentifier . '/' . $newFileName);

        // Bynder don't like slashes at the end of identifier
        $this->bynderClient->move($fileIdentifier, $targetFileIdentifier);

        $this->cache->flush();

        return $targetFileIdentifier;
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
        return stream_get_contents($this->bynderClient->download($fileIdentifier));
    }

    public function setFileContents($fileIdentifier, $contents): int
    {
        $response = $this->bynderClient->upload(
            $fileIdentifier,
            $contents,
            'overwrite'
        );

        $this->cache->flush();

        return (int)$response['size'];
    }

    public function fileExistsInFolder($fileName, $folderIdentifier): bool
    {
        $fileIdentifier = $folderIdentifier . $fileName;

        return $this->fileExists($fileIdentifier);
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
            'r' => true
        ];
    }

    public function dumpFileContents($identifier): void
    {
        $handle = fopen('php://output', 'wb');
        fwrite($handle, stream_get_contents($this->bynderClient->download($identifier)));
        fclose($handle);
    }

    public function isWithin($folderIdentifier, $identifier): bool
    {
        // As Bynder just has only ONE folder, this is always true
        return true;
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
                'identifier_hash' => $this->hashIdentifier('/'),
                'storage' => (string)$this->storageUid,
                'folder_hash' => $this->hashIdentifier('/')
            ];
        }

        $fileInfoResponse = $this->getFileInfoResponse($fileIdentifier);
        if (empty($propertiesToExtract)) {
            $propertiesToExtract = [
                'size', 'extension', 'atime', 'mtime', 'ctime', 'mimetype', 'name',
                'identifier', 'identifier_hash', 'storage', 'folder_hash'
            ];
        }

        $fileInformation = [];
        foreach ($propertiesToExtract as $property) {
            $fileInformation[$property] = $this->getSpecificFileInformation($fileInfoResponse, $property);
        }

        return $fileInformation;
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
                if (!StringUtility::endsWith(strtolower($fileName), '.' . $fileExt)) {
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
            'storage' => $this->storageUid
        ];
    }

    public function getFileInFolder($fileName, $folderIdentifier): string
    {
        return $this->canonicalizeAndCheckFileIdentifier($folderIdentifier . '/' . $fileName);
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
        $numberOfItems = $numberOfItems ?: 40;
        if ($start < $numberOfItems) {
            $start = 1;
        } else {
            $start = (int)ceil($start / $numberOfItems) + 1;
        }

        $sort = $sort ?: 'dateModified';
        $orderBy = [
            'file' => 'name',
            'tstamp' => 'dateModified',
            'size' => 'dateModified',
            'fileext' => 'dateModified',
            'rw' => 'dateModified',
        ];

        $ordering = $sortRev ? 'desc' : 'asc';

        $pageCacheIdentifier = sprintf(
            'page-%d-%d-%s-%s',
            $start,
            $numberOfItems,
            $orderBy[$sort],
            $ordering
        );
        if ($this->cache->has($pageCacheIdentifier)) {
            $files = $this->cache->get($pageCacheIdentifier);
        } else {
            $mediaResponse = $this->bynderClient->getAssetBankManager()->getMediaList([
                'page' => $start,
                'limit' => $numberOfItems,
                'orderBy' => $orderBy[$sort] . ' ' . $ordering,
                'includeMediaItems' => 1,
                'isPublic' => 0,
                'archive' => 0
            ])->wait();

            $files = [];
            foreach ($mediaResponse as $mediaFile) {
                $files[] = $mediaFile['id'];
                $this->cache->set($this->getFileCacheIdentifier($mediaFile['id']), $mediaFile);
            }

            $this->cache->set($pageCacheIdentifier, $mediaResponse);
        }

        return $files;
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
        // Bynder does not work folder based. So just return empty array for 0 folders.
        return [];
    }

    public function countFilesInFolder($folderIdentifier, $recursive = false, array $filenameFilterCallbacks = []): int
    {
        $mediaUsage = $this->bynderClient->getAssetBankManager()->getMediaList([
            'count' => 0,
            'limit' => 0,
            'total' => 1,
            'includeMediaItems' => 0,
            'isPublic' => 0,
            'archive' => 0
        ])->wait();

        return $mediaUsage['total']['count'] ?? 0;
    }

    public function countFoldersInFolder($folderIdentifier, $recursive = false, array $folderNameFilterCallbacks = []): int
    {
        // Bynder does not work folder based. So just return 0.
        return 0;
    }

    /**
     * @throws InvalidPathException
     */
    protected function canonicalizeAndCheckFilePath($filePath): string
    {
        $filePath = PathUtility::getCanonicalPath($filePath);

        // filePath must be valid
        // Special case is required by vfsStream in Unit Test context
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
        // As Bynder does not work with folders, we don't need to check for any subfolder identifiers
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
        $remoteFileResponse = $this->getMediaDownloadResponse($fileIdentifier);
        if ($remoteFileResponse !== []) {
            file_put_contents(
                $temporaryPath,
                file_get_contents($remoteFileResponse['s3_file'])
            );
        } else {
            $this->addFlashMessage(
                'The file meta extraction has been interrupted, because file has been removed in the meanwhile.',
                'File Meta Extraction aborted',
                AbstractMessage::INFO
            );
            return '';
        }

        return $temporaryPath;
    }

    /**
     * Bynder delivers 3 pre-configured thumbnails over its CDN.
     * Check, if we can use them, for faster rendering.
     *
     * Must be public as it was used by our EventListeners
     */
    public function getProcessingUrl(string $fileIdentifier, array $configuration): string
    {
        if (!isset($configuration['width'])) {
            return '';
        }

        $width = (int)$configuration['width'];
        $fileInfoResponse = $this->getFileInfoResponse($fileIdentifier);
        $processingUrl = '';
        if ($width <= 80) {
            $processingUrl = $fileInfoResponse['thumbnails']['mini'] ?? '';
        } elseif ($width <= 250) {
            $processingUrl = $fileInfoResponse['thumbnails']['thul'] ?? '';
        } elseif ($width <= 800) {
            $processingUrl = $fileInfoResponse['thumbnails']['webimage'] ?? '';
        }

        return $processingUrl;
    }

    /**
     * Get cached or original file info from Bynder API
     *
     * Must be public as it was used by our EventListeners
     */
    public function getFileInfoResponse(string $fileIdentifier): array
    {
        $fileCacheIdentifier = $this->getFileCacheIdentifier($fileIdentifier);
        if ($this->cache->has($fileCacheIdentifier)) {
            $fileInfoResponse = $this->cache->get($fileCacheIdentifier);
        } else {
            $fileInfoResponse = $this->bynderClient->getAssetBankManager()->getMediaList([
                'id' => $fileIdentifier
            ])->wait();
            $this->cache->set($fileCacheIdentifier, $fileInfoResponse);
        }

        return is_array($fileInfoResponse) ? $fileInfoResponse : [];
    }

    /**
     * Returns Bynder response for file download location
     *
     * Must be public as it was used by our EventListeners
     */
    public function getMediaDownloadResponse(string $fileIdentifier): array
    {
        $remoteFileResponse = $this->bynderClient->getAssetBankManager()->getMediaDownloadLocation(
            $fileIdentifier
        )->wait();

        if (($remoteFileResponse['statuscode'] ?? '200') === '200') {
            return $remoteFileResponse;
        }

        return [];
    }

    protected function getFileCacheIdentifier(string $fileIdentifier): string
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

    public function addFlashMessage(string $message, string $title = '', int $severity = AbstractMessage::OK): void
    {
        // We activate storeInSession, so that messages can be displayed when click on Save&Close button.
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
}
