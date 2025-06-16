<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/bynder2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Bynder2\Form\Element;

use Bynder\Api\BynderClient;
use JWeiland\Bynder2\Service\BynderClientFactory;
use JWeiland\Bynder2\Service\BynderService;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Service\FlexFormService;

/**
 * This class retrieves the Bynder Authorization URL
 */
class BynderAuthorizationUrlElement extends AbstractFormElement
{
    public function __construct(
        private readonly FlexFormService $flexFormService,
        private readonly BynderService $bynderService,
        private readonly BynderClientFactory $bynderClientFactory,
    ) {}

    public function render(): array
    {
        $resultArray = $this->initializeResultArray();
        if (is_string($this->data['databaseRow']['configuration'])) {
            $bynderFalConfiguration = $this->flexFormService->convertFlexFormContentToArray(
                $this->data['databaseRow']['configuration']
            );
        } else {
            $bynderFalConfiguration = array_map(static function ($value): string {
                return $value['vDEF'];
            }, $this->data['databaseRow']['configuration']['data']['sDEF']['lDEF']);
        }

        $resultArray['html'] = $this->getHtmlForAuthorizationUrl($bynderFalConfiguration);

        return $resultArray;
    }

    /**
     * Get HTML to show the user that he is connected with his bynder account
     */
    public function getHtmlForAuthorizationUrl(array $bynderFalConfiguration): string
    {
        try {
            $bynderClient = $this->getBynderClient($bynderFalConfiguration);

            return sprintf(
                'Authorization URL: <a href="%s" target="_blank" title="%s">%s</a>',
                $this->bynderService->getAuthorizationUrl($bynderClient),
                'Authorize Bynder App',
                'Authorize Bynder App'
            );
        } catch (\Exception $exception) {
            return 'Bynder Error: ' . $exception->getMessage();
        }
    }

    /**
     * @throws \Exception
     */
    protected function getBynderClient(array $configuration): BynderClient
    {
        return $this->bynderClientFactory->createClient($configuration);
    }
}
