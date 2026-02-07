<?php

namespace App\Rules;

use App\Helpers\Helper;
use Illuminate\Contracts\Validation\Rule;

class YesWithoutAll implements Rule
{
    protected array $otherFields;

    protected string $attribute;

    public function __construct(...$otherFields)
    {
        $this->otherFields = $otherFields;
    }

    public function passes($attribute, $value): bool
    {
        $this->attribute = $attribute; // Set the attribute name

        if ($value !== 'yes') {
            return true;
        }

        foreach ($this->otherFields as $field) {
            if (request($field)) {
                return true;
            }
        }

        return false;
    }

    public function message(): string
    {
        $formattedAttribute = Helper::formatAttribute($this->attribute);

        return "At least one of the other fields must be selected if $formattedAttribute is yes.";
    }
}
