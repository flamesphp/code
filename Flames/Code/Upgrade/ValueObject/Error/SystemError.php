<?php

declare (strict_types=1);
namespace Flames\Code\Upgrade\ValueObject\Error;

use FlamesPrefix202610\Nette\Utils\Strings;
use Flames\Code\Upgrade\Parallel\Contract\SerializableInterface;
use Flames\Code\Upgrade\Parallel\ValueObject\BridgeItem;
/**
 * @see \Flames\Code\Upgrade\Tests\ValueObject\Error\SystemErrorTest
 */
final readonly class SystemError implements SerializableInterface
{
    public function __construct(private string $message, private ?string $relativeFilePath = null, private ?int $line = null, private ?string $rectorClass = null)
    {
    }
    public function getMessage(): string
    {
        return $this->message;
    }
    public function getLine(): ?int
    {
        return $this->line;
    }
    public function getRelativeFilePath(): ?string
    {
        return $this->relativeFilePath;
    }
    public function getAbsoluteFilePath(): ?string
    {
        if ($this->relativeFilePath === null) {
            return null;
        }
        return \realpath($this->relativeFilePath);
    }
    /**
     * @return array{
     *     message: string,
     *     relative_file_path: string|null,
     *     absolute_file_path: string|null,
     *     line: int|null,
     *     rector_class: string|null
     * }
     */
    public function jsonSerialize(): array
    {
        return [BridgeItem::MESSAGE => $this->message, BridgeItem::RELATIVE_FILE_PATH => $this->relativeFilePath, BridgeItem::ABSOLUTE_FILE_PATH => $this->getAbsoluteFilePath(), BridgeItem::LINE => $this->line, BridgeItem::RECTOR_CLASS => $this->rectorClass];
    }
    /**
     * @param array<string, mixed> $json
     */
    public static function decode(array $json): self
    {
        return new self($json[BridgeItem::MESSAGE], $json[BridgeItem::RELATIVE_FILE_PATH], $json[BridgeItem::LINE], $json[BridgeItem::RECTOR_CLASS]);
    }
    public function getRectorClass(): ?string
    {
        return $this->rectorClass;
    }
    public function getRectorShortClass(): ?string
    {
        $rectorClass = $this->rectorClass;
        if (!in_array($rectorClass, [null, ''], \true)) {
            return (string) Strings::after($rectorClass, '\\', -1);
        }
        return null;
    }
}
