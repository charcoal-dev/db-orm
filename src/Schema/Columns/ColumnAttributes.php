<?php
/*
 * This file is a part of "charcoal-dev/db-orm" package.
 * https://github.com/charcoal-dev/db-orm
 *
 * Copyright (c) Furqan A. Siddiqui <hello@furqansiddiqui.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code or visit following link:
 * https://github.com/charcoal-dev/db-orm/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Charcoal\Database\ORM\Schema\Columns;

use Charcoal\Database\ORM\Exception\OrmQueryError;
use Charcoal\Database\ORM\Exception\OrmQueryException;
use Charcoal\Database\ORM\Schema\Charset;
use Charcoal\OOP\CaseStyles;

/**
 * Class ColumnAttributes
 * @package Charcoal\Database\ORM\Schema\Columns
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
    private string|\Closure|null $modelValueResolver = null;
    private \Closure|null $modelValueDissolve = null;

    /**
     * @param string $name
     */
    public function __construct(
        public readonly string $name
    )
    {
        $this->modelProperty = str_contains($this->name, "_") ? CaseStyles::camelCase($this->name) : $this->name;
    }

    /**
     * @return array
     */
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
            "modelValueResolver" => null,
            "modelValueDissolve" => null,
        ];
    }

    /**
     * @param array $data
     * @return void
     */
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
        $this->modelValueResolver = null;
        $this->modelValueDissolve = null;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function getResolvedModelProperty(mixed $value): mixed
    {
        if (is_null($value)) {
            return null;
        }

        if (!$this->modelValueResolver) {
            return $value; // No changes, return as-is
        }

        if (is_string($this->modelValueResolver)) {
            return new $this->modelValueResolver($value); // Encapsulate value in class
        }

        return call_user_func_array($this->modelValueResolver, [$value]);
    }

    /**
     * @param mixed $value
     * @param \Charcoal\Database\ORM\Schema\Columns\AbstractColumn|null $column
     * @return mixed
     * @throws \Charcoal\Database\ORM\Exception\OrmQueryException
     */
    public function getDissolvedModelProperty(mixed $value, ?AbstractColumn $column = null): mixed
    {
        if ($this->modelValueDissolve) {
            $value = call_user_func_array($this->modelValueDissolve, [$value]);
        }

        if ($column) {
            if (is_null($value)) {
                if (!$column->nullable()) {
                    throw new OrmQueryException(
                        OrmQueryError::COL_VALUE_TYPE_ERROR,
                        sprintf('Column "%s" is not nullable', $column->attributes->modelProperty)
                    );
                }

                return null;
            }

            if (gettype($value) !== $column::PRIMITIVE_TYPE) {
                throw new OrmQueryException(
                    OrmQueryError::COL_VALUE_TYPE_ERROR,
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

    /**
     * Provide a classname or resolver callback function to resolve this column's values when mapped to a model
     * @param string|\Closure $resolver
     * @return $this
     */
    public function setModelsValueResolver(string|\Closure $resolver): static
    {
        $this->modelValueResolver = $resolver;
        return $this;
    }

    /**
     * Value reversing to primitive types, this method is opposite to "setModelsValueResolver"
     * @param \Closure $dissolveFn
     * @return $this
     */
    public function setModelsValueDissolveFn(\Closure $dissolveFn): static
    {
        $this->modelValueDissolve = $dissolveFn;
        return $this;
    }
}
