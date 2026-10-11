<?php

namespace Flames\Code\Upgrade\ThirdParty\GuzzleHttp;

use Flames\Code\Upgrade\ThirdParty\Psr\Http\Message\MessageInterface;
interface BodySummarizerInterface
{
    /**
     * Returns a summarized message body.
     */
    public function summarize(MessageInterface $message): ?string;
}
