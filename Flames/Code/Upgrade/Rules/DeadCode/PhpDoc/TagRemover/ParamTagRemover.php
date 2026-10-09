<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\DeadCode\PhpDoc\TagRemover;

use PhpParser\Node\FunctionLike;
use PHPStan\PhpDocParser\Ast\Node;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\Type\Type;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Flames\Code\Upgrade\Comments\NodeDocBlock\DocBlockUpdater;
use Flames\Code\Upgrade\Rules\DeadCode\PhpDoc\DeadParamTagValueNodeAnalyzer;
use Flames\Code\Upgrade\PhpDocParser\PhpDocParser\PhpDocNodeTraverser;
final readonly class ParamTagRemover
{
    public function __construct(private DeadParamTagValueNodeAnalyzer $deadParamTagValueNodeAnalyzer, private DocBlockUpdater $docBlockUpdater)
    {
    }
    public function removeParamTagsIfUseless(PhpDocInfo $phpDocInfo, FunctionLike $functionLike, ?Type $type = null): bool
    {
        $hasChanged = \false;
        $phpDocNodeTraverser = new PhpDocNodeTraverser();
        $phpDocNodeTraverser->traverseWithCallable($phpDocInfo->getPhpDocNode(), '', function (Node $docNode) use ($functionLike, &$hasChanged, $type, $phpDocInfo): ?int {
            if (!$docNode instanceof PhpDocTagNode) {
                return null;
            }
            if (!$docNode->value instanceof ParamTagValueNode) {
                return null;
            }
            // handle only basic types, keep phpstan/psalm helper ones
            if ($docNode->name !== '@param') {
                return null;
            }
            if ($type instanceof Type) {
                $paramType = $phpDocInfo->getParamType($docNode->value->parameterName);
                if (!$type->equals($paramType)) {
                    return null;
                }
            }
            if (!$this->deadParamTagValueNodeAnalyzer->isDead($docNode->value, $functionLike)) {
                return null;
            }
            $hasChanged = \true;
            return PhpDocNodeTraverser::NODE_REMOVE;
        });
        if ($hasChanged) {
            $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($functionLike);
        }
        return $hasChanged;
    }
}
