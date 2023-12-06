<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/bynder2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Bynder2\Form\Element;

use JWeiland\Bynder2\Service\BynderService;
use JWeiland\Bynder2\Service\BynderServiceFactory;
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
            $config = $flexFormService->convertFlexFormContentToArray($this->data['databaseRow']['configuration']);
        } else {
            $config = [];
            foreach ($this->data['databaseRow']['configuration']['data']['sDEF']['lDEF'] as $key => $value) {
                $config[$key] = $value['vDEF'];
            }
        }

        $resultArray['html'] = $this->getHtmlForConnected($config);

        return $resultArray;
    }

    /**
     * Get HTML to show the user, that he is connected with his bynder account
     */
    public function getHtmlForConnected(array $config): string
    {
        if (
            isset($config['clientId'], $config['clientSecret'])
            && $config['clientId'] !== ''
            && $config['clientSecret'] !== ''
        ) {
            if (isset($config['accessToken']) && $config['accessToken'] !== '') {
                $view = $this->getStandaloneView();

                try {
                    $bynderService = $this->getBynderService($config);

                    $view->assign('account', $bynderService->getCurrentUser());
                    $view->assign('expires', $this->getExpires($config, $bynderService));

                    return $view->render();
                } catch (\Exception $exception) {
                    return 'Bynder Error: ' . $exception->getMessage();
                }
            } else {
                return 'Status will be visible after configuring accessToken first';
            }
        }

        return 'Status will be visible after configuring following fields: url, redirectCallback, clientId and clientSecret';
    }

    /**
     * This method has to be called AFTER the first bynder request.
     * That's because the access token will be refreshed if expired while bynder request.
     */
    protected function getExpires(array $config, BynderService $bynderService): int
    {
        $expires = $config['expires'];
        $token = $bynderService->getBynderConfiguration()->getToken();
        if ($token instanceof AccessToken) {
            $expires = $token->getExpires();
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

    protected function getBynderService(array $configuration): BynderService
    {
        return $this
            ->getBynderServiceFactory()
            ->getBynderServiceForConfiguration($configuration);
    }

    protected function getBynderServiceFactory(): BynderServiceFactory
    {
        return GeneralUtility::makeInstance(BynderServiceFactory::class);
    }
}
