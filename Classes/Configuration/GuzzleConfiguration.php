<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/bynder2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Bynder2\Configuration;

class GuzzleConfiguration
{
    private const DEFAULT_CONNECT_TIMEOUT = 10;

    /**
     * @param array<string, mixed> $typo3RequestOptions
     * @return array<string, mixed>
     */
    public function getConfiguration(array $typo3RequestOptions): array
    {
        $typo3RequestOptions['connect_timeout'] = (int)($typo3RequestOptions['connect_timeout'] ?? 0);
        $typo3RequestOptions['timeout'] = (int)($typo3RequestOptions['timeout'] ?? 0);

        // Set default timeouts if not set.
        if ($typo3RequestOptions['timeout'] === 0) {
            // We subtract 5 seconds to ensure PHP has sufficient time to handle potential failures gracefully
            $maxExecutionTimeout = (int)(ini_get('max_execution_time') ?: 30);
            $typo3RequestOptions['timeout'] = $maxExecutionTimeout - 5;
        }

        if ($typo3RequestOptions['connect_timeout'] === 0) {
            $typo3RequestOptions['connect_timeout'] = self::DEFAULT_CONNECT_TIMEOUT;
        }

        // Remove the HTTP handler because Guzzle does not support interpreting empty array handlers.
        if (isset($typo3RequestOptions['handler']) && $typo3RequestOptions['handler'] === []) {
            unset($typo3RequestOptions['handler']);
        }

        return $typo3RequestOptions;
    }
}
