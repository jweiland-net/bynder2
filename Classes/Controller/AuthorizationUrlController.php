<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/bynder2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Bynder2\Controller;

use JWeiland\Bynder2\Service\BynderService;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/*
 * Show code (to retrieve an access token) after a Bynder App was authorized and redirectCallback was called.
 */
class AuthorizationUrlController
{
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        // $this->getLanguageService()->includeLLFile('EXT:core/Resources/Private/Language/locallang_misc.xlf');
    }

    public function processRequest(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getQueryParams();
        $this->moduleTemplate->setTitle('Generate Bynder Access Token');

        if (isset($parameters['ext-bynder-code']) && $parameters['ext-bynder-code'] !== '') {
            $code = strip_tags($parameters['ext-bynder-code']);

            $view = $this->getStandaloneView();
            $view->assign('code', $code);

            try {
                if (
                    isset($parameters['ext-bynder2-storage'])
                    && MathUtility::canBeInterpretedAsInteger($parameters['ext-bynder2-storage'])
                ) {
                    $accessToken = $this->getAccessToken(
                        $this->getBynderStorage((int)$parameters['ext-bynder2-storage']),
                        $parameters['ext-bynder-code']
                    );
                    $this->assignAccessTokenToView($view, $accessToken);
                } else {
                    $bynderStorages = $this->getBynderStorages();
                    $view->assign('storages', $this->getBynderStorages());

                    if (count($bynderStorages) === 1) {
                        $storage = current($bynderStorages);
                        $accessToken = $this->getAccessToken(
                            $this->getBynderStorage($storage->getUid()),
                            $parameters['ext-bynder-code']
                        );
                        $this->assignAccessTokenToView($view, $accessToken);
                    }
                }
            } catch (\Exception $exception) {
                return new HtmlResponse(
                    'Bynder API: Bad Request. In most cases that means that the code has expired.
                    Please try to re-authorize the Bynder App'
                );
            }

            $this->moduleTemplate->setContent($view->render());

            return new HtmlResponse($this->moduleTemplate->renderContent());
        }

        return new HtmlResponse('Request does not contain a bynder code');
    }

    protected function assignAccessTokenToView(StandaloneView $view, AccessToken $accessToken): void
    {
        $view->assign('accessToken', $accessToken->getToken());
        $view->assign('refreshToken', $accessToken->getRefreshToken());
        $view->assign('expires', $accessToken->getExpires());
    }

    protected function getAccessToken(ResourceStorage $resourceStorage, string $code): AccessToken
    {
        return $this->getBynderService($resourceStorage)
            ->getBynderClient()
            ->getAccessToken($code);
    }

    /**
     * @return array|ResourceStorage[]
     */
    protected function getBynderStorages(): array
    {
        return $this->getStorageRepository()->findByStorageType('bynder2');
    }

    protected function getBynderStorage(int $storageUid): ResourceStorage
    {
        return $this->getStorageRepository()->findByUid($storageUid);
    }

    protected function getBynderService(ResourceStorage $resourceStorage): BynderService
    {
        $storageConfiguration = $resourceStorage->getConfiguration();
        $configuration = [
            'url' => $storageConfiguration['url'],
            'redirectCallback' => $storageConfiguration['redirectCallback'],
            'clientId' => $storageConfiguration['clientId'],
            'clientSecret' => $storageConfiguration['clientSecret'],
        ];

        return GeneralUtility::makeInstance(BynderService::class, $configuration);
    }

    protected function getStorageRepository(): StorageRepository
    {
        return GeneralUtility::makeInstance(StorageRepository::class);
    }

    protected function getStandaloneView(): StandaloneView
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(
            'EXT:bynder2/Resources/Private/Templates/ShowAccessToken.html'
        );

        return $view;
    }
}
