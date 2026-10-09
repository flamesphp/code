<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Console\Command;

use FlamesPrefix202610\Nette\Utils\Json;
use Flames\Code\Upgrade\ChangesReporting\Output\ConsoleOutputFormatter;
use Flames\Code\Upgrade\Configuration\Option;
use Flames\Code\Upgrade\Contract\Rector\RectorInterface;
use Flames\Code\Upgrade\PostRector\Rector\PostRectorInterface;
use Flames\Code\Upgrade\Skipper\SkipCriteriaResolver\SkippedClassResolver;
use FlamesPrefix202610\Symfony\Component\Console\Command\Command;
use FlamesPrefix202610\Symfony\Component\Console\Input\InputInterface;
use FlamesPrefix202610\Symfony\Component\Console\Input\InputOption;
use FlamesPrefix202610\Symfony\Component\Console\Output\OutputInterface;
use FlamesPrefix202610\Symfony\Component\Console\Style\SymfonyStyle;
final class ListRulesCommand extends Command
{
    /**
     * @param RectorInterface[] $rectors
     */
    public function __construct(private readonly SymfonyStyle $symfonyStyle, private readonly SkippedClassResolver $skippedClassResolver, private readonly array $rectors)
    {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->setName('list-rules');
        $this->setDescription('Show loaded Rectors');
        $this->setAliases(['show-rules']);
        $this->addOption(Option::OUTPUT_FORMAT, null, InputOption::VALUE_REQUIRED, 'Select output format', ConsoleOutputFormatter::NAME);
        $this->addOption(Option::ONLY, null, InputOption::VALUE_REQUIRED, 'Fully qualified rule class name');
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $rectorClasses = $this->resolveRectorClasses();
        $skippedClasses = $this->getSkippedRectorClasses();
        $outputFormat = $input->getOption(Option::OUTPUT_FORMAT);
        if ($outputFormat === 'json') {
            $data = ['rectors' => $rectorClasses, 'skipped-rectors' => $skippedClasses];
            echo Json::encode($data, \true) . \PHP_EOL;
            return Command::SUCCESS;
        }
        $this->symfonyStyle->title('Loaded Upgrade rules');
        $this->symfonyStyle->listing($rectorClasses);
        if ($skippedClasses !== []) {
            $this->symfonyStyle->title('Skipped Upgrade rules');
            $this->symfonyStyle->listing($skippedClasses);
        }
        $this->symfonyStyle->newLine();
        $this->symfonyStyle->note(sprintf('Loaded %d rules', count($rectorClasses)));
        return Command::SUCCESS;
    }
    /**
     * @return array<class-string<RectorInterface>>
     */
    private function resolveRectorClasses(): array
    {
        $customRectors = array_filter($this->rectors, static fn(RectorInterface $rector): bool => !$rector instanceof PostRectorInterface);
        $rectorClasses = array_map(get_class(...), $customRectors);
        sort($rectorClasses);
        return array_unique($rectorClasses);
    }
    /**
     * @return array<class-string>
     */
    private function getSkippedRectorClasses(): array
    {
        $skippedRectorClasses = [];
        foreach ($this->skippedClassResolver->resolve() as $rectorClass => $fileList) {
            // ignore specific skips
            if ($fileList !== null) {
                continue;
            }
            $skippedRectorClasses[] = $rectorClass;
        }
        return $skippedRectorClasses;
    }
}
