<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Columns;

use Charcoal\Base\Enums\Charset;
use Charcoal\Base\Support\CaseStyle;
use Charcoal\Database\Orm\Exception\OrmError;
use Charcoal\Database\Orm\Exception\OrmQueryException;

/**
 * Class ColumnAttributes
 * @package Charcoal\Database\Orm\Schema\Columns
 */
class ColumnAttributes
{
    public bool $nullable = false;
    public ?bool $unSigned = null;
    public ?bool $unique = null;
    public ?bool $autoIncrement = null;
    public ?Charset $charset = null;
    public int|float|string|null $defaultValue = null;

    public readonly string $modelProperty;
    private string|\Closure|null $resolveTypedValueFn = null;
    private \Closure|null $resolveDbValueFn = null;

    public function __construct(
        public readonly string $name
    )
    {
        $this->modelProperty = str_contains($this->name, "_") ?
            CaseStyle::CAMEL_CASE->from($this->name, CaseStyle::SNAKE_CASE) : $this->name;
    }

    public function __serialize(): array
    {
        return [
            "name" => $this->name,
            "nullable" => $this->nullable,
            "unSigned" => $this->unSigned,
            "unique" => $this->unique,
            "autoIncrement" => $this->autoIncrement,
            "charset" => $this->charset,
            "defaultValue" => $this->defaultValue,
            "modelProperty" => $this->modelProperty,
            "resolveTypedValueFn" => null,
            "resolveDbValueFn" => null,
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->name = $data["name"];
        $this->nullable = $data["nullable"];
        $this->unSigned = $data["unSigned"];
        $this->unique = $data["unique"];
        $this->autoIncrement = $data["autoIncrement"];
        $this->charset = $data["charset"];
        $this->defaultValue = $data["defaultValue"];
        $this->modelProperty = $data["modelProperty"];
        $this->resolveTypedValueFn = null;
        $this->resolveDbValueFn = null;
    }

    public function getResolvedModelProperty(mixed $value): mixed
    {
        if (is_null($value)) {
            return null;
        }

        if (!$this->resolveTypedValueFn) {
            return $value; // No changes, return as-is
        }

        if (is_string($this->resolveTypedValueFn)) {
            return new $this->resolveTypedValueFn($value); // Encapsulate value in class
        }

        return call_user_func_array($this->resolveTypedValueFn, [$value]);
    }

    /**
     * @throws OrmQueryException
     */
    public function getDissolvedModelProperty(mixed $value, ?AbstractColumn $column = null): mixed
    {
        if ($this->resolveDbValueFn) {
            $value = call_user_func_array($this->resolveDbValueFn, [$value]);
        }

        if ($column) {
            if (is_null($value)) {
                if (!$column->nullable()) {
                    throw new OrmQueryException(
                        OrmError::COL_VALUE_TYPE_ERROR,
                        sprintf('Column "%s" is not nullable', $column->attributes->modelProperty)
                    );
                }

                return null;
            }

            if (gettype($value) !== $column::PRIMITIVE_TYPE) {
                throw new OrmQueryException(
                    OrmError::COL_VALUE_TYPE_ERROR,
                    sprintf(
                        'Column "%s" value is expected to be of type "%s", got "%s"',
                        $column->attributes->modelProperty,
                        $column::PRIMITIVE_TYPE,
                        gettype($value)
                    )
                );
            }
        }

        return $value;
    }

    public function resolveTypedValue(string|\Closure $resolver): static
    {
        $this->resolveTypedValueFn = $resolver;
        return $this;
    }

    public function resolveDbValue(\Closure $dissolveFn): static
    {
        $this->resolveDbValueFn = $dissolveFn;
        return $this;
    }
}
