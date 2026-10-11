<?php

declare (strict_types=1);
namespace PHPStan\Parallel;

use PHPStan\ShouldNotHappenException;
use Flames\Code\Upgrade\ThirdParty\React\EventLoop\LoopInterface;
use Flames\Code\Upgrade\ThirdParty\React\EventLoop\TimerInterface;
use Flames\Code\Upgrade\ThirdParty\React\Stream\ReadableStreamInterface;
use Flames\Code\Upgrade\ThirdParty\React\Stream\WritableStreamInterface;
use Throwable;
use function sprintf;
/**
 * Process-creation-agnostic half of a parallel worker: the TCP/NDJSON
 * connection plus the per-request timeout timer. Subclasses only implement
 * start() (how the worker process is created) and quit() (how it is torn down).
 */
abstract class ProcessBase implements \PHPStan\Parallel\Process
{
    protected LoopInterface $loop;
    protected float $timeoutSeconds;
    private ?WritableStreamInterface $in = null;
    /** @var callable(mixed[] $json) : void */
    private $onData;
    /** @var callable(Throwable $exception): void */
    private $onError;
    private ?TimerInterface $timer = null;
    public function __construct(LoopInterface $loop, float $timeoutSeconds)
    {
        $this->loop = $loop;
        $this->timeoutSeconds = $timeoutSeconds;
    }
    /**
     * @param callable(mixed[] $json) : void $onData
     * @param callable(Throwable $exception): void $onError
     */
    protected function setCallbacks(callable $onData, callable $onError): void
    {
        $this->onData = $onData;
        $this->onError = $onError;
    }
    protected function cancelTimer(): void
    {
        if ($this->timer === null) {
            return;
        }
        $this->loop->cancelTimer($this->timer);
        $this->timer = null;
    }
    /** Cancels the timeout timer and ends the writable side of the connection. */
    protected function endConnection(): void
    {
        $this->cancelTimer();
        if ($this->in === null) {
            return;
        }
        $this->in->end();
    }
    /**
     * @param mixed[] $data
     */
    public function request(array $data): void
    {
        $this->cancelTimer();
        if ($this->in === null) {
            throw new ShouldNotHappenException();
        }
        $this->in->write($data);
        $this->timer = $this->loop->addTimer($this->timeoutSeconds, function (): void {
            $onError = $this->onError;
            $onError(new \PHPStan\Parallel\ProcessTimedOutException(sprintf('Child process timed out after %.1f seconds. Try making it longer with parallel.processTimeout setting.', $this->timeoutSeconds)));
        });
    }
    public function bindConnection(ReadableStreamInterface $out, WritableStreamInterface $in): void
    {
        $out->on('data', function (array $json): void {
            $this->cancelTimer();
            if ($json['action'] !== 'result') {
                return;
            }
            $onData = $this->onData;
            $onData($json['result']);
        });
        $this->in = $in;
        $out->on('error', function (Throwable $error): void {
            $onError = $this->onError;
            $onError($error);
        });
        $in->on('error', function (Throwable $error): void {
            $onError = $this->onError;
            $onError($error);
        });
    }
}
