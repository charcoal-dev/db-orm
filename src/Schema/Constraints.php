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

namespace Charcoal\Database\ORM\Schema;

use Charcoal\Database\ORM\Schema\Constraints\ForeignKeyConstraint;
use Charcoal\Database\ORM\Schema\Constraints\IndexKeyConstraint;
use Charcoal\Database\ORM\Schema\Constraints\UniqueKeyConstraint;

/**
 * Class Constraints
 * @package Charcoal\Database\ORM\Schema
 */
class Constraints implements \IteratorAggregate
{
    private array $constraints = [];

    /**
     * @param string $key
     * @return \Charcoal\Database\ORM\Schema\Constraints\UniqueKeyConstraint
     */
    public function uniqueKey(string $key): UniqueKeyConstraint
    {
        $constraint = new UniqueKeyConstraint($key);
        $this->constraints[$key] = $constraint;
        return $constraint;
    }

    /**
     * @param string $key
     * @return ForeignKeyConstraint
     */
    public function foreignKey(string $key): ForeignKeyConstraint
    {
        $constraint = new ForeignKeyConstraint($key);
        $this->constraints[$key] = $constraint;
        return $constraint;
    }

    /**
     * @param string $column
     * @param string $prefix
     * @return void
     */
    public function addIndex(string $column, string $prefix = "idx_"): void
    {
        $key = $prefix . $column;
        $this->constraints[$key] = (new IndexKeyConstraint($key))->columns($column);
    }

    /**
     * @param string $key
     * @return IndexKeyConstraint
     */
    public function addIndexComposite(string $key): IndexKeyConstraint
    {
        $constraint = new IndexKeyConstraint($key);
        $this->constraints[$key] = $constraint;
        return $constraint;
    }

    /**
     * @return \Traversable
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->constraints);
    }
}
