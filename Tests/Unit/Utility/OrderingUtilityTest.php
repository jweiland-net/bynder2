<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/bynder2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Bynder2\Tests\Functional\Configuration;

use JWeiland\Bynder2\Utility\OrderingUtility;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/*
 * Test case.
 */
class OrderingUtilityTest extends UnitTestCase
{
    public function orderingDataProvider(): array
    {
        return [
            'Sort by file ASC will be mapped to name' => ['file', false, 'name asc'],
            'Sort by file DESC will be mapped to name' => ['file', true, 'name desc'],
            'Sort by empty column will fallback to dateModified' => ['', false, 'dateModified asc'],
            'Sort by size ASC will fallback to dateModified' => ['size', false, 'dateModified asc'],
            'Sort by size DESC will fallback to dateModified' => ['size', true, 'dateModified desc'],
            'Sort by tstamp ASC will fallback to dateModified' => ['tstamp', false, 'dateModified asc'],
            'Sort by tstamp DESC will fallback to dateModified' => ['tstamp', true, 'dateModified desc'],
            'Sort by fileext ASC will fallback to dateModified' => ['fileext', false, 'dateModified asc'],
            'Sort by fileext DESC will fallback to dateModified' => ['fileext', true, 'dateModified desc'],
        ];
    }

    /**
     * @test
     *
     * @dataProvider orderingDataProvider
     */
    public function getOrderingWillReturnOrdering(string $sort, bool $sortRev, string $expected): void
    {
        self::assertSame(
            $expected,
            OrderingUtility::getOrdering($sort, $sortRev)
        );
    }
}
