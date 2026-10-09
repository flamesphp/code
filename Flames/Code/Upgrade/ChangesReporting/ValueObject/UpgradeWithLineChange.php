<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ChangesReporting\ValueObject;

use Flames\Code\Upgrade\Contract\Rector\RectorInterface;
use Flames\Code\Upgrade\Parallel\Contract\SerializableInterface;
use Flames\Code\Upgrade\PostRector\Rector\PostRectorInterface;
use Flames\Code\Upgrade\ThirdParty\Webmozart\Assert\Assert;
final readonly class UpgradeWithLineChange implements SerializableInterface
{
    private const string KEY_RECTOR_CLASS = 'rector_class';
    private const string KEY_LINE = 'line';
    /**
     * @param class-string<RectorInterface|PostRectorInterface> $rectorClass
     */
    public function __construct(private string $rectorClass, private int $line)
    {
    }
    /**
     * @return class-string<RectorInterface|PostRectorInterface>
     */
    public function getRectorClass(): string
    {
        return $this->rectorClass;
    }
    public function getLine(): int
    {
        return $this->line;
    }
    /**
     * @param array<string, mixed> $json
     */
    public static function decode(array $json): self
    {
        /** @var class-string<RectorInterface> $rectorClass */
        $rectorClass = $json[self::KEY_RECTOR_CLASS];
        Assert::string($rectorClass);
        $line = $json[self::KEY_LINE];
        Assert::integer($line);
        return new self($rectorClass, $line);
    }
    /**
     * @return array{rector_class: class-string<RectorInterface|PostRectorInterface>, line: int}
     */
    public function jsonSerialize(): array
    {
        return [self::KEY_RECTOR_CLASS => $this->rectorClass, self::KEY_LINE => $this->line];
    }
}
