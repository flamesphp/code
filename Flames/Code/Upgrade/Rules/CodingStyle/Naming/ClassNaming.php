<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\CodingStyle\Naming;

use Flames\Code\Upgrade\ThirdParty\Nette\Strings;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassLike;
final class ClassNaming
{
    /**
     * @param string|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Name|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Identifier|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\ClassLike $name
     */
    public function getShortName($name): string
    {
        if ($name instanceof ClassLike) {
            if (!$name->name instanceof Identifier) {
                return '';
            }
            return $this->getShortName($name->name);
        }
        if ($name instanceof Name || $name instanceof Identifier) {
            $name = $name->toString();
        }
        $name = trim($name, '\\');
        $shortName = Strings::after($name, '\\', -1);
        if (is_string($shortName)) {
            return $shortName;
        }
        return $name;
    }
}
