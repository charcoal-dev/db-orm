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
 * Enum column definition.
 */
class EnumColumn extends AbstractColumnBuilder
{
    protected array $options = [];

    use ColumnCharsetTrait;

    public function __construct(string $name)
    {
        parent::__construct($name, ColumnType::Enum);
    }

    /**
     * Set default enum options.
     */
    public function options(string ...$opts): static
    {
        $this->options = $opts;
        return $this;
    }

    /**
     * @param string $opt
     * @return $this
     */
    public function default(string $opt): static
    {
        if (!$this->options || !in_array($opt, $this->options)) {
            throw new \OutOfBoundsException(
                sprintf('Default value for "%s" must be from defined options', $this->attributes->name)
            );
        }

        $this->setDefaultValue($opt);
        return $this;
    }

    /**
     * Get the column SQL definition.
     */
    public function getColumnSQL(DbDriver $driver): ?string
    {
        $options = implode(",", array_map(function (string $opt) {
            if (str_contains($opt, "'")) {
                throw new \InvalidArgumentException("Enum cases must not contain quotes");
            }

            return sprintf("'%s'", $opt);
        }, $this->options));

        return match ($driver) {
            DbDriver::MYSQL => sprintf("enum(%s)", $options),
            DbDriver::PGSQL,
            DbDriver::SQLITE => sprintf("TEXT CHECK(%s in (%s))", $this->attributes->name, $options),
        };
    }

    /**
     * No CHECK constraint for enums.
     */
    public function getCheckConstraintSQL(DbDriver $driver): ?string
    {
        return null;
    }
}

