<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php80\NodeAnalyzer;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Param;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\ValueObject\MethodName;
final class PromotedPropertyResolver
{
    /**
     * @return Param[]
     */
    public function resolveFromClass(Class_ $class): array
    {
        $constructClassMethod = $class->getMethod(MethodName::CONSTRUCT);
        if (!$constructClassMethod instanceof ClassMethod) {
            return [];
        }
        $promotedPropertyParams = [];
        foreach ($constructClassMethod->getParams() as $param) {
            if (!$param->isPromoted()) {
                continue;
            }
            $promotedPropertyParams[] = $param;
        }
        return $promotedPropertyParams;
    }
}
