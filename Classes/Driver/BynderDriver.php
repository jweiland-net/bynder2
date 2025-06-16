<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/bynder2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Bynder2\Driver;

use JWeiland\Bynder2\Repository\SysFileRepository;
use JWeiland\Bynder2\Service\BynderClientFactory;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Exception\NoSuchCacheException;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\Capabilities;
use TYPO3\CMS\Core\Resource\Driver\AbstractDriver;
use TYPO3\CMS\Core\Resource\Exception\InvalidFileNameException;
use TYPO3\CMS\Core\Resource\Exception\InvalidPathException;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/*
 * Class which contains all methods to arrange files and folders
 */
class BynderDriver extends AbstractDriver
{
    private const UNSAFE_FILENAME_CHARACTER_EXPRESSION = '\\x00-\\x2C\\/\\x3A-\\x3F\\x5B-\\x60\\x7B-\\xBF';

    protected SysFileRepository $fileRepository;

    protected BynderClientFactory $bynderClientFactory;

    protected FlashMessageService $flashMessageService;

    protected FrontendInterface $cache;

    /**
     * @var array<string, mixed>
     */
    protected array $fileExistsCache = [];

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

    /**
     * @param array<string, mixed> $configuration
     */
    public function __construct(array $configuration)
    {
        parent::__construct($configuration);

        $this->capabilities = new Capabilities(Capabilities::CAPABILITY_BROWSABLE);
    }

    public function processConfiguration(): void {}

    public function initialize(): void
    {
        $this->fileRepository = GeneralUtility::makeInstance(SysFileRepository::class);
        $this->bynderClientFactory = GeneralUtility::makeInstance(BynderClientFactory::class);
        $this->flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);

