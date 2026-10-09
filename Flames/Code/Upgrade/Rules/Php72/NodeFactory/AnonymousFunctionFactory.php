<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Php72\NodeFactory;

use FlamesPrefix202610\Nette\Utils\Strings;
use PhpParser\Node;
use PhpParser\Node\ClosureUse;
use PhpParser\Node\ComplexType;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Stmt\Return_;
use PhpParser\Node\UnionType;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\Php\ReservedKeywordAnalyzer;
use Flames\Code\Upgrade\PhpDocParser\NodeTraverser\SimpleCallableNodeTraverser;
use Flames\Code\Upgrade\PhpParser\Node\BetterNodeFinder;
use Flames\Code\Upgrade\PhpParser\Parser\InlineCodeParser;
use Flames\Code\Upgrade\PhpParser\Parser\SimplePhpParser;
final readonly class AnonymousFunctionFactory
{
    /**
     * @see https://regex101.com/r/jkLLlM/2
     */
    private const string DIM_FETCH_REGEX = '#(\$|\\\\|\x0)(?<number>\d+)#';
    public function __construct(private NodeNameResolver $nodeNameResolver, private BetterNodeFinder $betterNodeFinder, private SimpleCallableNodeTraverser $simpleCallableNodeTraverser, private SimplePhpParser $simplePhpParser, private InlineCodeParser $inlineCodeParser, private ReservedKeywordAnalyzer $reservedKeywordAnalyzer)
    {
    }
    /**
     * @api
     * @param Param[] $params
     * @param Stmt[] $stmts
     * @param \PhpParser\Node\Identifier|\PhpParser\Node\Name|\PhpParser\Node\NullableType|\PhpParser\Node\UnionType|\PhpParser\Node\ComplexType|null $returnTypeNode
     */
    public function create(array $params, array $stmts, $returnTypeNode, bool $static = \false): Closure
    {
        $useVariables = $this->createUseVariablesFromParams($stmts, $params);
        $anonymousFunctionClosure = new Closure();
        $anonymousFunctionClosure->params = $params;
        if ($static) {
            $anonymousFunctionClosure->static = $static;
        }
        foreach ($useVariables as $useVariable) {
            $anonymousFunctionClosure->uses[] = new ClosureUse($useVariable);
        }
        if ($returnTypeNode instanceof Node) {
            $anonymousFunctionClosure->returnType = $returnTypeNode;
        }
        $anonymousFunctionClosure->stmts = $stmts;
        return $anonymousFunctionClosure;
    }
    public function createAnonymousFunctionFromExpr(Expr $expr): ?Closure
    {
        $stringValue = $this->inlineCodeParser->stringify($expr);
        $phpCode = '<?php ' . $stringValue . ';';
        $contentStmts = $this->simplePhpParser->parseString($phpCode);
        $anonymousFunction = new Closure();
        $firstNode = $contentStmts[0] ?? null;
        if (!$firstNode instanceof Expression) {
            return null;
        }
        $stmt = $firstNode->expr;
        $this->simpleCallableNodeTraverser->traverseNodesWithCallable($stmt, static function (Node $node): Node {
            if (!$node instanceof String_) {
                return $node;
            }
            $match = Strings::match($node->value, self::DIM_FETCH_REGEX);
            if ($match === null) {
                return $node;
            }
            $matchesVariable = new Variable('matches');
            return new ArrayDimFetch($matchesVariable, new Int_((int) $match['number']));
        });
        $anonymousFunction->stmts[] = new Return_($stmt);
        $anonymousFunction->params[] = new Param(new Variable('matches'));
        $variables = $expr instanceof Variable ? [] : $this->betterNodeFinder->findInstanceOf($expr, Variable::class);
        $anonymousFunction->uses = array_map(static fn(Variable $variable): ClosureUse => new ClosureUse($variable), $variables);
        return $anonymousFunction;
    }
    /**
     * @param Param[] $params
     * @return string[]
     */
    private function collectParamNames(array $params): array
    {
        $paramNames = [];
        foreach ($params as $param) {
            $paramNames[] = $this->nodeNameResolver->getName($param);
        }
        return $paramNames;
    }
    /**
     * @param Node[] $nodes
     * @param Param[] $params
     * @return array<string, Variable>
     */
    private function createUseVariablesFromParams(array $nodes, array $params): array
    {
        $paramNames = $this->collectParamNames($params);
        /** @var Variable[] $variables */
        $variables = $this->betterNodeFinder->findInstanceOf($nodes, Variable::class);
        /** @var array<string, Variable> $filteredVariables */
        $filteredVariables = [];
        $alreadyAssignedVariables = [];
        foreach ($variables as $variable) {
            // "$this" is allowed
            if ($this->nodeNameResolver->isName($variable, 'this')) {
                continue;
            }
            $variableName = $this->nodeNameResolver->getName($variable);
            if ($variableName === null) {
                continue;
            }
            if (in_array($variableName, $paramNames, \true)) {
                continue;
            }
            // Superglobal variables cannot be in a use statement
            if ($this->reservedKeywordAnalyzer->isNativeVariable($variableName)) {
                continue;
            }
            if ($variable->getAttribute(AttributeKey::IS_BEING_ASSIGNED) === \true || $variable->getAttribute(AttributeKey::IS_PARAM_VAR) === \true || $variable->getAttribute(AttributeKey::IS_VARIABLE_LOOP) === \true) {
                $alreadyAssignedVariables[] = $variableName;
            }
            if (!$this->nodeNameResolver->isNames($variable, $alreadyAssignedVariables)) {
                $filteredVariables[$variableName] = $variable;
            }
        }
        return $filteredVariables;
    }
}
