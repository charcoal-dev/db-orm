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
use Charcoal\Database\ORM\Schema\Traits\ColumnCharsetTrait;
use Charcoal\Database\ORM\Schema\Traits\LengthValueTrait;
use Charcoal\Database\ORM\Schema\Traits\StringValueTrait;
use Charcoal\Database\ORM\Schema\Traits\UniqueValueTrait;

/**
 * Class StringColumn
 * @package Charcoal\Database\ORM\Schema\Columns
 */
class StringColumn extends AbstractColumn
{
    /** @var string */
    public const PRIMITIVE_TYPE = "string";
    /** @var int */
    protected const LENGTH_MIN = 1;
    /** @var int */
    protected const LENGTH_MAX = 0xffff;

    /** @var int */
    private int $length = 255;
    /** @var bool */
    private bool $fixed = false;

    use ColumnCharsetTrait;
    use LengthValueTrait;
    use StringValueTrait;
    use UniqueValueTrait;

    /**
     * @param \Charcoal\Database\DbDriver $driver
     * @return string|null
     */
    public function getColumnSQL(DbDriver $driver): ?string
    {
        switch ($driver->value) {
            case "mysql":
                $type = $this->fixed ? "char" : "varchar";
                return sprintf('%s(%d)', $type, $this->length);
            case "sqlite":
            default:
                return "TEXT";
        }
    }
}
