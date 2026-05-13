<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\ProjectIdea;
use Illuminate\Foundation\Http\FormRequest;

class StoreCommunityIdeaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string'],
            'domain' => ['nullable', 'string', 'max:80'],
            'difficulty' => ['required', 'in:' . implode(',', [
                ProjectIdea::DIFFICULTY_BEGINNER,
                ProjectIdea::DIFFICULTY_INTERMEDIATE,
                ProjectIdea::DIFFICULTY_ADVANCED,
            ])],
            'team_size' => ['nullable', 'integer', 'between:1,20'],
            'suggested_roles' => ['nullable', 'array'],
            'suggested_roles.*' => ['string', 'max:100'],
            'requirements' => ['nullable', 'array'],
            'requirements.*' => ['string', 'max:100'],
        ];
    }
}
