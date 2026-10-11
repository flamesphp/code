<?php

declare (strict_types=1);
namespace PHPStan\Analyser\ResultCache;

use PHPStan\Analyser\AnalyserResult;
final class ResultCacheProcessResult
{
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public AnalyserResult $analyserResult;
    #[\PHPStan\Reflection\Attribute\PrivateProperty]
    public bool $saved;
    public function __construct(
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        AnalyserResult $analyserResult,
        #[\PHPStan\Reflection\Attribute\PrivateProperty]
        bool $saved
    )
    {
        $this->analyserResult = $analyserResult;
        $this->saved = $saved;
    }
    public function getAnalyserResult(): AnalyserResult
    {
        return $this->analyserResult;
    }
    public function isSaved(): bool
    {
        return $this->saved;
    }
}
