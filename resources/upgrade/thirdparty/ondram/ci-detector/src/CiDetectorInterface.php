<?php

declare (strict_types=1);
namespace FlamesPrefix202610\OndraM\CiDetector;

use FlamesPrefix202610\OndraM\CiDetector\Ci\CiInterface;
use FlamesPrefix202610\OndraM\CiDetector\Exception\CiNotDetectedException;
/**
 * Unified way to get environment variables from current continuous integration server
 */
interface CiDetectorInterface
{
    /**
     * Is current environment an recognized CI server?
     */
    public function isCiDetected(): bool;
    /**
     * Detect current CI server and return instance of its settings
     *
     * @throws CiNotDetectedException
     */
    public function detect(): CiInterface;
}
