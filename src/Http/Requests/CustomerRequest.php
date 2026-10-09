<?php

namespace Customers13\Http\Requests;

use Basics13\Support\Validation\MaxLength;
use Customers13\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $name = $this->input('name');

        if (is_string($name)) {
            $this->merge(['name' => trim($name)]);
        }
    }

    public function authorize(): bool
    {
        $customer = $this->route('customer');

        return $customer
            ? ($this->user()?->can('update', $customer) ?? false)
            : ($this->user()?->can('create', Customer::class) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:4',
                'max:'.MaxLength::string(),
                Rule::unique(Customer::class)
                    ->ignore($this->route('customer'))
                    ->whereNull('deleted_at'),
            ],
            'notes' => ['nullable', 'string', 'max:'.MaxLength::longText()],
            'reuse_deleted_name' => ['sometimes', 'boolean'],
        ];
    }
}
