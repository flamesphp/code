<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

final class FinalizerResult
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public \PHPStan\Analyser\AnalyserResult $analyserResult;
    /**
     * @var list<Error>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $collectorErrors;
    /**
     * @var list<Error>
     */
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public array $locallyIgnoredCollectorErrors;
    /**
     * @param list<Error> $collectorErrors
     * @param list<Error> $locallyIgnoredCollectorErrors
     */
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        \PHPStan\Analyser\AnalyserResult $analyserResult,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $collectorErrors,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        array $locallyIgnoredCollectorErrors
    )
    {
        $this->analyserResult = $analyserResult;
        $this->collectorErrors = $collectorErrors;
        $this->locallyIgnoredCollectorErrors = $locallyIgnoredCollectorErrors;
    }
    /**
     * @return list<Error>
     */
    public function getErrors(): array
    {
        return $this->analyserResult->getErrors();
    }
    public function getAnalyserResult(): \PHPStan\Analyser\AnalyserResult
    {
        return $this->analyserResult;
    }
    /**
     * @return list<Error>
     */
    public function getCollectorErrors(): array
    {
        return $this->collectorErrors;
    }
    /**
     * @return list<Error>
     */
    public function getLocallyIgnoredCollectorErrors(): array
    {
        return $this->locallyIgnoredCollectorErrors;
    }
}
