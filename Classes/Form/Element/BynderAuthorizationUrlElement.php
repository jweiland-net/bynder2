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
use Bynder\Api\Impl\OAuth2\Configuration;
use JWeiland\Bynder2\Service\BynderService;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/*
 * This class retrieves the Bynder Authorization URL
 */
class BynderAuthorizationUrlElement extends AbstractFormElement
{
    public function render(): array
    {
        $resultArray = $this->initializeResultArray();
        if (is_string($this->data['databaseRow']['configuration'])) {
            $flexFormService = GeneralUtility::makeInstance(FlexFormService::class);
            $config = $flexFormService->convertFlexFormContentToArray($this->data['databaseRow']['configuration']);
        } else {
            $config = [];
            foreach ($this->data['databaseRow']['configuration']['data']['sDEF']['lDEF'] as $key => $value) {
                $config[$key] = $value['vDEF'];
            }
        }

        $resultArray['html'] = $this->getHtmlForAuthorizationUrl($config);

        return $resultArray;
    }

    /**
     * Get HTML to show the user, that he is connected with his bynder account
     */
    public function getHtmlForAuthorizationUrl(array $config): string
    {
        if (
            isset(
                $config['url'],
                $config['redirectCallback'],
                $config['clientId'],
                $config['clientSecret']
            )
            && $config['url'] !== ''
            && $config['redirectCallback'] !== ''
            && $config['clientId'] !== ''
            && $config['clientSecret'] !== ''
        ) {
            if (StringUtility::beginsWith($config['url'], 'http')) {
                return 'Field URL has just to be the domain. Please remove scheme like http:// or https://';
            }

            try {
                return sprintf(
                    'Authorization URL: <a href="%s" target="_blank" title="%s">%s</a>',
                    $this->getBynderService($config)->getAuthorizationUrl(),
                    'Authorize Bynder App',
                    'Authorize Bynder App'
                );
            } catch (\Exception $exception) {
                return 'Bynder Error: ' . $exception->getMessage();
            }
        }

        return 'Please setup url, redirectCallback, clientId and clientSecret first.';
    }

    protected function getBynderService(array $configuration): BynderService
    {
        return GeneralUtility::makeInstance(BynderService::class, $configuration);
    }
}
