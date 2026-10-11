<?php

declare (strict_types=1);
namespace PHPStan\Command\Bisect;

/**
 * @template T
 */
final class BinarySearchStep
{
    /**
     * @readonly
     * @var T
     */
    public $item;
    /**
     * @readonly
     * @var list<T>
     */
    public array $ifGood;
    /**
     * @readonly
     * @var list<T>
     */
    public array $ifBad;
    /**
     * @readonly
     */
    public int $stepsRemaining;
    /**
     * @param T $item Item to test
     * @param list<T> $ifGood Remaining items to search if this item is good
     * @param list<T> $ifBad Remaining items to search if this item is bad
     */
    public function __construct($item, array $ifGood, array $ifBad, int $stepsRemaining)
    {
        $this->item = $item;
        $this->ifGood = $ifGood;
        $this->ifBad = $ifBad;
        $this->stepsRemaining = $stepsRemaining;
    }
}
