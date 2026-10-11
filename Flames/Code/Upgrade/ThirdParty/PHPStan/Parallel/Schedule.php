<?php

declare (strict_types=1);
namespace PHPStan\Parallel;

final class Schedule
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public int $numberOfProcesses;
    /**
     * @var array<array<string>>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $jobs;
    /**
     * @param array<array<string>> $jobs
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        int $numberOfProcesses,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $jobs
    )
    {
        $this->numberOfProcesses = $numberOfProcesses;
        $this->jobs = $jobs;
    }
    public function getNumberOfProcesses(): int
    {
        return $this->numberOfProcesses;
    }
    /**
     * @return array<array<string>>
     */
    public function getJobs(): array
    {
        return $this->jobs;
    }
}
