<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/bynder2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Bynder2\Command;

use Doctrine\DBAL\Exception;
use JWeiland\Bynder2\Service\BynderSynchronization;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Bynder does not work with folders, so all files are on the root level. So, it's hard to find the related files
 * as we currently do not have any filtering options in the filelist module. The only option you have is: searching.
 * As FAL search is realized on sys_file and sys_file_metadata tables, we need a command to sync all files from
 * Bynder into these tables.
 */
class SyncBynderFilesCommand extends Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly \SplObjectStorage $bynderStorages,
        private readonly BynderSynchronization $bynderSynchronization,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription(
            'Executing this command will retrieve all file records via Bynder API and creates/updates ' .
            'the related sys_file/sys_file_metadata records of TYPO3s FAL system.'
        );
    }

    /**
     * Start synchronization of bynder files
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Checking environment...');
        $tableColumns = $this->getColumnsOfTable('sys_file_metadata');
        foreach (['bynder2_thumb_mini', 'bynder2_thumb_thul', 'bynder2_thumb_webimage'] as $column) {
            if (!isset($tableColumns[$column])) {
                $output->writeln(
                    'Missing columns detected in the "sys_file_metadata" table. '
                    . 'Please analyze the database using the Install Tool before proceeding.'
                );
                return Command::FAILURE;
            }
        }

        $output->writeln('Start synchronizing bynder files');
        $this->synchronizeStorages($output);
        $this->logger->info('All Bynder files have been synchronized');

        return 0;
    }

    protected function synchronizeStorages(OutputInterface $output): void
    {
        if ($this->bynderStorages->count() === 0) {
            $output->writeln('No bynder storages found.');
            $this->logger->warning('No bynder storages found.');
            return;
        }

        foreach ($this->bynderStorages as $bynderStorage) {
            $this->logger->info('Start synchronizing files of storage with UID: ' . $bynderStorage->getUid());
            $this->logger->debug('If solr or other file extractors are activated indexing will need its time.');
            $this->bynderSynchronization->synchronizeStorage($bynderStorage);
            $this->logger->info('Finished synchronizing files of storage with UID: ' . $bynderStorage->getUid());
        }
    }

    protected function getColumnsOfTable(string $table): array
    {
        try {
            return GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable($table)
                ->createSchemaManager()
                ->listTableColumns($table);
        } catch (Exception $e) {
        }

        return [];
    }
}
