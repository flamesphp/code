<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Symfony\ValueObject\ValidatorAssert;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\New_;
final readonly class ClassMethodAndConstraint
{
    /**
     * @param string[] $possibleMethodNames
     */
    public function __construct(private array $possibleMethodNames, private New_ $constraintNew)
    {
    }
    /**
     * @return string[]
     */
    public function getPossibleMethodNames(): array
    {
        return $this->possibleMethodNames;
    }
    public function getConstraintNew(): New_
    {
        return $this->constraintNew;
    }
}
