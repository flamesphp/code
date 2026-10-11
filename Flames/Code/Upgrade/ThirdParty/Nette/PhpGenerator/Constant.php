<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace Flames\Code\Upgrade\ThirdParty\Nette\PhpGenerator;

use Flames\Code\Upgrade\ThirdParty\Nette;
/**
 * Class constant.
 */
final class Constant
{
    use Nette\SmartObject;
    use Traits\NameAware;
    use Traits\VisibilityAware;
    use Traits\CommentAware;
    use Traits\AttributeAware;
    /** @var mixed */
    private $value;
    /** @var bool */
    private $final = \false;
    /** @return static */
    public function setValue($val): self
    {
        $this->value = $val;
        return $this;
    }
    public function getValue()
    {
        return $this->value;
    }
    /** @return static */
    public function setFinal(bool $state = \true): self
    {
        $this->final = $state;
        return $this;
    }
    public function isFinal(): bool
    {
        return $this->final;
    }
}
