<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Rules\Naming\Naming;

use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\GroupUse;
use Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Use_;
use Flames\Code\Upgrade\Application\Provider\CurrentFileProvider;
use Flames\Code\Upgrade\PhpParser\Node\FileNode;
use Flames\Code\Upgrade\ValueObject\Application\File;
final readonly class UseImportsResolver
{
    public function __construct(private CurrentFileProvider $currentFileProvider)
    {
    }
    /**
     * @return array<Use_|GroupUse>
     */
    public function resolve(): array
    {
        $file = $this->currentFileProvider->getFile();
        if (!$file instanceof File) {
            return [];
        }
        $rootNode = $file->getFileNode();
        if (!$rootNode instanceof FileNode) {
            return [];
        }
        return $rootNode->getUsesAndGroupUses();
    }
    /**
     * @api
     * @return Use_[]
     */
    public function resolveBareUses(): array
    {
        $file = $this->currentFileProvider->getFile();
        if (!$file instanceof File) {
            return [];
        }
        $fileNode = $file->getFileNode();
        if (!$fileNode instanceof FileNode) {
            return [];
        }
        return $fileNode->getUses();
    }
    /**
     * @param \Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\Use_|\Flames\Code\Upgrade\ThirdParty\PhpParser\Node\Stmt\GroupUse $use
     */
    public function resolvePrefix($use): string
    {
        return $use instanceof GroupUse ? $use->prefix . '\\' : '';
    }
}
