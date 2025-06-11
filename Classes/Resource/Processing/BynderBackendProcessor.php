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
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Imaging\Exception\ZeroImageDimensionException;
use TYPO3\CMS\Core\Imaging\ImageDimension;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\Processing\ProcessorInterface;
use TYPO3\CMS\Core\Resource\Processing\TaskInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The default DeferredBackendImageProcessor attempts to persist the ProcessedImage, but omits the processing_url.
 * When invoking $processedFile->toArray(), the processing_url is not included.
 * Since we are utilizing predefined thumbnail URIs provided by the Bynder API, additional image processing is
 * unnecessary. Simply use the provided URL directly.
 */
class BynderBackendProcessor implements ProcessorInterface
{
    private const UNAVAILABLE_IMAGE_PATH = 'EXT:bynder/Resources/Public/Icons/ImageUnavailable.svg';

    public function canProcessTask(TaskInterface $task): bool
    {
        // Early return on the wrong storage provider
        if ($task->getSourceFile()->getStorage()->getDriverType() !== 'bynder2') {
            return false;
        }

        // This is a copy&paste from DeferredBackendImageProcessor.
        // Remove the last condition as _processing_ folder is not needed.
        $context = GeneralUtility::makeInstance(Context::class);
        return ($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend()
            && $task->getType() === 'Image'
            && in_array($task->getName(), ['Preview', 'CropScaleMask'], true)
            && (!$context->hasAspect('fileProcessing') || $context->getPropertyFromAspect('fileProcessing', 'deferProcessing'))
            && $task->getSourceFile()->getProperty('width') > 0
            && $task->getSourceFile()->getProperty('height') > 0;
    }

    /**
     * Processes the given task and sets the processing result in the task object.
     */
    public function processTask(TaskInterface $task): void
    {
        try {
            $imageDimension = ImageDimension::fromProcessingTask($task);
        } catch (ZeroImageDimensionException $e) {
            // To not fail image processing, we just assume an image dimension here
            $imageDimension = new ImageDimension(64, 64);
        }

        $processedFile = $task->getTargetFile();
        if ($processingUrl = $this->getProcessingUrl($processedFile, $imageDimension)) {
            $processedFile->updateProcessingUrl($processingUrl);
            $processedFile->updateProperties([
                'width' => $imageDimension->getWidth(),
                'height' => $imageDimension->getHeight(),
            ]);
        }

        $task->setExecuted(true);
    }

    /**
     * Bynder delivers 3 pre-configured thumbnails over its CDN.
     * Check if we can use them, for faster rendering.
     * Must be public URL
     */
    public function getProcessingUrl(ProcessedFile $processedFile, ImageDimension $imageDimension): string
    {
        if ($imageDimension->getWidth() <= 80) {
            $processingUrl = $processedFile->getProperty('bynder2_thumb_mini') ?? '';
        } elseif ($imageDimension->getWidth() <= 250) {
            $processingUrl = $processedFile->getProperty('bynder2_thumb_thul') ?? '';
        } elseif ($imageDimension->getWidth() <= 800) {
            $processingUrl = $processedFile->getProperty('bynder2_thumb_webimage') ?? '';
        } else {
            $processingUrl = self::UNAVAILABLE_IMAGE_PATH;
        }

        return $processingUrl;
    }
}
