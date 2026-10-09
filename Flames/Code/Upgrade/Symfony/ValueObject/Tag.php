<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\Symfony\ValueObject;

use Flames\Code\Upgrade\ThirdParty\Symfony\Contract\Tag\TagInterface;
final readonly class Tag implements TagInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private string $name,
        /**
         * @readonly
         */
        private array $data = []
    )
    {
    }
    public function getName(): string
    {
        return $this->name;
    }
    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }
}
