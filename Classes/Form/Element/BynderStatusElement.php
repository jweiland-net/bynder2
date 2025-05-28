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
use Bynder\Api\Impl\OAuth2\Configuration as AccessTokenConfiguration;
use JWeiland\Bynder2\Client\BynderClientWrapper;
use JWeiland\Bynder2\Service\BynderClientFactory;
use JWeiland\Bynder2\Service\BynderService;
use League\OAuth2\Client\Token\AccessToken;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/*
 * This class retrieves and shows Bynder Account information
 */
class BynderStatusElement extends AbstractFormElement
{
    public function render(): array
    {
        $resultArray = $this->initializeResultArray();
        if (is_string($this->data['databaseRow']['configuration'])) {
            $flexFormService = GeneralUtility::makeInstance(FlexFormService::class);
            $bynderFalConfiguration = $flexFormService->convertFlexFormContentToArray($this->data['databaseRow']['configuration']);
        } else {
            $bynderFalConfiguration = array_map(static function ($value): string {
                return $value['vDEF'];
            }, $this->data['databaseRow']['configuration']['data']['sDEF']['lDEF']);
        }

        $resultArray['html'] = $this->getHtmlForConnected($bynderFalConfiguration);

        return $resultArray;
    }

    /**
     * Get HTML to show the user that he is connected with his bynder account
     */
    public function getHtmlForConnected(array $bynderFalConfiguration): string
    {
        $view = $this->getStandaloneView();

        if (isset($bynderFalConfiguration['accessToken']) && $bynderFalConfiguration['accessToken'] !== '') {
            try {
                $bynderClientWrapper = $this->getBynderClientFactory()->createClientWrapper($bynderFalConfiguration);
                $view->assignMultiple([
                    'account' => $this->getBynderService()->getCurrentUser($bynderClientWrapper->getBynderClient()),
                    'expires' => $this->getExpires($bynderFalConfiguration, $bynderClientWrapper)
                ]);

                return $view->render();
            } catch (\Exception $exception) {
                return 'Bynder Error: ' . $exception->getMessage();
            }
        }

        return 'Status will be visible after configuring following fields: url, redirectCallback, clientId and clientSecret';
    }

    /**
     * This method should be invoked only *after* completing any Bynder API request.
     * The reason is that the access token is automatically refreshed during a Bynder request if it has expired.
     * A Bynder request is required to obtain the updated "expire" value of the access token.
     */
    protected function getExpires(array $bynderFalConfiguration, BynderClientWrapper $bynderClientWrapper): int
    {
        $expires = $bynderFalConfiguration['expires'];

        if (($bynderTokenConfiguration = $bynderClientWrapper->getBynderTokenConfiguration()->getToken())
            && $bynderTokenConfiguration instanceof AccessTokenConfiguration
            && ($accessToken = $bynderTokenConfiguration->getToken())
            && $accessToken instanceof AccessToken
        ) {
            $expires = $accessToken->getExpires();
        }

        return (int)$expires;
    }

    protected function getStandaloneView(): StandaloneView
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(
            'EXT:bynder2/Resources/Private/Templates/ShowAccountStatus.html'
        );

        return $view;
    }

    protected function getBynderService(): BynderService
    {
        return GeneralUtility::makeInstance(BynderService::class);
    }

    protected function getBynderClientFactory(): BynderClientFactory
    {
        return GeneralUtility::makeInstance(BynderClientFactory::class);
    }

    /**
     * @throws \Exception
     */
    protected function getBynderClient(array $configuration): BynderClient
    {
        return $this->getBynderClientFactory()->createClient($configuration);
    }
}
