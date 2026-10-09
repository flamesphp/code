<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Console\Command;

use FlamesPrefix202610\Symfony\Component\Console\Command\Command;
use FlamesPrefix202610\Symfony\Component\Console\Input\InputInterface;
use FlamesPrefix202610\Symfony\Component\Console\Output\OutputInterface;
use FlamesPrefix202610\Symfony\Component\Console\Style\SymfonyStyle;
final class CustomRuleCommand extends Command
{
    public function __construct(private readonly SymfonyStyle $symfonyStyle)
    {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->setName('custom-rule');
        $this->setDescription('[DEPRECATED] Create base of local custom rule with tests');
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->symfonyStyle->error('The "custom-rule" command is deprecated and no longer generates files. Use an AI agent to scaffold a custom rule instead - it handles the setup faster and with less guesswork.');
        return Command::FAILURE;
    }
}
