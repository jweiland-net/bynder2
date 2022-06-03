<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Bynder2\Controller;

use JWeiland\Bynder2\Service\BynderService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * Use this ajax request to retrieve the authorization URL for Bynder OAuth 2.0
 */
class AjaxController
{
    /**
     * Handles the actual process from within the ajaxExec function
     * therefore, it does exactly the same as the real typo3/tce_file.php.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function processAjaxRequest(ServerRequestInterface $request): ResponseInterface
    {
        if (!$request->hasHeader('ext-bynder2')) {
            return new JsonResponse([
                'error' => 'Invalid request for EXT:bynder2.',
            ], 400);
        }

        $credentials = $this->getClientIdAndSecretFromRequest($request);
        if ($credentials === null) {
            return new JsonResponse([
                'error' => 'Request does not contain needed client ID and client secret for bynder.',
            ], 400);
        }

        $bynderService = $this->getBynderService($credentials);

        return new JsonResponse([
            'authorizationUrl' => $bynderService->getAuthorizationUrl(),
        ]);
    }

    protected function getClientIdAndSecretFromRequest(ServerRequestInterface $request): ?array
    {
        $getParameters = $request->getQueryParams();

        if (!isset(
            $getParameters['clientId'],
            $getParameters['clientSecret'],
            $getParameters['bynderDomain'],
            $getParameters['redirectCallback']
        )) {
            return null;
        }

        return [
            'clientId' => $getParameters['clientId'],
            'clientSecret' => $getParameters['clientSecret'],
            'bynderDomain' => $getParameters['bynderDomain'],
            'redirectCallback' => $getParameters['redirectCallback'],
        ];
    }

    protected function getBynderService(array $credentials): BynderService
    {
        return GeneralUtility::makeInstance(BynderService::class, $credentials);
    }
}
