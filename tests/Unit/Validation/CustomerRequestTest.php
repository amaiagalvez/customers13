<?php

namespace Customers13\Tests\Unit\Validation;

use Tests\TestCase;
use Customers13\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;

class CustomerRequestTest extends TestCase
{
    public function test_authorize_returns_true_when_creating(): void
    {
        $request = new class extends FormRequest {
            protected function authorize(): bool
            {
                return true;
            }

            public function rules(): array
            {
                return [
                    'name' => ['required', 'string', 'min:4', 'max:'.\Customers13\Support\Validation\MaxLength::string(),
                        \Illuminate\Validation\Rule::unique(Customer::class)->ignore(null)->whereNull('deleted_at')],
                ];
            }
        };

        $this->assertTrue($request->authorize());
        $this->assertArrayHasKey('name', $request->rules());
        $rules = $request->rules()['name'];
        $this->assertArrayHasKey('required', $rules);
        $this->assertArrayHasKey('min', $rules);
        $this->assertArrayHasKey('max', $rules);
    }
}