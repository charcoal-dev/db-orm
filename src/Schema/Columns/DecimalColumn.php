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
 * Class DecimalColumn
 * @package Charcoal\Database\ORM\Schema\Columns
 */
class DecimalColumn extends AbstractColumn
{
    /** @var string */
    public const PRIMITIVE_TYPE = "string";
    /** @var int */
    protected const MAX_DIGITS = 65;
    /** @var int */
    protected const MAX_SCALE = 30;

    /** @var int */
    protected int $digits = 0;
    /** @var int */
    protected int $scale = 0;

    use NumericValueTrait;
    use PrecisionValueTrait;

    /**
     * @return array
     */
    public function __serialize(): array
    {
        $data = parent::__serialize();
        $data["digits"] = $this->digits;
        $data["scale"] = $this->scale;
        return $data;
    }

    /**
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->digits = $data["digits"];
        $this->scale = $data["scale"];
        unset($data["digits"], $data["scale"]);
        parent::__unserialize($data);
    }

    /**
     * @param string $name
     */
    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->setDefaultValue("0");
    }

    /**
     * @param string $value
     * @return DecimalColumn
     */
    public function default(string $value = "0"): static
    {
        if (!preg_match('/^-?[0-9]+(\.[0-9]+)?$/', $value)) {
            throw new \InvalidArgumentException(sprintf('Bad default decimal value for col "%s"', $this->attributes->name));
        }

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
            "mysql" => sprintf('decimal(%d,%d)', $this->digits, $this->scale),
            "sqlite" => "REAL",
            default => null,
        };
    }
}
