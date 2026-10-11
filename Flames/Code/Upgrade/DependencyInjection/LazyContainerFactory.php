<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\DependencyInjection;

use Flames\Code\Upgrade\ThirdParty\Doctrine\Inflector;
use Flames\Code\Upgrade\ThirdParty\Doctrine\Rules\English\InflectorFactory;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Lexer;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\Parser\Parser;
use PHPStan\Php\PhpVersionFactory;
use PHPStan\PhpDoc\TypeNodeResolver;
use Flames\Code\Upgrade\ThirdParty\PHPStan\ParserConfig;
use PHPStan\Reflection\ReflectionProvider;
use Flames\Code\Upgrade\Application\ChangedNodeScopeRefresher;
use Flames\Code\Upgrade\Application\FileProcessor;
use Flames\Code\Upgrade\Application\Provider\CurrentFileProvider;
use Flames\Code\Upgrade\BetterPhpDocParser\Comment\CommentsMerger;
use Flames\Code\Upgrade\BetterPhpDocParser\Contract\BasePhpDocNodeVisitorInterface;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocNodeVisitor\ArrayTypePhpDocNodeVisitor;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocNodeVisitor\CallableTypePhpDocNodeVisitor;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocNodeVisitor\IntersectionTypeNodePhpDocNodeVisitor;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocNodeVisitor\TemplatePhpDocNodeVisitor;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocNodeVisitor\UnionTypeNodePhpDocNodeVisitor;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocParser\StaticDoctrineAnnotationParser;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocParser\StaticDoctrineAnnotationParser\ArrayParser;
use Flames\Code\Upgrade\BetterPhpDocParser\PhpDocParser\StaticDoctrineAnnotationParser\PlainValueParser;
use Flames\Code\Upgrade\Caching\Cache;
use Flames\Code\Upgrade\Caching\CacheFactory;
use Flames\Code\Upgrade\Config\UpgradeConfig;
use Flames\Code\Upgrade\Configuration\ConfigurationRuleFilter;
use Flames\Code\Upgrade\Configuration\RenamedClassesDataCollector;
use Flames\Code\Upgrade\Console\Command\ComposerBasedCommand;
use Flames\Code\Upgrade\Console\Command\CustomRuleCommand;
use Flames\Code\Upgrade\Console\Command\ListRulesCommand;
use Flames\Code\Upgrade\Console\Command\ProcessCommand;
use Flames\Code\Upgrade\Console\Command\SetupCICommand;
use Flames\Code\Upgrade\Console\Command\ValidateConfigCommand;
use Flames\Code\Upgrade\Console\Command\WorkerCommand;
use Flames\Code\Upgrade\Console\ConsoleApplication;
use Flames\Code\Upgrade\Console\Style\SymfonyStyleFactory;
use Flames\Code\Upgrade\Contract\PhpParser\DecoratingNodeVisitorInterface;
use Flames\Code\Upgrade\NodeDecorator\CreatedByRuleDecorator;
use Flames\Code\Upgrade\NodeNameResolver\NodeNameResolver;
use Flames\Code\Upgrade\NodeTypeResolver\DependencyInjection\PHPStanServicesFactory;
use Flames\Code\Upgrade\NodeTypeResolver\NodeTypeResolver;
use Flames\Code\Upgrade\NodeTypeResolver\Reflection\BetterReflection\SourceLocatorProvider\DynamicSourceLocatorProvider;
use Flames\Code\Upgrade\PhpAttribute\AnnotationToAttributeMapper;
use Flames\Code\Upgrade\PhpAttribute\AnnotationToAttributeMapper\ArrayAnnotationToAttributeMapper;
use Flames\Code\Upgrade\PhpAttribute\AnnotationToAttributeMapper\ArrayItemNodeAnnotationToAttributeMapper;
use Flames\Code\Upgrade\PhpAttribute\AnnotationToAttributeMapper\CurlyListNodeAnnotationToAttributeMapper;
use Flames\Code\Upgrade\PhpAttribute\AnnotationToAttributeMapper\DoctrineAnnotationAnnotationToAttributeMapper;
use Flames\Code\Upgrade\PhpDocParser\NodeTraverser\SimpleCallableNodeTraverser;
use Flames\Code\Upgrade\PhpParser\Comparing\NodeComparator;
use Flames\Code\Upgrade\PhpParser\Node\NodeFactory;
use Flames\Code\Upgrade\PhpParser\NodeVisitor\AssignedToNodeVisitor;
use Flames\Code\Upgrade\PhpParser\NodeVisitor\ByRefNodeVisitor;
use Flames\Code\Upgrade\PhpParser\NodeVisitor\CallLikeReflectionNodeVisitor;
use Flames\Code\Upgrade\PhpParser\NodeVisitor\ContextNodeVisitor;
use Flames\Code\Upgrade\PhpParser\NodeVisitor\DefaultValueNodeVisitor;
use Flames\Code\Upgrade\PhpParser\NodeVisitor\LocalVariableScopeNodeVisitor;
use Flames\Code\Upgrade\PhpParser\NodeVisitor\NameAndArgNodeVisitor;
use Flames\Code\Upgrade\PhpParser\NodeVisitor\PhpVersionConditionNodeVisitor;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\PHPStanStaticTypeMapper;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\TypeMapper\ArrayTypeMapper;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\TypeMapper\ConditionalTypeForParameterMapper;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\TypeMapper\ConditionalTypeMapper;
use Flames\Code\Upgrade\PHPStanStaticTypeMapper\TypeMapper\UnionTypeMapper;
use Flames\Code\Upgrade\PostRector\Application\PostFileProcessor;
use Flames\Code\Upgrade\Rector\AbstractRector;
use Flames\Code\Upgrade\Skipper\Skipper\Skipper;
use Flames\Code\Upgrade\Skipper\Skipper\UsedSkipCollector;
use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console\Application;
use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console\Style\SymfonyStyle;
use Flames\Code\Upgrade\ThirdParty\Webmozart\Assert\Assert;
final class LazyContainerFactory
{
    /**
     * @var array<class-string<BasePhpDocNodeVisitorInterface>>
     */
    private const array BASE_PHP_DOC_NODE_VISITORS = [ArrayTypePhpDocNodeVisitor::class, CallableTypePhpDocNodeVisitor::class, IntersectionTypeNodePhpDocNodeVisitor::class, TemplatePhpDocNodeVisitor::class, UnionTypeNodePhpDocNodeVisitor::class];
    /**
     * @var array<class-string<DecoratingNodeVisitorInterface>>
     */
    private const array DECORATING_NODE_VISITOR_CLASSES = [CallLikeReflectionNodeVisitor::class, PhpVersionConditionNodeVisitor::class, AssignedToNodeVisitor::class, ByRefNodeVisitor::class, ContextNodeVisitor::class, LocalVariableScopeNodeVisitor::class, NameAndArgNodeVisitor::class, DefaultValueNodeVisitor::class];
    /**
     * @var array<class-string>
     */
    private const array PUBLIC_PHPSTAN_SERVICE_TYPES = [ScopeFactory::class, TypeNodeResolver::class, NodeScopeResolver::class, ReflectionProvider::class, PhpVersionFactory::class];
    /**
     * @api used as next rectorConfig factory
     */
    public function create(): UpgradeConfig
    {
        $rectorConfig = new UpgradeConfig();
        $rectorConfig->import(__DIR__ . '/../../../../resources/upgrade/config/config.php');
        $this->registerConsole($rectorConfig);
        $this->registerFileProcessing($rectorConfig);
        $this->registerCachingAndResettables($rectorConfig);
        $this->registerTypeMappers($rectorConfig);
        $this->registerNodeNameResolvers($rectorConfig);
        $this->registerRectorAutowiring($rectorConfig);
        $this->registerTaggedServices($rectorConfig);
        $this->registerAnnotationToAttributeSetters($rectorConfig);
        $this->registerNodeVisitorsAndPhpDoc($rectorConfig);
        return $rectorConfig;
    }
    private function registerConsole(UpgradeConfig $rectorConfig): void
    {
        $rectorConfig->singleton(Application::class, static function (UpgradeConfig $rectorConfig): Application {
            $consoleApplication = $rectorConfig->make(ConsoleApplication::class);
            $commandNamesToHide = ['list', 'completion', 'help', 'worker'];
            foreach ($commandNamesToHide as $commandNameToHide) {
                $commandToHide = $consoleApplication->get($commandNameToHide);
                $commandToHide->setHidden();
            }
            return $consoleApplication;
        });
        $rectorConfig->singleton(Inflector::class, static function (): Inflector {
            $inflectorFactory = new InflectorFactory();
            return $inflectorFactory->build();
        });
        $rectorConfig->singleton(ConfigurationRuleFilter::class);
        $rectorConfig->singleton(ProcessCommand::class);
        $rectorConfig->singleton(WorkerCommand::class);
        $rectorConfig->singleton(ValidateConfigCommand::class);
        $rectorConfig->singleton(SetupCICommand::class);
        $rectorConfig->singleton(ListRulesCommand::class);
        $rectorConfig->singleton(CustomRuleCommand::class);
        $rectorConfig->singleton(ComposerBasedCommand::class);
    }
    private function registerFileProcessing(UpgradeConfig $rectorConfig): void
    {
        $rectorConfig->singleton(FileProcessor::class);
        $rectorConfig->singleton(PostFileProcessor::class);
        // shared state: collects used skips across the skipper, the path skipper and the file processor
        $rectorConfig->singleton(UsedSkipCollector::class);
        $rectorConfig->singleton(DynamicSourceLocatorProvider::class, static function (UpgradeConfig $rectorConfig): DynamicSourceLocatorProvider {
            $phpStanServicesFactory = $rectorConfig->make(PHPStanServicesFactory::class);
            return $phpStanServicesFactory->createDynamicSourceLocatorProvider();
        });
    }
    private function registerCachingAndResettables(UpgradeConfig $rectorConfig): void
    {
        // resettable: registering the class makes it discoverable via findByContract(ResettableInterface)
        $rectorConfig->singleton(RenamedClassesDataCollector::class);
        // caching
        $rectorConfig->singleton(Cache::class, static function (UpgradeConfig $rectorConfig): Cache {
            /** @var CacheFactory $cacheFactory */
            $cacheFactory = $rectorConfig->make(CacheFactory::class);
            return $cacheFactory->create();
        });
    }
    private function registerTypeMappers(UpgradeConfig $rectorConfig): void
    {
        // tagged services
        $rectorConfig->afterResolving(ArrayTypeMapper::class, static function (ArrayTypeMapper $arrayTypeMapper) use ($rectorConfig): void {
            $arrayTypeMapper->autowire($rectorConfig->make(PHPStanStaticTypeMapper::class));
        });
        $rectorConfig->afterResolving(ConditionalTypeForParameterMapper::class, static function (ConditionalTypeForParameterMapper $conditionalTypeForParameterMapper) use ($rectorConfig): void {
            $phpStanStaticTypeMapper = $rectorConfig->make(PHPStanStaticTypeMapper::class);
            $conditionalTypeForParameterMapper->autowire($phpStanStaticTypeMapper);
        });
        $rectorConfig->afterResolving(ConditionalTypeMapper::class, static function (ConditionalTypeMapper $conditionalTypeMapper) use ($rectorConfig): void {
            $phpStanStaticTypeMapper = $rectorConfig->make(PHPStanStaticTypeMapper::class);
            $conditionalTypeMapper->autowire($phpStanStaticTypeMapper);
        });
        $rectorConfig->afterResolving(UnionTypeMapper::class, static function (UnionTypeMapper $unionTypeMapper) use ($rectorConfig): void {
            $phpStanStaticTypeMapper = $rectorConfig->make(PHPStanStaticTypeMapper::class);
            $unionTypeMapper->autowire($phpStanStaticTypeMapper);
        });
    }
    private function registerNodeNameResolvers(UpgradeConfig $rectorConfig): void
    {
        // node name resolvers
        $rectorConfig->autodiscover(__DIR__ . '/../Rules/Php80/AttributeDecorator');
    }
    private function registerRectorAutowiring(UpgradeConfig $rectorConfig): void
    {
        $rectorConfig->afterResolving(AbstractRector::class, static function (AbstractRector $rector) use ($rectorConfig): void {
            $rector->autowire($rectorConfig->get(NodeNameResolver::class), $rectorConfig->get(NodeTypeResolver::class), $rectorConfig->get(SimpleCallableNodeTraverser::class), $rectorConfig->get(NodeFactory::class), $rectorConfig->get(Skipper::class), $rectorConfig->get(NodeComparator::class), $rectorConfig->get(CurrentFileProvider::class), $rectorConfig->get(CreatedByRuleDecorator::class), $rectorConfig->get(ChangedNodeScopeRefresher::class), $rectorConfig->get(CommentsMerger::class));
        });
        $rectorConfig->autodiscover(__DIR__ . '/../StaticTypeMapper/PhpParser');
        $this->registerTagged($rectorConfig, self::BASE_PHP_DOC_NODE_VISITORS, BasePhpDocNodeVisitorInterface::class);
    }
    private function registerTaggedServices(UpgradeConfig $rectorConfig): void
    {
        // PHP 8.0 attributes
        $rectorConfig->autodiscover(__DIR__ . '/../PhpAttribute/AnnotationToAttributeMapper');
        $rectorConfig->autodiscover(__DIR__ . '/../PHPStanStaticTypeMapper/TypeMapper');
        $rectorConfig->autodiscover(__DIR__ . '/../StaticTypeMapper/PhpDocParser');
        $rectorConfig->autodiscover(__DIR__ . '/../NodeNameResolver/NodeNameResolver');
        $rectorConfig->autodiscover(__DIR__ . '/../NodeTypeResolver/NodeTypeResolver');
        $rectorConfig->autodiscover(__DIR__ . '/../ChangesReporting/Output');
        $rectorConfig->autodiscover(__DIR__ . '/../Rules/CodingStyle/ClassNameImport/ClassNameImportSkipVoter');
        $rectorConfig->singleton(SymfonyStyle::class, static function (UpgradeConfig $rectorConfig): SymfonyStyle {
            $symfonyStyleFactory = $rectorConfig->make(SymfonyStyleFactory::class);
            return $symfonyStyleFactory->create();
        });
    }
    private function registerAnnotationToAttributeSetters(UpgradeConfig $rectorConfig): void
    {
        // required-like setter
        $rectorConfig->afterResolving(ArrayAnnotationToAttributeMapper::class, static function (ArrayAnnotationToAttributeMapper $arrayAnnotationToAttributeMapper) use ($rectorConfig): void {
            $annotationToAttributeMapper = $rectorConfig->make(AnnotationToAttributeMapper::class);
            $arrayAnnotationToAttributeMapper->autowire($annotationToAttributeMapper);
        });
        $rectorConfig->afterResolving(ArrayItemNodeAnnotationToAttributeMapper::class, static function (ArrayItemNodeAnnotationToAttributeMapper $arrayItemNodeAnnotationToAttributeMapper) use ($rectorConfig): void {
            $annotationToAttributeMapper = $rectorConfig->make(AnnotationToAttributeMapper::class);
            $arrayItemNodeAnnotationToAttributeMapper->autowire($annotationToAttributeMapper);
        });
        $rectorConfig->afterResolving(PlainValueParser::class, static function (PlainValueParser $plainValueParser) use ($rectorConfig): void {
            $plainValueParser->autowire($rectorConfig->make(StaticDoctrineAnnotationParser::class), $rectorConfig->make(ArrayParser::class));
        });
        $rectorConfig->afterResolving(CurlyListNodeAnnotationToAttributeMapper::class, static function (CurlyListNodeAnnotationToAttributeMapper $curlyListNodeAnnotationToAttributeMapper) use ($rectorConfig): void {
            $annotationToAttributeMapper = $rectorConfig->make(AnnotationToAttributeMapper::class);
            $curlyListNodeAnnotationToAttributeMapper->autowire($annotationToAttributeMapper);
        });
        $rectorConfig->afterResolving(DoctrineAnnotationAnnotationToAttributeMapper::class, static function (DoctrineAnnotationAnnotationToAttributeMapper $doctrineAnnotationAnnotationToAttributeMapper) use ($rectorConfig): void {
            $annotationToAttributeMapper = $rectorConfig->make(AnnotationToAttributeMapper::class);
            $doctrineAnnotationAnnotationToAttributeMapper->autowire($annotationToAttributeMapper);
        });
    }
    private function registerNodeVisitorsAndPhpDoc(UpgradeConfig $rectorConfig): void
    {
        $this->registerTagged($rectorConfig, self::DECORATING_NODE_VISITOR_CLASSES, DecoratingNodeVisitorInterface::class);
        $this->createPHPStanServices($rectorConfig);
        // phpdoc-parser
        $rectorConfig->singleton(ParserConfig::class, static fn(UpgradeConfig $rectorConfig): ParserConfig => new ParserConfig(['lines' => \true, 'indexes' => \true, 'comments' => \true]));
    }
    /**
     * @param array<class-string> $classes
     * @param class-string $tagInterface
     */
    private function registerTagged(UpgradeConfig $rectorConfig, array $classes, string $tagInterface): void
    {
        foreach ($classes as $class) {
            Assert::isAOf($class, $tagInterface);
            $rectorConfig->singleton($class);
        }
    }
    private function createPHPStanServices(UpgradeConfig $rectorConfig): void
    {
        $rectorConfig->singleton(Parser::class, static function (UpgradeConfig $rectorConfig) {
            $phpStanServicesFactory = $rectorConfig->make(PHPStanServicesFactory::class);
            return $phpStanServicesFactory->createPHPStanParser();
        });
        $rectorConfig->singleton(Lexer::class, static function (UpgradeConfig $rectorConfig) {
            $phpStanServicesFactory = $rectorConfig->make(PHPStanServicesFactory::class);
            return $phpStanServicesFactory->createEmulativeLexer();
        });
        foreach (self::PUBLIC_PHPSTAN_SERVICE_TYPES as $publicPhpstanServiceType) {
            $rectorConfig->singleton($publicPhpstanServiceType, static function (UpgradeConfig $rectorConfig) use ($publicPhpstanServiceType) {
                $phpStanServicesFactory = $rectorConfig->make(PHPStanServicesFactory::class);
                return $phpStanServicesFactory->getByType($publicPhpstanServiceType);
            });
        }
    }
}
