@php
    // Euskera is the language a type always has a name in, so it is the only field that is asked
    // for and always shown; the other three are optional translations, shown only when the
    // `languages` setting enables them.
    $nameFields = [
        'name_eu' => __('Name (eu)'),
        'name_es' => __('Name (es)'),
        'name_fr' => __('Name (fr)'),
        'name_en' => __('Name (en)'),
    ];
    $enabledLanguages = \Basics13\Enums\Language::enabledCodes();
    $nameFields = array_filter($nameFields, static fn (string $field): bool => $field === 'name_eu'
        || in_array(substr($field, 5), $enabledLanguages, true), ARRAY_FILTER_USE_KEY);
@endphp

<x-basics13::forms.tracked-resource prefix="person-type" context-field="_person_type_form"
    id-field="_person_type_id">
    @foreach ($nameFields as $field => $label)
        <flux:field>
            <x-basics13::forms.field-label :request="\Entities13\Http\Requests\PersonTypeRequest::class" :field="$field" :test="'person-type-' . $field . '-info'"
                :for="'person-type-' . $field" :labels="$nameFields">
                {{ $label }}
            </x-basics13::forms.field-label>
            <flux:input name="{{ $field }}" x-model="form.{{ $field }}"
                :minlength="$field === 'name_eu' ? 2 : null" :required="$field === 'name_eu'"
                :autofocus="$field === 'name_eu'"
                maxlength="{{ \Basics13\Support\Validation\MaxLength::string() }}"
                id="person-type-{{ $field }}" data-test="person-type-{{ $field }}" />
            <flux:error name="{{ $field }}" />
        </flux:field>
    @endforeach

    <flux:field>
        <x-basics13::forms.field-label :request="\Entities13\Http\Requests\PersonTypeRequest::class" field="notes"
            test="person-type-notes-info" for="person-type-notes">
            {{ __('Notes') }}
        </x-basics13::forms.field-label>
        <flux:textarea name="notes" x-model="form.notes" rows="3"
            maxlength="{{ \Basics13\Support\Validation\MaxLength::longText() }}"
            id="person-type-notes" data-test="person-type-notes" />
        <flux:error name="notes" />
    </flux:field>
</x-basics13::forms.tracked-resource>