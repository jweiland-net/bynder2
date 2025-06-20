<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/bynder2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Bynder2\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * After authorization of Bynder APP you will be redirected back to the URL configured in redirectCallback. In most
 * cases this should be the TYPO3 backend. This middleware will catch such redirects and shows the bynder code
 * you have to add at the Bynder sys_file_storage record.
 */
class ShowBynderAuthorizationCodeMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $queryParameters = $request->getQueryParams();

        if (
            isset($queryParameters['state'], $queryParameters['code'])
            && $queryParameters['state'] !== ''
            && $queryParameters['code'] !== ''
        ) {
            return new RedirectResponse(
                (string)$this->getUriBuilder()->buildUriFromRoute(
                    'bynder_authorization_code',
                    [
                        'ext-bynder-code' => htmlspecialchars(strip_tags($queryParameters['code'])),
                    ]
                ),
                303
            );
        }

        return $handler->handle($request);
    }

    private function getUriBuilder(): UriBuilder
    {
        return GeneralUtility::makeInstance(UriBuilder::class);
    }
}
