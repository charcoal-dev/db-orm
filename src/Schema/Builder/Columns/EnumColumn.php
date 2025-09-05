<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Builder\Columns;

use Charcoal\Database\Enums\DbDriver;
use Charcoal\Database\Orm\Enums\ColumnType;
use Charcoal\Database\Orm\Schema\Builder\Traits\ColumnCharsetTrait;

/**
 * Class EnumColumn
 * @package Charcoal\Database\Orm\Schema\Columns
 */
class EnumColumn extends AbstractColumnBuilder
{
    protected array $options = [];

    use ColumnCharsetTrait;

    public function __construct(string $name)
    {
        parent::__construct($name, ColumnType::Enum);
    }

    public function options(string ...$opts): static
    {
        $this->options = $opts;
        return $this;
    }

    public function default(string $opt): static
    {
        if (!in_array($opt, $this->options)) {
            throw new \OutOfBoundsException(
                sprintf('Default value for "%s" must be from defined options', $this->attributes->name)
            );
        }

        $this->setDefaultValue($opt);
        return $this;
    }

    public function getColumnSQL(DbDriver $driver): ?string
    {
        $options = implode(",", array_map(function (string $opt) {
            return sprintf("'%s'", $opt);
        }, $this->options));

        return match ($driver) {
            DbDriver::MYSQL => sprintf('enum(%s)', $options),
            DbDriver::SQLITE => sprintf('TEXT CHECK(%s in (%s))', $this->attributes->name, $options),
            default => null,
        };
    }
}

