<?php

namespace App\Http\Requests;

use App\Enums\WinType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexTitleReignsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /** Only reigns held by this wrestler (as primary champion or co-champion). */
            'wrestler_id' => ['sometimes', 'string', 'exists:wrestlers,id'],
            'win_type' => ['sometimes', Rule::enum(WinType::class)],
            /** true: only the open reign. false: only ended reigns. */
            'current' => ['sometimes', 'boolean'],
            /** true: only reigns that ended by vacancy. false: exclude them. */
            'vacated' => ['sometimes', 'boolean'],
            'won_on_from' => ['sometimes', 'date'],
            'won_on_to' => ['sometimes', 'date', 'after_or_equal:won_on_from'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * Validated filters with booleans normalised, excluding pagination.
     */
    public function filters(): array
    {
        $filters = $this->safe()->except(['per_page']);

        foreach (['current', 'vacated'] as $flag) {
            if (array_key_exists($flag, $filters)) {
                $filters[$flag] = filter_var($filters[$flag], FILTER_VALIDATE_BOOLEAN);
            }
        }

        return $filters;
    }

    public function perPage(): int
    {
        return (int) $this->validated('per_page', 15);
    }
}
