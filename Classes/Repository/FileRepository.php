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
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FileRepository
{
    public function __construct(
        private readonly QueryBuilder $queryBuilder,
    ) {}

    public function getFileIdentifiersOfStorage(int $storageUid): array
    {
        $queryBuilder = $this->getQueryBuilder();

        try {
            $sysFileRecords = $queryBuilder
                ->select('identifier')
                ->from('sys_file')
                ->where(
                    $queryBuilder->expr()->eq(
                        'storage',
                        $queryBuilder->createNamedParameter($storageUid, Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'missing',
                        $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                    ),
                )->executeQuery()
                ->fetchAllAssociative();
        } catch (Exception $e) {
            $sysFileRecords = [];
        }

        $fileIdentifiers = [];
        foreach ($sysFileRecords as $sysFileRecord) {
            $fileIdentifiers[] = $sysFileRecord['identifier'];
        }

        return $fileIdentifiers;
    }

    public function hasFileIdentifierInStorage(string $identifier, int $storageUid): bool
    {
        $queryBuilder = $this->getQueryBuilder();

        try {
            $sysFileRecord = $queryBuilder
                ->select('uid')
                ->from('sys_file')
                ->where(
                    $queryBuilder->expr()->eq(
                        'identifier',
                        $queryBuilder->createNamedParameter($identifier, Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'storage',
                        $queryBuilder->createNamedParameter($storageUid, Connection::PARAM_INT)
                    ),
                )->executeQuery()
                ->fetchAssociative();
        } catch (Exception $e) {
            $sysFileRecord = [];
        }

        return $sysFileRecord !== [];
    }

    private function getQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->queryBuilder;
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        return $queryBuilder;
    }
}
