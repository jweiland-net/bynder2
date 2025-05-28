<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/bynder2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Bynder2\Configuration;

use Bynder\Api\Impl\OAuth2\Configuration as AccessTokenConfiguration;
use Bynder\Api\Impl\PermanentTokens\Configuration as PermanentTokenConfiguration;

interface BynderTokenConfigurationInterface
{
    public function getToken(): AccessTokenConfiguration|PermanentTokenConfiguration|null;
}
