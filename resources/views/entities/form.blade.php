@php
    // The form is only rendered from the active list, which is the only state that passes the
    // selectable types in.
    $entityTypes = $entityTypes ?? collect();
@endphp

<x-basics13::forms.tracked-resource prefix="entity" context-field="_entity_form" id-field="_entity_id">
    <flux:field>
        <x-basics13::forms.field-label :request="\Entities13\Http\Requests\EntityRequest::class" field="name" test="entity-name-info"
            for="entity-name">
            {{ __('Name') }}
        </x-basics13::forms.field-label>
        <flux:input name="name" x-model="form.name" minlength="4"
            maxlength="{{ \Basics13\Support\Validation\MaxLength::string() }}" required autofocus
            id="entity-name" data-test="entity-name" />
        <flux:error name="name" />
    </flux:field>
    <flux:field>
        <x-basics13::forms.field-label :request="\Entities13\Http\Requests\EntityRequest::class" field="entity_type_ids"
            test="entity-type-ids-info" for="entity-type-ids" :notices="[__('You can pick several types.')]">
            {{ __('Types') }}
        </x-basics13::forms.field-label>

        {{--
            One checkbox per type rather than a multiple select: a list shows every type and which of
            them the entity holds at a glance, needs no Ctrl/Cmd, and is the shape the controller
            already passes the types in. The panel scrolls once the list outgrows the drawer, and the
            name of the language the type is always named in is the one on screen.
        --}}
        <div id="entity-type-ids" data-test="entity-type-ids" role="group"
            aria-label="{{ __('Types') }}"
            class="max-h-64 divide-y divide-zinc-100 overflow-y-auto rounded-lg border border-zinc-200 border-b-zinc-300/80 bg-white shadow-xs dark:divide-white/5 dark:border-white/10 dark:bg-white/10">
            @forelse ($entityTypes as $entityType)
                <label data-entity-type-active="{{ $entityType->active ? 'true' : 'false' }}"
                    x-show="$el.dataset.entityTypeActive === 'true' || form.entity_type_ids.includes($el.querySelector('input').value)"
                    class="flex cursor-pointer items-center gap-3 px-3 py-2.5 text-sm text-zinc-700 transition-colors hover:bg-zinc-50 has-[:checked]:bg-brand-50 dark:text-zinc-300 dark:hover:bg-white/5 dark:has-[:checked]:bg-brand-500/10">
                    <input type="checkbox" name="entity_type_ids[]"
                        value="{{ (string) $entityType->id }}" x-model="form.entity_type_ids"
                        class="size-4 shrink-0 rounded accent-brand-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-focus dark:accent-brand-400" />
                    <span class="min-w-0 flex-1 truncate">{{ $entityType->name_eu }}</span>
                    @unless ($entityType->active)
                        <span
                            class="shrink-0 rounded-full bg-zinc-800/5 px-2 py-0.5 text-xs text-zinc-600 dark:bg-white/10 dark:text-zinc-300">
                            {{ __('basics13::messages.archived_label') }}
                        </span>
                    @endunless
                </label>
            @empty
                <p class="px-3 py-2.5 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('No entity types yet.') }}</p>
            @endforelse
        </div>

        <flux:error name="entity_type_ids" />
    </flux:field>
    <flux:field>
        <x-basics13::forms.field-label :request="\Entities13\Http\Requests\EntityRequest::class" field="notes" test="entity-notes-info"
            for="entity-notes">
            {{ __('Notes') }}
        </x-basics13::forms.field-label>
        <flux:textarea name="notes" x-model="form.notes" rows="3"
            maxlength="{{ \Basics13\Support\Validation\MaxLength::longText() }}" id="entity-notes"
            data-test="entity-notes" />
        <flux:error name="notes" />
    </flux:field>
</x-basics13::forms.tracked-resource>

{{-- Shown only while an existing entity is being edited (`form.id`), with the persons it carries. --}}
@include('entities13::entities.persons-panel')
