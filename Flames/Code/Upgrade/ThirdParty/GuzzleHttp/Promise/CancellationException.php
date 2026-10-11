<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\GuzzleHttp\Promise;

/**
 * Exception that is set as the reason for a promise that has been cancelled.
 */
class CancellationException extends RejectionException
{
}
