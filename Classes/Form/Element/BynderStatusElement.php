<?php

declare(strict_types=1);

/*
 * This file is part of the package stefanfroemken/dropbox.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\FalBynder\Form\Element;

use Bynder\Api\BynderApiFactory;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
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
            isset(
                $config['consumer_key'],
                $config['consumer_secret'],
                $config['token_key'],
                $config['token_secret']
            )
        ) {
            $view = GeneralUtility::makeInstance(StandaloneView::class);
            $view->setTemplatePathAndFilename(
                GeneralUtility::getFileAbsFileName(
                    'EXT:fal_bynder/Resources/Templates/ShowAccountStatus.html'
                )
            );

            try {
                $bynderApi = BynderApiFactory::create(
                    [
                        'consumerKey' => $config['consumer_key'],
                        'consumerSecret' => $config['consumer_secret'],
                        'token' => $config['token_key'],
                        'tokenSecret' => $config['token_secret'],
                        'baseUrl' => $config['url']
                    ]
                );

                $view->assign('account', $bynderApi->getCurrentUser()->wait());
            } catch (\Exception $exception) {
                return 'Bynder Error: ' . $exception->getMessage();
            }

            return $view->render();
        }

        return 'Please setup bynder first to see your account info here.';
    }
}
