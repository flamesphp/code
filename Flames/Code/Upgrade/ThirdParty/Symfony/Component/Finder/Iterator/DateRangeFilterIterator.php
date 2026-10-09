<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Flames\Code\Upgrade\ThirdParty\Symfony\Component\Finder\Iterator;

use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Finder\Comparator\DateComparator;
/**
 * DateRangeFilterIterator filters out files that are not in the given date range (last modified dates).
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @extends \FilterIterator<string, \SplFileInfo>
 */
class DateRangeFilterIterator extends \FilterIterator
{
    /**
     * @param \Iterator<string, \SplFileInfo> $iterator
     * @param DateComparator[]                $comparators
     */
    public function __construct(\Iterator $iterator, private readonly array $comparators)
    {
        parent::__construct($iterator);
    }
    /**
     * Filters the iterator values.
     */
    public function accept(): bool
    {
        $fileinfo = $this->current();
        if (!file_exists($fileinfo->getPathname())) {
            return \false;
        }
        $filedate = $fileinfo->getMTime();
        return array_all($this->comparators, fn($compare) => $compare->test($filedate));
    }
}
