<?php

namespace Customers13\Http\Requests;

use Illuminate\Validation\Rule;
use Customers13\Models\Customer;
use Customers13\Models\CustomerType;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rules\Exists;
use Basics13\Support\Validation\MaxLength;
use Illuminate\Foundation\Http\FormRequest;

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
            'customer_type_ids' => ['sometimes', 'array'],
            'customer_type_ids.*' => ['integer', 'distinct', $this->selectableTypeRule()],
            'reuse_deleted_name' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * The types the form offers are the active ones. A type the customer already holds stays valid
     * after it has been archived, so a form that does not change it can still be saved without
     * silently dropping the type from a customer nobody meant to touch.
     */
    private function selectableTypeRule(): Exists
    {
        $customer = $this->route('customer');
        $assigned = $customer instanceof Customer
            ? $customer->customerTypes()->pluck('customer_type_id')->all()
            : [];

        return Rule::exists(CustomerType::class, 'id')
            ->whereNull('deleted_at')
            ->where(static function (Builder $query) use ($assigned): void {
                $query->where('active', true);

                if ($assigned !== []) {
                    $query->orWhereIn('id', $assigned);
                }
            });
    }
}
