<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Console\Command;

use FlamesPrefix202610\Symfony\Component\Console\Command\Command;
use FlamesPrefix202610\Symfony\Component\Console\Input\InputInterface;
use FlamesPrefix202610\Symfony\Component\Console\Output\OutputInterface;
use FlamesPrefix202610\Symfony\Component\Console\Style\SymfonyStyle;
final class SetupCICommand extends Command
{
    public function __construct(private readonly SymfonyStyle $symfonyStyle)
    {
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->setName('setup-ci');
        $this->setDescription('[DEPRECATED] Add CI workflow to let Upgrade work for you');
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->symfonyStyle->error('The "setup-ci" command is deprecated and no longer generates files. Its generic template lacked caching and did not fit most setups. Add a CI workflow explicitly for your use case instead - an AI agent can tailor one to your repository.');
        return Command::FAILURE;
    }
}