        try {
            $this->cache = $this->getCacheManager()->getCache('bynder2_file_response');
        } catch (NoSuchCacheException) {
            $this->addFlashMessage(
                'Cache for file information of bynder files could not be created. Please check cache configuration of DB tables',
                'Cache error',
                ContextualFeedbackSeverity::ERROR
            );
        }
    }

    /**
     * We do not merge capabilities from the user of the storage configuration into the actual
     * capabilities of the driver as we do not want to make the storage public or writable.
     */
    public function mergeConfigurationCapabilities(Capabilities $capabilities): Capabilities
    {
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

    public function getParentFolderIdentifierOfIdentifier(string $fileIdentifier): string
    {
        // Bynder has no parent folders. So just return the root folder
        return '/';
    }

    /**
     * This driver is marked as non-public, so this will never be called:
     */
    public function getPublicUrl(string $identifier): string
    {
        return '';
    }

    public function createFolder(
        string $newFolderName,
        string $parentFolderIdentifier = '',
        bool $recursive = false
    ): string {
        throw new \RuntimeException('Bynder driver is readonly. Folders cannot be created.');
    }

    public function renameFolder(string $folderIdentifier, string $newName): array
    {
        throw new \RuntimeException('Bynder driver is readonly. Folders cannot be renamed.');
    }

    public function deleteFolder(string $folderIdentifier, bool $deleteRecursively = false): bool
    {
        throw new \RuntimeException('Bynder driver is readonly. Folders cannot be deleted.');
    }

    public function fileExists(string $fileIdentifier): bool
    {
        // Early return for root folder "/"
        if ($fileIdentifier === '/') {
            return true;
        }

        if (array_key_exists($fileIdentifier, $this->fileExistsCache)) {
            return $this->fileExistsCache[$fileIdentifier];
        }

        $this->fileExistsCache[$fileIdentifier] = $this->fileRepository->hasFileIdentifierInStorage($fileIdentifier, $this->storageUid);

        return $this->fileExistsCache[$fileIdentifier];
    }

    public function folderExists(string $folderIdentifier): bool
    {
        // Bynder does not work with folder structures. So just return true for the root folder. Else false
        return $folderIdentifier === '/';
    }

    public function isFolderEmpty(string $folderIdentifier): bool
    {
        // Just count the files in the root folder
        return (bool)$this->countFilesInFolder('/');
    }

    public function addFile(
        string $localFilePath,
        string $targetFolderIdentifier,
        string $newFileName = '',
        bool $removeOriginal = true
    ): string {
        throw new \RuntimeException('Bynder driver is readonly. Files cannot be added.');
    }

    public function createFile(string $fileName, string $parentFolderIdentifier): string
    {
        throw new \RuntimeException('Bynder driver is readonly. Files cannot be created.');
    }

    public function copyFileWithinStorage(
        string $fileIdentifier,
        string $targetFolderIdentifier,
        string $fileName
    ): string {
        throw new \RuntimeException('Bynder driver is readonly. Files cannot be copied within storage.');
    }

    public function renameFile(string $fileIdentifier, string $newName): string
    {
        throw new \RuntimeException('Bynder driver is readonly. Files cannot be renamed.');
    }

    public function replaceFile(string $fileIdentifier, string $localFilePath): bool
    {
        throw new \RuntimeException('Bynder driver is readonly. Files cannot be replaced.');
    }

    public function deleteFile(string $fileIdentifier): bool
    {
        throw new \RuntimeException('Bynder driver is readonly. Files cannot be deleted.');
    }

    public function hash(string $fileIdentifier, string $hashAlgorithm): string
    {
        // All core calls were done with sha1. No need to check any other $hashAlgorithm
        return sha1($fileIdentifier);
    }

    public function moveFileWithinStorage(
        string $fileIdentifier,
        string $targetFolderIdentifier,
        string $newFileName
    ): string {
        throw new \RuntimeException('Bynder driver is readonly. Files cannot be moved within storage.');
    }

    public function moveFolderWithinStorage(
        string $sourceFolderIdentifier,
        string $targetFolderIdentifier,
        string $newFolderName
    ): array {
        throw new \RuntimeException('Bynder driver is readonly. Folders cannot be moved within storage.');
    }

    public function copyFolderWithinStorage(
        string $sourceFolderIdentifier,
        string $targetFolderIdentifier,
        string $newFolderName
    ): bool {
        throw new \RuntimeException('Bynder driver is readonly. Folders cannot be copied within storage.');
    }

    public function getFileContents(string $fileIdentifier): string
    {
        try {
            $client = $this->bynderClientFactory->createClientWrapper($this->configuration);
            if ($cdnDownloadUrl = $client->getCdnDownloadUrl($fileIdentifier)) {
                return file_get_contents($cdnDownloadUrl);
            }
        } catch (\Exception) {
        }

        return '';
    }

    public function setFileContents(string $fileIdentifier, string $contents): int
    {
        throw new \RuntimeException('Bynder driver is readonly. Files content cannot be written.');
    }

    public function fileExistsInFolder(string $fileName, string $folderIdentifier): bool
    {
        return $this->fileExists($folderIdentifier . $fileName);
    }

    public function folderExistsInFolder(string $folderName, string $folderIdentifier): bool
    {
        // Bynder has only ONE folder. So there never can be a folder IN another folder
        return false;
    }

    public function getFileForLocalProcessing(string $fileIdentifier, bool $writable = true): string
    {
        return $this->copyFileToTemporaryPath($fileIdentifier);
    }

    public function getPermissions(string $identifier): array
    {
        // Currently, only READ permission is implemented
        return [
            'r' => true,
            'w' => false,
        ];
    }

    public function dumpFileContents(string $identifier): void
    {
        $handle = fopen('php://output', 'wb');
        fwrite($handle, $this->getFileContents($identifier));
        fclose($handle);
    }

    public function isWithin(string $folderIdentifier, string $identifier): bool
    {
        // As Bynder just has only ONE folder, this is always true
        return $folderIdentifier === '/' || $folderIdentifier === '';
    }

    public function getFileInfoByIdentifier(string $fileIdentifier, array $propertiesToExtract = []): array
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

        if (!$this->cache->has($fileIdentifier)) {
            // ToDo: Retrieve data from sys_file
            return [];
        }

        $fileResponse = $this->cache->get($fileIdentifier);

        $properties = [];
        if ($fileResponse !== []) {
            if ($propertiesToExtract === []) {
                $propertiesToExtract = self::DEFAULT_PROPERTIES_TO_EXTRACT;
            }

            foreach ($propertiesToExtract as $property) {
                $properties[$property] = $this->getSpecificFileInformation($fileResponse, $property);
            }
        }

        return $properties;
    }

    /**
     * @param array<string, mixed> $fileResponse
     * @throws InvalidFileNameException
     */
    public function getSpecificFileInformation(array $fileResponse, string $property): string
    {
        switch ($property) {
            case 'size':
                return (string)$fileResponse['fileSize'];
            case 'mtime':
            case 'atime':
                $date = \DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $fileResponse['dateModified']);
                return $date instanceof \DateTime ? $date->format('U') : '0';
            case 'ctime':
                $date = \DateTime::createFromFormat('Y-m-d\TH:i:s\Z', $fileResponse['dateCreated']);
                return $date instanceof \DateTime ? $date->format('U') : '0';
            case 'name':
                $fileName = $this->sanitizeFileName($fileResponse['name']);
                // In most cases, Bynder does not contain the fileExt in name.
                // As FAL extracts fileExt from name, we have to append it manually
                $fileExt = strtolower($fileResponse['extension'][0] ?? '');
                if (!str_ends_with(strtolower($fileName), '.' . $fileExt)) {
                    $fileName .= '.' . $fileExt;
                }
                return $this->sanitizeFileName($fileName);
            case 'extension':
                return strtolower($fileResponse['extension'][0] ?? '');
            case 'mimetype':
                $fileExt = strtolower($fileResponse['extension'][0] ?? '');
                if (in_array($fileExt, ['jpg', 'jpeg', 'bmp', 'svg', 'ico', 'pdf', 'png', 'tiff'])) {
                    return 'image/' . $fileExt;
                }
                return 'text/' . $fileExt;
            case 'identifier':
                return $fileResponse['id'];
            case 'storage':
                return (string)$this->storageUid;
            case 'identifier_hash':
                // Do not use Bynder hash. Else TYPO3 will not find records from sys_file
                return $this->hashIdentifier($fileResponse['id'] ?? '');
            case 'folder_hash':
                return $this->hashIdentifier('/');
            case 'title':
                return $fileResponse['name'] ?? '';
            case 'description':
                return $fileResponse['description'] ?? '';
            case 'width':
                return (string)($fileResponse['width'] ?? '0');
            case 'height':
                return (string)($fileResponse['height'] ?? '0');
            case 'copyright':
                return (string)($fileResponse['copyright'] ?? '');
            case 'keywords':
                return implode(', ', $fileResponse['tags'] ?? []);
            default:
                if (isset($fileResponse[$property])) {
                    return $fileResponse[$property];
                }
        }
        throw new \InvalidArgumentException(sprintf('The information "%s" is not available.', $property));
    }

    /**
     * @return array<string, int|string>
     */
    public function getFolderInfoByIdentifier(string $folderIdentifier): array
    {
        // Bynder has just ONE root folder
        return [
            'identifier' => '/',
            'name' => 'Bynder Root Folder',
            'storage' => $this->storageUid,
        ];
    }

    public function getFileInFolder(string $fileName, string $folderIdentifier): string
    {
        return $this->canonicalizeAndCheckFileIdentifier('/' . $fileName);
    }

    /**
     * @param array<int, mixed> $filenameFilterCallbacks
     * @return array<int, string>
     */
    public function getFilesInFolder(
        string $folderIdentifier,
        int $start = 0,
        int $numberOfItems = 0,
        bool $recursive = false,
        array $filenameFilterCallbacks = [],
        string $sort = '',
        bool $sortRev = false
    ): array {
        // Respecting the sorting order is not practical, as FileList::sortResources
        // manually sorts all thousands of files in the "file list" module.
        return $this->fileRepository->getFileIdentifiersOfStorage(
            $this->storageUid,
            $start,
            $numberOfItems,
        );
    }

    public function getFolderInFolder(string $folderName, string $folderIdentifier): string
    {
        // As Bynder does not have subfolders, this method should never be called.
        return '';
    }

    /**
     * @param array<int, mixed> $folderNameFilterCallbacks
     */
    public function getFoldersInFolder(
        string $folderIdentifier,
        int $start = 0,
        int $numberOfItems = 0,
        bool $recursive = false,
        array $folderNameFilterCallbacks = [],
        string $sort = '',
        bool $sortRev = false
    ): array {
        // Bynder does not work folder-based. So just return an empty array for 0 folders.
        return [];
    }

    public function countFilesInFolder(
        string $folderIdentifier,
        bool $recursive = false,
        array $filenameFilterCallbacks = []
    ): int {
        if ($this->storageUid !== null) {
            return $this->fileRepository->countFilesOfStorage($this->storageUid);
        }

        return 0;
    }

    public function countFoldersInFolder(
        string $folderIdentifier,
        bool $recursive = false,
        array $folderNameFilterCallbacks = []
    ): int {
        // Bynder does not work folder-based. So just return 0.
        return 0;
    }

    /**
     * @throws InvalidPathException
     */
    protected function canonicalizeAndCheckFilePath(string $filePath): string
    {
        $filePath = PathUtility::getCanonicalPath($filePath);

        // filePath must be valid
        // a Special case is required by vfsStream in Unit Test context
        if (!GeneralUtility::validPathStr($filePath)) {
            throw new InvalidPathException('File ' . $filePath . ' is not valid (".." and "//" is not allowed in path).', 1320286857);
        }

        return $filePath;
    }

    protected function canonicalizeAndCheckFileIdentifier(string $fileIdentifier): string
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

    protected function canonicalizeAndCheckFolderIdentifier(string $folderIdentifier): string
    {
        if ($folderIdentifier === '/') {
            return $folderIdentifier;
        }

        return rtrim($this->canonicalizeAndCheckFileIdentifier($folderIdentifier), '/') . '/';
    }

    /**
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
    protected function getTemporaryPathForFile(string $fileIdentifier): string
    {
        // Fallback to "jpg". FAL needs an extension, else img processing will not work
        $fileExtension = $this->getFileInfoByIdentifier($fileIdentifier, ['extension'])['extension'] ?? 'jpg';

        return GeneralUtility::tempnam(
            'bynder-tempfile-',
            '.' . $fileExtension
        );
    }

    /**
     * This is a copy of LocalDriver
     */
    public function sanitizeFileName(string $fileName, string $charset = 'utf-8'): string
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

    private function getCacheManager(): CacheManager
    {
        return GeneralUtility::makeInstance(CacheManager::class);
    }
}
