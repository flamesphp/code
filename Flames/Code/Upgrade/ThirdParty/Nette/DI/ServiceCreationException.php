<?php

declare(strict_types=1);

namespace Flames\Code\Upgrade\ThirdParty\Nette\DI;

use Flames\Code\Upgrade\ThirdParty\Nette\InvalidStateException;

class ServiceCreationException extends InvalidStateException
{
    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }
}
