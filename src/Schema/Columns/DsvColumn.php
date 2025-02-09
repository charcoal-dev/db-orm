<?php
declare(strict_types=1);

namespace Charcoal\Database\ORM\Schema\Columns;

use Charcoal\OOP\Vectors\DsvString;

/**
 * Class DsvColumn
 * @package Charcoal\Database\ORM\Schema\Columns
 */
class DsvColumn extends StringColumn
{
    private string $delimiter = ",";
    private int $limit = 1;
    private bool $caseInsensitive = false;

    /**
     * @return void
     */
    protected function attributesCallback(): void
    {
        $this->attributes->setModelsValueResolver(function (?string $dsvString): DsvString {
            return new DsvString($dsvString, $this->limit, $this->caseInsensitive, $this->delimiter);
        });

        $this->attributes->setModelsValueDissolveFn(function (?DsvString $dsvString): ?string {
            if ($dsvString->delimiter !== $this->delimiter) {
                throw new \UnexpectedValueException("Delimiter of DsvString vector does not match with column");
            }

            if ($dsvString->caseInsensitive !== $this->caseInsensitive) {
                throw new \UnexpectedValueException("CaseInsensitive of DsvString vector does not match with column");
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

    /**
     * @param int $limit
     * @return $this
     */
    public function limit(int $limit = 0): static
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @return $this
     */
    public function caseInsensitive(): static
    {
        $this->caseInsensitive = true;
        return $this;
    }

    /**
     * @return $this
     */
    public function caseSensitive(): static
    {
        $this->caseInsensitive = false;
        return $this;
    }

    /**
     * @param string $delimiter
     * @return $this
     */
    public function delimiter(string $delimiter = ","): static
    {
        if (strlen($delimiter) !== 1) {
            throw new \InvalidArgumentException("Delimiter must be a single character");
        }

        $this->delimiter = $delimiter;
        return $this;
    }

    /**
     * @return array
     */
    public function __serialize(): array
    {
        $data = parent::__serialize();
        $data["limit"] = $this->limit;
        $data["delimiter"] = $this->delimiter;
        $data["caseInsensitive"] = $this->caseInsensitive;
        return $data;
    }

    /**
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->limit = $data["limit"];
        $this->delimiter = $data["delimiter"];
        $this->caseInsensitive = $data["caseInsensitive"];
        unset($data["limit"], $data["delimiter"], $data["caseInsensitive"]);
        parent::__unserialize($data);
    }
}