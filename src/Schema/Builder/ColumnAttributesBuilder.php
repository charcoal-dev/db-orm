<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Builder;

use Charcoal\Base\Objects\Traits\NotCloneableTrait;
use Charcoal\Base\Objects\Traits\NotSerializableTrait;
use Charcoal\Base\Strings\CaseStyle;
use Charcoal\Contracts\Charsets\Charset;
use Charcoal\Database\Orm\Contracts\ColumnValuePipeEnumInterface;
use Charcoal\Database\Orm\Enums\ColumnType;

/**
 * Class ColumnAttributesBuilder
 * @package Charcoal\Database\Orm\Schema\Builder
 */
class ColumnAttributesBuilder
{
    use NotCloneableTrait;
    use NotSerializableTrait;

    public readonly string $entityMapKey;
    public bool $nullable = false;
    public ?bool $unSigned = null;
    public ?bool $unique = null;
    public ?bool $autoIncrement = null;
    public ?Charset $charset = null;
    public int|float|string|null $defaultValue = null;

    private ?ColumnValuePipeEnumInterface $valuePipe = null;
    private ?string $pipeContextFqcn;

    public function __construct(
        public readonly string     $name,
        public readonly ColumnType $type
    )
    {
        $this->entityMapKey = str_contains($this->name, "_") ?
            CaseStyle::CAMEL_CASE->from($this->name, CaseStyle::SNAKE_CASE) :
            $this->name;
    }

    /**
     * @internal
     */
    public function useValuePipe(
        ColumnValuePipeEnumInterface $pipe,
    ): static
    {
        $this->valuePipe = $pipe;
        return $this;
    }

    /**
     * @internal
     */
    public function setPipeContext(string $fqcn): void
    {
        $this->pipeContextFqcn = $fqcn;
    }

    /**
     * @internal
     */
    public function getPipe(): array
    {
        return [$this->valuePipe ?? null,
            $this->pipeContextFqcn ?? null];
    }
}
