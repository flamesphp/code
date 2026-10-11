<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Class_;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\UnionType;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\Enum\TypeKind;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\StaticTypeMapper\StaticTypeMapper;
use Flames\Code\Upgrade\Rules\TypeDeclaration\Guard\ParamTypeAddGuard;
use Flames\Code\Upgrade\Rules\TypeDeclarationDocblocks\NodeFinder\ArrayDimFetchFinder;
use Flames\Code\Upgrade\VendorLocker\ParentClassMethodTypeOverrideGuard;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @see \Flames\Code\Upgrade\Rules\TypeDeclaration\Rector\ClassMethod\AddParamFromDimFetchKeyUseRectorTest
 */
final class AddParamFromDimFetchKeyUseRector extends AbstractRector
{
    public function __construct(private readonly ArrayDimFetchFinder $arrayDimFetchFinder, private readonly StaticTypeMapper $staticTypeMapper, private readonly ParentClassMethodTypeOverrideGuard $parentClassMethodTypeOverrideGuard, private readonly ParamTypeAddGuard $paramTypeAddGuard)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Add method param type based on use in array dim fetch of known keys', [new CodeSample(<<<'CODE_SAMPLE'
final class SomeClass
{
    public function get($key)
    {
        $data = [
            'name' => 'John',
            'age' => 30,
        ];

        return $data[$key];
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
final class SomeClass
{
    public function get(string $key)
    {
        $data = [
            'name' => 'John',
            'age' => 30,
        ];

        return $data[$key];
    }
}
CODE_SAMPLE
)]);
    }
    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }
    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        $hasChanged = \false;
        foreach ($node->getMethods() as $classMethod) {
            if ($classMethod->params === []) {
                continue;
            }
            if ($this->parentClassMethodTypeOverrideGuard->hasParentClassMethod($classMethod)) {
                continue;
            }
            foreach ($classMethod->getParams() as $param) {
                if ($param->type instanceof Node) {
                    continue;
                }
                $paramName = $this->getName($param);
                $dimFetches = $this->arrayDimFetchFinder->findByDimName($classMethod, $paramName);
                if ($dimFetches === []) {
                    continue;
                }
                if (!$this->paramTypeAddGuard->isLegal($param, $classMethod)) {
                    continue;
                }
                foreach ($dimFetches as $dimFetch) {
                    $dimFetchType = $this->getType($dimFetch->var);
                    if (!$dimFetchType instanceof ArrayType && !$dimFetchType instanceof ConstantArrayType) {
                        continue 2;
                    }
                    if ($dimFetch->dim instanceof Variable) {
                        $type = $this->nodeTypeResolver->getType($dimFetch->dim);
                        if ($type instanceof UnionType) {
                            continue 2;
                        }
                    }
                }
                $paramTypeNode = $this->staticTypeMapper->mapPHPStanTypeToPhpParserNode($dimFetchType->getKeyType(), TypeKind::PARAM);
                if (!$paramTypeNode instanceof Node) {
                    continue;
                }
                $param->type = $paramTypeNode;
                $hasChanged = \true;
            }
        }
        if ($hasChanged) {
            return $node;
        }
        return null;
    }
}
