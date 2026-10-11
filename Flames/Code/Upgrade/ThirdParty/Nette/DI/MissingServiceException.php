<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\Nette\DI;

use Flames\Code\Upgrade\ThirdParty\Nette;
/**
 * Service not found exception.
 */
class MissingServiceException extends Nette\InvalidStateException
{
}
/**
 * Not allowed when container is resolving.
 */
class NotAllowedDuringResolvingException extends Nette\InvalidStateException
{
}
/**
 * Error in configuration.
 */
class InvalidConfigurationException extends Nette\InvalidStateException
{
}
