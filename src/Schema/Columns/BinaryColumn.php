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
use Charcoal\Database\ORM\Schema\Traits\LengthValueTrait;
use Charcoal\Database\ORM\Schema\Traits\StringValueTrait;
use Charcoal\Database\ORM\Schema\Traits\UniqueValueTrait;

/**
 * Class BinaryColumn
 * @package Charcoal\Database\ORM\Schema\Columns
 */
class BinaryColumn extends AbstractColumn
{
    /** @var string */
    public const PRIMITIVE_TYPE = "string";
    /** @var int */
    protected const LENGTH_MIN = 1;
    /** @var int */
    protected const LENGTH_MAX = 0xffff;

    /** @var int */
    protected int $length = 255;
    /** @var bool */
    protected bool $fixed = false;

    use LengthValueTrait;
    use StringValueTrait;
    use UniqueValueTrait;

    /**
     * @return array
     */
    public function __serialize(): array
    {
        $data = parent::__serialize();
        $data["length"] = $this->length;
        $data["fixed"] = $this->fixed;
        return $data;
    }

    /**
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->length = $data["length"];
        $this->fixed = $data["fixed"];
        unset($data["length"], $data["fixed"]);
        parent::__unserialize($data);
    }

    /**
     * @param \Charcoal\Database\DbDriver $driver
     * @return string|null
     */
    public function getColumnSQL(DbDriver $driver): ?string
    {
        switch ($driver->value) {
            case "mysql":
                $type = $this->fixed ? "binary" : "varbinary";
                return sprintf('%s(%d)', $type, $this->length);
            case "sqlite":
            default:
                return "BLOB";
        }
    }
}
