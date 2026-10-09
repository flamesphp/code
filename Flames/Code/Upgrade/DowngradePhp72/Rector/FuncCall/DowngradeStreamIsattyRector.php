<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DowngradePhp72\Rector\FuncCall;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Assign;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Closure;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\FuncCall;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Expr\Variable;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Echo_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Expression;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Return_;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Switch_;
use PHPStan\Analyser\Scope;
use Flames\Code\Upgrade\Exception\ShouldNotHappenException;
use Flames\Code\Upgrade\Rules\Naming\Naming\VariableNaming;
use Flames\Code\Upgrade\NodeAnalyzer\ExprInTopStmtMatcher;
use Flames\Code\Upgrade\NodeTypeResolver\Node\AttributeKey;
use Flames\Code\Upgrade\PhpParser\Enum\NodeGroup;
use Flames\Code\Upgrade\PhpParser\Parser\InlineCodeParser;
use Flames\Code\Upgrade\PHPStan\ScopeFetcher;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\CodeSample\CodeSample;
use Flames\Code\Upgrade\ThirdParty\Symplify\ValueObject\RuleDefinition;
/**
 * @changelog https://github.com/symfony/polyfill/commit/cc2bf55accd32b989348e2039e8c91cde46aebed
 *
 * @see \Flames\Code\Upgrade\DowngradePhp72\Rector\FuncCall\DowngradeStreamIsattyRectorTest
 */
final class DowngradeStreamIsattyRector extends AbstractRector
{
    private ?Closure $cachedClosure = null;
    public function __construct(private readonly InlineCodeParser $inlineCodeParser, private readonly VariableNaming $variableNaming, private readonly ExprInTopStmtMatcher $exprInTopStmtMatcher)
    {
    }
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Downgrade stream_isatty() function', [new CodeSample(<<<'CODE_SAMPLE'
class SomeClass
{
    public function run($stream)
    {
        $isStream = stream_isatty($stream);
    }
}
CODE_SAMPLE
, <<<'CODE_SAMPLE'
class SomeClass
{
    public function run($stream)
    {
        $streamIsatty = function ($stream) {
            if (\function_exists('stream_isatty')) {
                return stream_isatty($stream);
            }

            if (!\is_resource($stream)) {
                trigger_error('stream_isatty() expects parameter 1 to be resource, '.\gettype($stream).' given', \E_USER_WARNING);

                return false;
            }

            if ('\\' === \DIRECTORY_SEPARATOR) {
                $stat = @fstat($stream);
                // Check if formatted mode is S_IFCHR
                return $stat ? 0020000 === ($stat['mode'] & 0170000) : false;
            }

            return \function_exists('posix_isatty') && @posix_isatty($stream);
        };
        $isStream = $streamIsatty($stream);
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
        $stmtsAware = NodeGroup::STMTS_AWARE;
        return array_merge($stmtsAware, [Switch_::class, Return_::class, Expression::class, Echo_::class]);
    }
    /**
     * @param StmtsAware|Switch_|Return_|Expression|Echo_ $node
     * @return Node[]|null
     */
    public function refactor(Node $node): ?array
    {
        $expr = $this->exprInTopStmtMatcher->match($node, function (Node $subNode): bool {
            if (!$subNode instanceof FuncCall) {
                return \false;
            }
            if (!$this->isName($subNode, 'stream_isatty')) {
                return \false;
            }
            // need pull Scope from target traversed sub Node
            $scope = $subNode->getAttribute(AttributeKey::SCOPE);
            if (!$scope instanceof Scope) {
                return \false;
            }
            return !$scope->isInFunctionExists('stream_isatty');
        });
        if (!$expr instanceof FuncCall) {
            return null;
        }
        $function = $this->createClosure();
        $scope = ScopeFetcher::fetch($node);
        $variable = new Variable($this->variableNaming->createCountedValueName('streamIsatty', $scope));
        $assign = new Assign($variable, $function);
        $expr->name = $variable;
        return [new Expression($assign), $node];
    }
    private function createClosure(): Closure
    {
        if ($this->cachedClosure instanceof Closure) {
            return clone $this->cachedClosure;
        }
        $stmts = $this->inlineCodeParser->parseFile(__DIR__ . '/../../snippet/isatty_closure.php.inc');
        /** @var Expression $expression */
        $expression = $stmts[0];
        $expr = $expression->expr;
        if (!$expr instanceof Closure) {
            throw new ShouldNotHappenException();
        }
        $this->cachedClosure = $expr;
        return $expr;
    }
}
