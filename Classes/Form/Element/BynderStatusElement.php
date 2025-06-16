<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/bynder2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Bynder2\Form\Element;

use Bynder\Api\Impl\OAuth2\Configuration as AccessTokenConfiguration;
use JWeiland\Bynder2\Client\BynderClientWrapper;
use JWeiland\Bynder2\Service\BynderClientFactory;
use JWeiland\Bynder2\Service\BynderService;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Core\View\ViewInterface;

/*
 * This class retrieves and shows Bynder Account information
 */
class BynderStatusElement extends AbstractFormElement
{
    private const TEMPLATE = 'EXT:bynder2/Resources/Private/Templates/ShowAccountStatus.html';

    public function __construct(
        private readonly BynderService $bynderService,
        private readonly BynderClientFactory $bynderClientFactory,
        private readonly ViewFactoryInterface $viewFactory,
        private readonly FlexFormService $flexFormService,
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

        $resultArray['html'] = $this->getHtmlForConnected(
            $bynderFalConfiguration,
            $this->data['request'] ?? new ServerRequest(),
        );

        return $resultArray;
    }

    /**
     * Get HTML to show the user that he is connected with his bynder account
     */
    public function getHtmlForConnected(array $bynderFalConfiguration, ServerRequestInterface $request): string
    {
        $view = $this->getView($request);

        if (isset($bynderFalConfiguration['accessToken']) && $bynderFalConfiguration['accessToken'] !== '') {
            try {
                $bynderClientWrapper = $this->bynderClientFactory->createClientWrapper($bynderFalConfiguration);
                $view->assignMultiple([
                    'account' => $this->bynderService->getCurrentUser($bynderClientWrapper->getBynderClient()),
                    'expires' => $this->getExpires($bynderFalConfiguration, $bynderClientWrapper),
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

    protected function getView(ServerRequestInterface $request): ViewInterface
    {
        $viewData = new ViewFactoryData(
            templatePathAndFilename: self::TEMPLATE,
            request: $request,
        );

        return $this->viewFactory->create($viewData);
    }
}
