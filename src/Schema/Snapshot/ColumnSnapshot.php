<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Snapshot;

use Charcoal\Contracts\Charsets\Charset;
use Charcoal\Database\Orm\Contracts\ColumnValuePipeEnumInterface;
use Charcoal\Database\Orm\Enums\ColumnType;

/**
 * Snapshot of column attributes
 */
final readonly class ColumnSnapshot
{
    public function __construct(
        public string                        $name,
        public string                        $entityMapKey,
        public ColumnType                    $type,
        public bool                          $nullable,
        public ?bool                         $unSigned,
        public ?bool                         $unique,
        public ?bool                         $autoIncrement,
        public ?Charset                      $charset,
        public int|float|string|null         $defaultValue,
        public ?int                          $byteLen,
        public ?bool                         $fixedLen,
        public ?ColumnValuePipeEnumInterface $valuePipe,
        public ?array                        $pipeContext,
        public ?string                       $schemaSql,
    )
    {
    }

    /**
     * Serialize the object to an array.
     */
    public function __serialize(): array
    {
        return [
            "name" => $this->name,
            "entityMapKey" => $this->entityMapKey,
            "type" => $this->type,
            "nullable" => $this->nullable,
            "unSigned" => $this->unSigned,
            "unique" => $this->unique,
            "autoIncrement" => $this->autoIncrement,
            "charset" => $this->charset,
            "defaultValue" => $this->defaultValue,
            "byteLen" => $this->byteLen,
            "fixedLen" => $this->fixedLen,
            "valuePipe" => $this->valuePipe,
            "pipeContext" => $this->pipeContext,
            "schemaSql" => $this->schemaSql,
        ];
    }

    /**
     * Unserialize the object from the given serialized data.
     */
    public function __unserialize(array $data): void
    {
        $this->name = $data["name"];
        $this->entityMapKey = $data["entityMapKey"];
        $this->type = $data["type"];
        $this->nullable = $data["nullable"];
        $this->unSigned = $data["unSigned"];
        $this->unique = $data["unique"];
        $this->autoIncrement = $data["autoIncrement"];
        $this->charset = $data["charset"];
        $this->defaultValue = $data["defaultValue"];
        $this->byteLen = $data["byteLen"];
        $this->fixedLen = $data["fixedLen"];
        $this->valuePipe = $data["valuePipe"];
        $this->pipeContext = $data["pipeContext"];
        $this->schemaSql = $data["schemaSql"];
    }
}