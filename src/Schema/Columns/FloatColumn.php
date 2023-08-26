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

use Charcoal\Database\DbDriver;
use Charcoal\Database\ORM\Schema\Traits\NumericValueTrait;
use Charcoal\Database\ORM\Schema\Traits\PrecisionValueTrait;

/**
 * Class FloatColumn
 * @package Charcoal\Database\ORM\Schema\Columns
 */
class FloatColumn extends AbstractColumn
{
    /** @var string */
    public const PRIMITIVE_TYPE = "double";
    /** @var int */
    protected const MAX_DIGITS = 65;
    /** @var int */
    protected const MAX_SCALE = 30;

    /** @var string */
    protected string $type;
    /** @var int */
    private int $digits = 10;
    /** @var int */
    private int $scale = 0;

    use NumericValueTrait;
    use PrecisionValueTrait;

    /**
     * @param string $name
     */
    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->type = "float";
        $this->setDefaultValue("0");
    }

    /**
     * @param float|int $value
     * @return $this
     */
    public function default(float|int $value = 0): static
    {
        $this->setDefaultValue($value);
        return $this;
    }

    /**
     * @param \Charcoal\Database\DbDriver $driver
     * @return string|null
     */
    public function getColumnSQL(DbDriver $driver): ?string
    {
        return match ($driver->value) {
            "mysql" => sprintf('%s(%d,%d)', $this->type, $this->digits, $this->scale),
            "sqlite" => "REAL",
            default => null,
        };
    }
}
