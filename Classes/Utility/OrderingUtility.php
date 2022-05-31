<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/bynder2.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Bynder2\Utility;

/*
 * Bynder can just order by three columns, so we have to map all TYPO3 columns to the allowed columns of Bynder
 */
class OrderingUtility
{
    public static function getOrdering(string $sort = '', bool $sortRev = false): string
    {
        return sprintf(
            '%s %s',
            self::getColumnToOrderBy($sort),
            self::getOrderingDirection($sortRev)
        );
    }

    /**
     * Bynder can not sort by size, tstamp, fileext and rw. So, use dateModified instead
     */
    protected static function getColumnToOrderBy(string $sort): string
    {
        return $sort === 'file' ? 'name' : 'dateModified';
    }

    protected static function getOrderingDirection(bool $sortRev): string
    {
        return $sortRev ? 'desc' : 'asc';
    }
}
