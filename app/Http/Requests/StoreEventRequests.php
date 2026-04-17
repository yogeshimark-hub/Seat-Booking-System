<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequests extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
              'event_name' => 'required|string|max:255',
              'event_venue' => 'required|string|max:255',
              'event_date' => 'required|date|after:now',
              'total_rows' => 'required|integer|min:1|max:26',
              'total_columns' => 'required|integer|min:1|max:50',
          ];
    }
}
