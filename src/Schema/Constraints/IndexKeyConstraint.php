<?php
declare(strict_types=1);

namespace Charcoal\Database\ORM\Schema\Constraints;

use Charcoal\Database\DbDriver;

/**
 * Class IndexKeyConstraint
 * @package Charcoal\Database\ORM\Schema\Constraints
 */
class IndexKeyConstraint extends AbstractConstraint
{
    /** @var array */
    private array $columns = [];

    /**
     * @param string ...$cols
     * @return $this
     */
    public function columns(string ...$cols): static
    {
        $this->columns = $cols;
        return $this;
    }

    /**
     * @param DbDriver $driver
     * @return string|null
     */
    public function getConstraintSQL(DbDriver $driver): ?string
    {
        $columns = implode(",", array_map(function ($col) {
            return sprintf('`%s`', $col);
        }, $this->columns));

        return match ($driver->value) {
            "mysql" => sprintf('INDEX `%s` (%s)', $this->name, $columns),
            default => null,
        };
    }
}