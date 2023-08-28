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
use Charcoal\Database\ORM\Schema\Traits\BigStringSizeTrait;

/**
 * Class BlobColumn
 * @package Charcoal\Database\ORM\Schema\Columns
 */
class BlobColumn extends AbstractColumn
{
    /** @var string */
    public const PRIMITIVE_TYPE = "string";

    /** @var string */
    protected string $size = "";

    use BigStringSizeTrait;

    /**
     * @return array
     */
    public function __serialize(): array
    {
        $data = parent::__serialize();
        $data["size"] = $this->size;
        return $data;
    }

    /**
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->size = $data["size"];
        unset($data["size"]);
        parent::__unserialize($data);
    }

    /**
     * @param \Charcoal\Database\DbDriver $driver
     * @return string|null
     */
    public function getColumnSQL(DbDriver $driver): ?string
    {
        return match ($driver->value) {
            "mysql" => sprintf('%sBLOB', strtoupper($this->size ?? "")),
            default => "BLOB",
        };
    }
}
