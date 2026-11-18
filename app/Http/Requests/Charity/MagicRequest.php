<?php
// Validates the email address a magic link is requested or checked for.

namespace App\Http\Requests\Charity;

use App\Rules\ValidDomainEmail;
use Illuminate\Foundation\Http\FormRequest;

class MagicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // When following the link the email address sits base64 encoded in the URL.
        if ($this->isMethod('get')) {
            $this->merge(['email' => base64_decode($this->input('email'))]);
        }
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', new ValidDomainEmail],
        ];
    }
}
