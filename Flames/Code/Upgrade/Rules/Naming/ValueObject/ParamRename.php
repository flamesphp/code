<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Naming\ValueObject;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\FunctionLike;
final readonly class ParamRename
{
    public function __construct(private string $currentName, private string $expectedName, private Variable $variable, private FunctionLike $functionLike)
    {
    }
    public function getCurrentName(): string
    {
        return $this->currentName;
    }
    public function getExpectedName(): string
    {
        return $this->expectedName;
    }
    public function getFunctionLike(): FunctionLike
    {
        return $this->functionLike;
    }
    public function getVariable(): Variable
    {
        return $this->variable;
    }
}
