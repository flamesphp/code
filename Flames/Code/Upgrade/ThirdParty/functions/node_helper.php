<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node;
use Flames\Code\Upgrade\ThirdParty\PhpParser\PrettyPrinter\Standard;
use Flames\Code\Upgrade\Console\Style\SymfonyStyleFactory;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\FileNode;
use Flames\Code\Upgrade\ThirdParty\Util\NodePrinter;
use Flames\Code\Upgrade\ThirdParty\Util\Reflection\PrivatesAccessor;
use Flames\Code\Upgrade\ThirdParty\Symfony\Component\Console\Output\OutputInterface;
if (!\function_exists('print_node')) {
    /**
     * @param Node|Node[] $node
     */
    function print_node($node): void
    {
        $standard = new Standard();
        $nodes = \is_array($node) ? $node : [$node];
        if ($nodes[0] instanceof FileNode) {
            $nodes = $nodes[0]->stmts;
        }
        foreach ($nodes as $node) {
            $printedContent = $standard->prettyPrint([$node]);
            \var_dump($printedContent);
        }
    }
}
if (!\function_exists('dump_node')) {
    /**
     * @param Node|Node[] $node
     */
    function dump_node($node): void
    {
        $symfonyStyleFactory = new SymfonyStyleFactory(new PrivatesAccessor());
        $rectorStyle = $symfonyStyleFactory->create();
        // we turn up the verbosity so it's visible in tests overriding the
        // default which is to be quite during tests
        $rectorStyle->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
        $rectorStyle->newLine();
        $nodePrinter = new NodePrinter($rectorStyle);
        $nodePrinter->printNodes($node);
    }
}
