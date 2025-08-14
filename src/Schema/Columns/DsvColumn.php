<?php
/**
 * Part of the "charcoal-dev/db-orm" package.
 * @link https://github.com/charcoal-dev/db-orm
 */

declare(strict_types=1);

namespace Charcoal\Database\Orm\Schema\Columns;

use Charcoal\Base\Enums\Charset;
use Charcoal\Base\Enums\ExceptionAction;
use Charcoal\Base\Support\DsvString;
use Charcoal\Base\Support\Helpers\EnumHelper;

/**
 * Class DsvColumn
 * @package Charcoal\Database\Orm\Schema\Columns
 * @var class-string|null $enumClass
 */
class DsvColumn extends StringColumn
{
    private string $delimiter = ",";
    private bool $caseSensitive = true;
    private ?string $enumClass = null;

    protected function attributesCallback(): void
    {
        $this->attributes->resolveTypedValue(function (?string $dsvString): DsvString {
            return new DsvString($dsvString, $this->delimiter, Charset::ASCII, $this->caseSensitive, true);
        });

        $this->attributes->resolveDbValue(function (?DsvString $dsvString): ?string {
            if (!$dsvString) {
                return null;
            }

            if ($dsvString->delimiter !== $this->delimiter) {
                throw new \UnexpectedValueException("Delimiter of DsvString vector does not match with column");
            }

            if ($dsvString->changeCase !== $this->caseSensitive) {
                throw new \UnexpectedValueException("CaseInsensitive of DsvString vector does not match with column");
            }

            if ($this->enumClass) {
                EnumHelper::validatedEnumCasesFromVector($this->enumClass, $dsvString, ExceptionAction::Ignore);
            }

            $dsvString = $dsvString->toString();
            if (strlen($dsvString) > $this->length) {
                throw new \OverflowException(sprintf(
                    'DsvString value of %d bytes exceeds column "%s" limit of %d bytes',
                    strlen($dsvString),
                    $this->attributes->name,
                    $this->length
                ));
            }

            return $dsvString;
        });
    }

    public function caseInsensitive(): static
    {
        $this->caseSensitive = false;
        return $this;
    }

    public function caseSensitive(): static
    {
        $this->caseSensitive = true;
        return $this;
    }

    /**
     * @param class-string<\StringBackedEnum> $enumClass
     * @return $this
     */
    public function enumClass(string $enumClass): static
    {
        $this->enumClass = $enumClass;
        return $this;
    }

    public function delimiter(string $delimiter = ","): static
    {
        if (strlen($delimiter) !== 1) {
            throw new \InvalidArgumentException("Delimiter must be a single character");
        }

        $this->delimiter = $delimiter;
        return $this;
    }

    public function __serialize(): array
    {
        $data = parent::__serialize();
        $data["delimiter"] = $this->delimiter;
        $data["caseSensitive"] = $this->caseSensitive;
        $data["enumClass"] = $this->enumClass;
        return $data;
    }

    public function __unserialize(array $data): void
    {
        $this->delimiter = $data["delimiter"];
        $this->caseSensitive = $data["caseSensitive"];
        $this->enumClass = $data["enumClass"];
        unset($data["delimiter"], $data["caseInsensitive"], $data["enumClass"]);
        parent::__unserialize($data);
    }
}