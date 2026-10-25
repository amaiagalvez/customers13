@php
    // The form is only rendered from the active list, which passes every entity and type in: an edit
    // keeps an archived one it already holds, while only the active ones are offered.
    $entities = $entities ?? collect();
    $personTypes = $personTypes ?? collect();
    // Creating from the entity panel fixes the entity.
    $fixedEntityId = $fixedEntityId ?? null;

    $langOptions = [
        'eu' => 'Euskera',
        'es' => 'Español',
        'fr' => 'Français',
        'en' => 'English',
    ];
@endphp

<x-basics13::forms.tracked-resource prefix="person" context-field="_person_form"
    id-field="_person_id">
    <flux:field>
        <x-basics13::forms.field-label :request="\Entities13\Http\Requests\PersonRequest::class" field="name"
            test="person-name-info" for="person-name">
            {{ __('Name') }}
        </x-basics13::forms.field-label>
        <flux:input name="name" x-model="form.name" required minlength="2"
            maxlength="{{ \Basics13\Support\Validation\MaxLength::string() }}"
            id="person-name" data-test="person-name" autofocus />
        <flux:error name="name" />
    </flux:field>

    <flux:field>
        <x-basics13::forms.field-label :request="\Entities13\Http\Requests\PersonRequest::class" field="last_name_1"
            test="person-last_name_1-info" for="person-last_name_1">
            {{ __('First last name') }}
        </x-basics13::forms.field-label>
        <flux:input name="last_name_1" x-model="form.last_name_1"
            maxlength="{{ \Basics13\Support\Validation\MaxLength::string() }}"
            id="person-last_name_1" data-test="person-last_name_1" />
        <flux:error name="last_name_1" />
    </flux:field>

    <flux:field>
        <x-basics13::forms.field-label :request="\Entities13\Http\Requests\PersonRequest::class" field="last_name_2"
            test="person-last_name_2-info" for="person-last_name_2">
            {{ __('Second last name') }}
        </x-basics13::forms.field-label>
        <flux:input name="last_name_2" x-model="form.last_name_2"
            maxlength="{{ \Basics13\Support\Validation\MaxLength::string() }}"
            id="person-last_name_2" data-test="person-last_name_2" />
        <flux:error name="last_name_2" />
    </flux:field>

    <flux:field>
        <x-basics13::forms.field-label :request="\Entities13\Http\Requests\PersonRequest::class" field="document_number"
            test="person-document_number-info" for="person-document_number">
            {{ __('Document number') }}
        </x-basics13::forms.field-label>
        <flux:input name="document_number" x-model="form.document_number"
            maxlength="{{ \Basics13\Support\Validation\MaxLength::string() }}"
            id="person-document_number" data-test="person-document_number" />
        <flux:error name="document_number" />
    </flux:field>

    <flux:field>
        <x-basics13::forms.field-label :request="\Entities13\Http\Requests\PersonRequest::class" field="lang"
            test="person-lang-info" for="person-lang">
            {{ __('Language') }}
        </x-basics13::forms.field-label>
        <flux:select name="lang" x-model="form.lang"
            id="person-lang" data-test="person-lang">
            <option value="">{{ __('Select language') }}</option>
            @foreach ($langOptions as $code => $label)
                <option value="{{ $code }}">{{ $label }}</option>
            @endforeach
        </flux:select>
        <flux:error name="lang" />
    </flux:field>

    <flux:field>
        <x-basics13::forms.field-label :request="\Entities13\Http\Requests\PersonRequest::class" field="entity_id"
            test="person-entity_id-info" for="person-entity_id">
            {{ __('Entity') }}
        </x-basics13::forms.field-label>
        @if ($fixedEntityId !== null)
            <input type="hidden" name="entity_id" value="{{ $fixedEntityId }}" x-init="form.entity_id = @js((string) $fixedEntityId)" />
        @endif
        <flux:select name="entity_id" x-model="form.entity_id"
            :disabled="$fixedEntityId !== null" id="person-entity_id" data-test="person-entity_id">
            <option value="">{{ __('Select entity') }}</option>
            @foreach ($entities as $entity)
                <option value="{{ (string) $entity->id }}"
                    @unless ($entity->active) x-bind:hidden="String(form.entity_id) !== '{{ $entity->id }}'" @endunless>
                    {{ $entity->name }}
                </option>
            @endforeach
        </flux:select>
        <flux:error name="entity_id" />
    </flux:field>

    <flux:field>
        <x-basics13::forms.field-label :request="\Entities13\Http\Requests\PersonRequest::class" field="person_type_ids"
            test="person-person_type_ids-info" for="person-person_type_ids">
            {{ __('Person types') }}
        </x-basics13::forms.field-label>
        <div id="person-person_type_ids" data-test="person-person_type_ids" role="group"
            aria-label="{{ __('Person types') }}"
            class="max-h-64 divide-y divide-zinc-100 overflow-y-auto rounded-lg border border-zinc-200 border-b-zinc-300/80 bg-white shadow-xs dark:divide-white/5 dark:border-white/10 dark:bg-white/10">
            @forelse ($personTypes as $personType)
                <label data-person-type-active="{{ $personType->active ? 'true' : 'false' }}"
                    x-show="$el.dataset.personTypeActive === 'true' || form.person_type_ids.includes($el.querySelector('input').value)"
                    class="flex cursor-pointer items-center gap-3 px-3 py-2.5 text-sm text-zinc-700 transition-colors hover:bg-zinc-50 has-[:checked]:bg-brand-50 dark:text-zinc-300 dark:hover:bg-white/5 dark:has-[:checked]:bg-brand-500/10">
                    <input type="checkbox" name="person_type_ids[]"
                        value="{{ (string) $personType->id }}" x-model="form.person_type_ids"
                        class="size-4 shrink-0 rounded accent-brand-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus dark:accent-brand-400" />
                    <span class="min-w-0 flex-1 truncate">{{ $personType->name_eu }}</span>
                    @unless ($personType->active)
                        <span
                            class="shrink-0 rounded-full bg-zinc-800/5 px-2 py-0.5 text-xs text-zinc-600 dark:bg-white/10 dark:text-zinc-300">
                            {{ __('basics13::messages.archived_label') }}
                        </span>
                    @endunless
                </label>
            @empty
                <p class="px-3 py-2.5 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('No person types yet.') }}</p>
            @endforelse
        </div>
        <flux:error name="person_type_ids" />
    </flux:field>

    <flux:field>
        <x-basics13::forms.field-label :request="\Entities13\Http\Requests\PersonRequest::class" field="notes"
            test="person-notes-info" for="person-notes">
            {{ __('Notes') }}
        </x-basics13::forms.field-label>
        <flux:textarea name="notes" x-model="form.notes" rows="3"
            maxlength="{{ \Basics13\Support\Validation\MaxLength::longText() }}"
            id="person-notes" data-test="person-notes" />
        <flux:error name="notes" />
    </flux:field>
</x-basics13::forms.tracked-resource>