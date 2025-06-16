<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/bynder2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Bynder2\Resource\Processing;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Imaging\ImageDimension;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\Processing\ProcessorInterface;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\CMS\Core\Type\File\ImageInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The LocalImageProcessor always downloads the original file. However, most images displayed on the frontend have
 * a width of approximately 600px. Downloading the full-sized image is less efficient; therefore, it is preferable
 * to retrieve a thumbnail (up to 800px wide) using its CDN URI for improved performance.
 */
class BynderFrontendProcessor implements ProcessorInterface
{
    public function canProcessTask(TaskInterface $task): bool
    {
        // Early return on the wrong storage provider
        if ($task->getSourceFile()->getStorage()->getDriverType() !== 'bynder2') {
            return false;
        }

        // This is a copy&paste from DeferredBackendImageProcessor.
        // Remove the last condition as _processing_ folder is not needed.
        return ($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend()
            && $task->getType() === 'Image'
            && $task->getSourceFile()->getProperty('width') > 0
            && $task->getSourceFile()->getProperty('height') > 0;
    }

    /**
     * Processes the given task and sets the processing result in the task object.
     */
    public function processTask(TaskInterface $task): void
    {
        if ($this->checkForExistingTargetFile($task)) {
            return;
        }

        $imageDimension = ImageDimension::fromProcessingTask($task);

        $targetFile = $task->getTargetFile();
        $originalFileName = $this->getFilenameForImageCropScaleMask($task);
        if ($processingUrl = $this->getProcessingUrl($targetFile, $imageDimension)) {
            file_put_contents(
                $originalFileName,
                file_get_contents($processingUrl)
            );
        } else {
            file_put_contents(
                $originalFileName,
                file_get_contents($task->getSourceFile()->getForLocalProcessing(false))
            );
        }

        $targetFileExtension = $task->getTargetFileExtension();

        $imageOperations = GeneralUtility::makeInstance(GraphicalFunctions::class);

        $configuration = $targetFile->getProcessingConfiguration();
        $configuration['additionalParameters'] ??= '';

        // the result info is an array with 0=width, 1=height, 2=extension, 3=filename
        $result = $imageOperations->resize(
            $originalFileName,
            $targetFileExtension,
            $configuration['width'] ?? '',
            $configuration['height'] ?? '',
            $configuration['additionalParameters'],
            $configuration,
        );

        if ($result === null) {
            $task->setExecuted(true);
            $task->getTargetFile()->setUsesOriginalFile();
        } elseif (!empty($result->getRealPath()) && file_exists($result->getRealPath())) {
            $imageInformation = GeneralUtility::makeInstance(ImageInfo::class, $result->getRealPath());
            $task->setExecuted(true);
            $task->getTargetFile()->setName($task->getTargetFileName());
            $task->getTargetFile()->updateProperties([
                'width' => $imageInformation->getWidth(),
                'height' => $imageInformation->getHeight(),
                'size' => $imageInformation->getSize(),
                'checksum' => $task->getConfigurationChecksum(),
            ]);
            $task->getTargetFile()->updateWithLocalFile($result->getRealPath());
        } else {
            // It seems we have no valid processing result
            $task->setExecuted(false);
        }

        // Do not retain the originally downloaded file, as it may cause excessive growth in the typo3temp folder.
        unlink($originalFileName);
    }

    /**
     * Check if the target file that is to be processed already exists.
     * If it exists, use the metadata from that file and mark the task as done.
     */
    protected function checkForExistingTargetFile(TaskInterface $task): bool
    {
        // the storage of the processed file, not of the original file!
        $storage = $task->getTargetFile()->getStorage();
        $processingFolder = $storage->getProcessingFolder($task->getSourceFile());

        // explicitly check for the raw filename here, as we check for files that existed before we even started
        // processing, i.e., that were processed earlier
        if ($processingFolder->hasFile($task->getTargetFileName())) {
            // When the processed file already exists, set it as a processed file
            $task->getTargetFile()->setName($task->getTargetFileName());

            return true;
        }

        return false;
    }

    /**
     * Bynder delivers 3 pre-configured thumbnails over its CDN.
     * Check if we can use them, for faster rendering.
     * Must be public URL
     */
    public function getProcessingUrl(ProcessedFile $processedFile, ImageDimension $imageDimension): string
    {
        $processingUrl = '';

        // It is necessary to use "->getOriginalFile()" here, as "->getProperty()" on ProcessedFile
        // will only function correctly if the ProcessedFile is newly created (uid === null).
        if ($imageDimension->getWidth() <= 80) {
            $processingUrl = $processedFile->getOriginalFile()->getProperty('bynder2_thumb_mini') ?? '';
        } elseif ($imageDimension->getWidth() <= 250) {
            $processingUrl = $processedFile->getOriginalFile()->getProperty('bynder2_thumb_thul') ?? '';
        } elseif ($imageDimension->getWidth() <= 800) {
            $processingUrl = $processedFile->getOriginalFile()->getProperty('bynder2_thumb_webimage') ?? '';
        }

        return $processingUrl;
    }

    protected function getFilenameForImageCropScaleMask(TaskInterface $task): string
    {
        $targetFileExtension = $task->getTargetFileExtension();

        return Environment::getPublicPath() . '/typo3temp/' . $task->getTargetFile()->generateProcessedFileNameWithoutExtension() . '.' . ltrim(trim($targetFileExtension), '.');
    }
}
