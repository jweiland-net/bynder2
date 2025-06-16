<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/bynder2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Bynder2\Repository;

use Doctrine\DBAL\Exception;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Resource\Exception\FileOperationErrorException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFileAccessPermissionsException;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Repository for accessing records in the "sys_file" table.
 * Primarily utilized during synchronization processes to retrieve all files and identify deleted records.
 */
readonly class SysFileRepository
{
    private const TABLE = 'sys_file';

    public function __construct(
        private QueryBuilder $queryBuilder,
        private ResourceFactory $resourceFactory,
    ) {}

    public function getFileIdentifiersOfStorage(
        int $storageUid,
        $start = 0,
        $numberOfItems = 0,
    ): array {
        $queryBuilder = $this->getRestrictedQueryBuilder();

        try {
            $queryBuilder
                ->select('identifier')
                ->from(self::TABLE)
                ->where(
                    $queryBuilder->expr()->eq(
                        'storage',
                        $queryBuilder->createNamedParameter($storageUid, Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'missing',
                        $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                    ),
                );

            // Default sorting for the FileBrowser modal: display the most recently updated data first.
            // This does not affect the file list module's sorting, as files are sorted manually there via PHP.
            $queryBuilder->orderBy('creation_date', 'DESC');

            if ($start > 0) {
                $queryBuilder->setFirstResult($start);
            }

            if ($numberOfItems > 0) {
                $queryBuilder->setMaxResults($numberOfItems);
            }

            $sysFileRecords = $queryBuilder
                ->executeQuery()
                ->fetchAllAssociative();
        } catch (Exception) {
            $sysFileRecords = [];
        }

        $fileIdentifiers = [];
        foreach ($sysFileRecords as $sysFileRecord) {
            $fileIdentifiers[] = $sysFileRecord['identifier'];
        }

        return $fileIdentifiers;
    }

    public function countFilesOfStorage(int $storageUid): int
    {
        $queryBuilder = $this->getRestrictedQueryBuilder();

        try {
            $numberOfFileRecords = (int)$queryBuilder
                ->count('*')
                ->from(self::TABLE)
                ->where(
                    $queryBuilder->expr()->eq(
                        'storage',
                        $queryBuilder->createNamedParameter($storageUid, Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'missing',
                        $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                    ),
                )
                ->executeQuery()
                ->fetchOne();
        } catch (Exception) {
            $numberOfFileRecords = 0;
        }

        return $numberOfFileRecords;
    }

    public function hasFileIdentifierInStorage(string $identifier, int $storageUid): bool
    {
        $queryBuilder = $this->getRestrictedQueryBuilder();

        try {
            $sysFileRecord = $queryBuilder
                ->select('uid')
                ->from(self::TABLE)
                ->where(
                    $queryBuilder->expr()->eq(
                        'identifier',
                        $queryBuilder->createNamedParameter($identifier, Connection::PARAM_STR)
                    ),
                    $queryBuilder->expr()->eq(
                        'storage',
                        $queryBuilder->createNamedParameter($storageUid, Connection::PARAM_INT)
                    ),
                )->executeQuery()
                ->fetchAssociative();
        } catch (Exception) {
            $sysFileRecord = false;
        }

        return $sysFileRecord !== false;
    }

    public function deleteFile(int $storageUid, string $identifier): void
    {
        $fileObject = $this->resourceFactory->getFileObjectByStorageAndIdentifier($storageUid, $identifier);

        if ($fileObject instanceof FileInterface) {
            try {
                $fileObject->delete();
            } catch (InsufficientFileAccessPermissionsException | FileOperationErrorException) {
            }
        }
    }

    private function getRestrictedQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->queryBuilder;
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        return $queryBuilder;
    }
}
