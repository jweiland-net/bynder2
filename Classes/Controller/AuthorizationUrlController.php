<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/bynder2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Bynder2\Controller;

use JWeiland\Bynder2\Service\BynderClientFactory;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Core\View\ViewInterface;

/**
 * Show code (to retrieve an access token) after a Bynder App was authorized and redirectCallback was called.
 */
final readonly class AuthorizationUrlController
{
    private const TEMPLATE = 'EXT:bynder2/Resources/Private/Templates/ShowAccessToken.html';

    public function __construct(
        protected ViewFactoryInterface $viewFactory,
        protected BynderClientFactory $bynderClientFactory,
        protected \SplObjectStorage $bynderStorages,
    ) {}

    public function processRequest(ServerRequestInterface $request): ResponseInterface
    {
        $parameters = $request->getQueryParams();
        $view = $this->getView($request);

        if (isset($parameters['ext-bynder-code']) && $parameters['ext-bynder-code'] !== '') {
            $code = strip_tags($parameters['ext-bynder-code']);

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
                    $view->assign('storages', $this->bynderStorages);

                    if (count($this->bynderStorages) === 1) {
                        $storage = $this->bynderStorages->current();
                        $accessToken = $this->getAccessToken(
                            $this->getBynderStorage($storage->getUid()),
                            $parameters['ext-bynder-code']
                        );
                        $this->assignAccessTokenToView($view, $accessToken);
                    }
                }
            } catch (\Exception) {
                return new HtmlResponse(
                    'Bynder API: Bad Request. In most cases that means that the code has expired.
                    Please try to re-authorize the Bynder App'
                );
            }

            return new HtmlResponse($view->render());
        }

        return new HtmlResponse('Request does not contain a bynder code');
    }

    protected function assignAccessTokenToView(ViewInterface $moduleTemplate, AccessToken $accessToken): void
    {
        $moduleTemplate->assign('accessToken', $accessToken->getToken());
        $moduleTemplate->assign('refreshToken', $accessToken->getRefreshToken());
        $moduleTemplate->assign('expires', $accessToken->getExpires());
    }

    protected function getAccessToken(ResourceStorage $resourceStorage, string $code): AccessToken
    {
        return $this->bynderClientFactory
            ->createClient($resourceStorage->getConfiguration())
            ->getAccessToken($code);
    }

    protected function getBynderStorage(int $storageUid): ?ResourceStorage
    {
        foreach ($this->bynderStorages as $bynderStorage) {
            if ($bynderStorage->getUid() === $storageUid) {
                return $bynderStorage;
            }
        }

        return null;
    }

    private function getView(ServerRequestInterface $request): ViewInterface
    {
        $viewData = new ViewFactoryData(
            templatePathAndFilename: self::TEMPLATE,
            request: $request,
        );

        return $this->viewFactory->create($viewData);
    }
}
