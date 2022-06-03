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
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Backend\Routing\UriBuilder;
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
            $view = GeneralUtility::makeInstance(StandaloneView::class);
            $view->setTemplatePathAndFilename(
                GeneralUtility::getFileAbsFileName(
                    'EXT:bynder2/Resources/Private/Templates/ShowAccountStatus.html'
                )
            );

            try {
                /*$configuration = new \Bynder\Api\Impl\PermanentTokens\Configuration(
                    $config['url'],
                    '',
                    ['timeout' => 5] // Guzzle HTTP request options
                );

                $bynderClient = new BynderClient($configuration);
                $user = $bynderClient->getCurrentUser()->wait();*/




                $bynderClient = new BynderClient(new Configuration(
                    $config['url'],
                    'https://www.drs.de/typo3/index.php',
                    $config['clientId'],
                    $config['clientSecret']
                ));

                $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
                echo $uriBuilder->buildUriFromRoute(
                    'get_bynder2_authorization_url',
                    [
                        'clientId' => 'bla',
                    ]
                );
                echo $uriBuilder->buildUriFromRoutePath(
                    '/ext/bynder2/authorization',
                    [
                        'clientId' => 'bla',
                    ]
                );

                $token = $bynderClient->getAccessToken('d10a25e9b3f5a02e78501a62322cf7c9');

                $view->assign('account', $bynderClient->getCurrentUser()->wait());
            } catch (\Exception $exception) {
                return 'Bynder Error: ' . $exception->getMessage();
            }

            return $view->render();
        }

        return 'Please setup bynder first to see your account info here.';
    }
}
