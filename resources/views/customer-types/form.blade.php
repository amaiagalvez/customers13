@php
    // Euskera is the language a type always has a name in, so it is the only field that is asked
    // for; the other three are optional translations of the same type.
    $nameFields = [
        'name_eu' => __('Name (eu)'),
        'name_es' => __('Name (es)'),
        'name_fr' => __('Name (fr)'),
        'name_en' => __('Name (en)'),
    ];
@endphp

<x-basics13::forms.tracked-resource prefix="customer-type" context-field="_customer_type_form" id-field="_customer_type_id">
    @foreach ($nameFields as $field => $label)
        <flux:field>
            <x-basics13::forms.field-label :request="\Customers13\Http\Requests\CustomerTypeRequest::class"
                :field="$field" :test="'customer-type-'.$field.'-info'" :for="'customer-type-'.$field"
                :labels="$nameFields">
                {{ $label }}
            </x-basics13::forms.field-label>
            <flux:input name="{{ $field }}" x-model="form.{{ $field }}"
                :minlength="$field === 'name_eu' ? 2 : null"
                :required="$field === 'name_eu'"
                :autofocus="$field === 'name_eu'"
                maxlength="{{ \Basics13\Support\Validation\MaxLength::string() }}"
                id="customer-type-{{ $field }}" data-test="customer-type-{{ $field }}" />
            <flux:error name="{{ $field }}" />
        </flux:field>
    @endforeach

    <flux:field>
        <x-basics13::forms.field-label :request="\Customers13\Http\Requests\CustomerTypeRequest::class" field="notes"
            test="customer-type-notes-info" for="customer-type-notes">
            {{ __('Notes') }}
        </x-basics13::forms.field-label>
        <flux:textarea name="notes" x-model="form.notes" rows="3"
            maxlength="{{ \Basics13\Support\Validation\MaxLength::longText() }}"
            id="customer-type-notes" data-test="customer-type-notes" />
        <flux:error name="notes" />
    </flux:field>
</x-basics13::forms.tracked-resource>
