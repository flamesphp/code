<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\PHPUnit\NodeAnalyzer;

use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\ClassMethod;
use Flames\Code\Upgrade\PhpParser\AstResolver;
use Flames\Code\Upgrade\Testing\PHPUnit\StaticPHPUnitEnvironment;
use Flames\Code\Upgrade\ValueObject\MethodName;
/**
 * Decorate setUp() and tearDown() with "void" when local TestClass class uses them
 */
final readonly class SetUpMethodDecorator
{
    public function __construct(private AstResolver $astResolver)
    {
    }
    public function decorate(ClassMethod $classMethod): void
    {
        // skip test run
        if (StaticPHPUnitEnvironment::isPHPUnitRun()) {
            return;
        }
        $setUpClassMethod = $this->astResolver->resolveClassMethod(\PHPUnit\Framework\TestCase::class, MethodName::SET_UP);
        if (!$setUpClassMethod instanceof ClassMethod) {
            return;
        }
        if ($setUpClassMethod->returnType instanceof Identifier) {
            $classMethod->returnType = new Identifier($setUpClassMethod->returnType->toString());
            return;
        }
        $classMethod->returnType = null;
    }
}
