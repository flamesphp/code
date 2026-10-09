<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php80\ValueObject;

use PhpParser\Comment;
use PhpParser\Node\Expr;
use Flames\Code\Upgrade\Rules\Php80\Enum\MatchKind;
final readonly class CondAndExpr
{
    /**
     * @param Expr[]|null $condExprs
     * @param MatchKind::* $matchKind
     * @param Comment[] $comments
     */
    public function __construct(
        /**
         * @readonly
         */
        private ?array $condExprs,
        private Expr $expr,
        private string $matchKind,
        /**
         * @readonly
         */
        private array $comments = []
    )
    {
    }
    public function getExpr(): Expr
    {
        return $this->expr;
    }
    /**
     * @return Expr[]|null
     */
    public function getCondExprs(): ?array
    {
        // internally checked by PHPStan, cannot be empty array
        if ($this->condExprs === []) {
            return null;
        }
        if ($this->condExprs === null) {
            return null;
        }
        return array_values($this->condExprs);
    }
    /**
     * @return MatchKind::*
     */
    public function getMatchKind(): string
    {
        return $this->matchKind;
    }
    /**
     * @param MatchKind::* $matchKind
     */
    public function equalsMatchKind(string $matchKind): bool
    {
        return $this->matchKind === $matchKind;
    }
    /**
     * @return Comment[]
     */
    public function getComments(): array
    {
        return $this->comments;
    }
}
