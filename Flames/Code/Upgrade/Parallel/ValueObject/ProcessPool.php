<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Parallel\ValueObject;

use FlamesPrefix202610\React\Socket\TcpServer;
use Flames\Code\Upgrade\Parallel\Exception\ParallelShouldNotHappenException;
/**
 * Used from https://github.com/phpstan/phpstan-src/blob/master/src/Parallel/ProcessPool.php
 */
final class ProcessPool
{
    /**
     * @var array<string, ParallelProcess>
     */
    private array $processes = [];
    public function __construct(private readonly TcpServer $tcpServer)
    {
    }
    public function getProcess(string $identifier): \Flames\Code\Upgrade\Parallel\ValueObject\ParallelProcess
    {
        if (!\array_key_exists($identifier, $this->processes)) {
            throw new ParallelShouldNotHappenException(\sprintf('Process "%s" not found.', $identifier));
        }
        return $this->processes[$identifier];
    }
    public function attachProcess(string $identifier, \Flames\Code\Upgrade\Parallel\ValueObject\ParallelProcess $parallelProcess): void
    {
        $this->processes[$identifier] = $parallelProcess;
    }
    public function tryQuitProcess(string $identifier): void
    {
        if (!\array_key_exists($identifier, $this->processes)) {
            return;
        }
        $this->quitProcess($identifier);
    }
    public function quitProcess(string $identifier): void
    {
        $parallelProcess = $this->getProcess($identifier);
        $parallelProcess->quit();
        unset($this->processes[$identifier]);
        if ($this->processes !== []) {
            return;
        }
        $this->tcpServer->close();
    }
    public function quitAll(): void
    {
        foreach (\array_keys($this->processes) as $identifier) {
            $this->quitProcess($identifier);
        }
    }
}
