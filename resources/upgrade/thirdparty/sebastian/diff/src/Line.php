<?php

declare (strict_types=1);
/*
 * This file is part of sebastian/diff.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace FlamesPrefix202610\SebastianBergmann\Diff;

final readonly class Line
{
    public const int ADDED = 1;
    public const int REMOVED = 2;
    public const int UNCHANGED = 3;
    public function __construct(private int $type = self::UNCHANGED, private string $content = '')
    {
    }
    public function content(): string
    {
        return $this->content;
    }
    public function type(): int
    {
        return $this->type;
    }
    public function isAdded(): bool
    {
        return $this->type === self::ADDED;
    }
    public function isRemoved(): bool
    {
        return $this->type === self::REMOVED;
    }
    public function isUnchanged(): bool
    {
        return $this->type === self::UNCHANGED;
    }
}
