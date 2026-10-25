@php
    $editingPerson = $list['create'] && str_starts_with(old('_person_form', ''), 'edit-');

    $initialForm = $list['create']
        ? [
            'id' => $editingPerson ? old('_person_id') : null,
            'name' => old('name', ''),
            'last_name_1' => old('last_name_1', ''),
            'last_name_2' => old('last_name_2', ''),
            'document_number' => old('document_number', ''),
            'lang' => old('lang', ''),
            'entity_id' => old('entity_id', ''),
            'person_type_ids' => old('person_type_ids', []),
            'notes' => old('notes', ''),
            'context' => $editingPerson ? old('_person_form') : 'create',
            'method' => $editingPerson ? 'PUT' : 'POST',
            'action' => $editingPerson
                ? route('persons.update', old('_person_id'))
                : route('persons.store'),
            'title' => $editingPerson ? __('Edit person') : __('New person'),
            'submitLabel' => $editingPerson ? __('Update person') : __('Save person'),
        ]
        : null;

    $searchableFields = $searchableFields ?? [];
    $filters = $filters ?? [];
    $scope = $scope ?? 'active';
@endphp

<x-layouts::app :title="$list['resource']">
    <div x-data="listConfirmation({
        form: @js($initialForm),
        storeUrl: @js(route('persons.store')),
        updateUrl: @js(route('persons.update', '__PERSON__')),
        createPerson() {
            this.form = {
                id: null,
                name: '',
                last_name_1: '',
                last_name_2: '',
                document_number: '',
                lang: '',
                entity_id: @js((string) (request()->integer('entity_id') ?: '')),
                person_type_ids: [],
                notes: '',
                context: 'create',
                method: 'POST',
                action: this.storeUrl,
                title: @js(__('New person')),
                submitLabel: @js(__('Save person')),
            };
        },
        editPerson(person) {
            this.form = {
                ...person,
                context: `edit-${person.id}`,
                method: 'PUT',
                action: this.updateUrl.replace('__PERSON__', person.id),
                title: @js(__('Edit person')),
                submitLabel: @js(__('Update person')),
            };
        },
    })"
        @if ($errors->any()) x-init="$nextTick(() => $dispatch('modal-show', { name: 'person-form' }))"
        @elseif ($list['create'] && request()->boolean('create'))
            x-init="createPerson(); $nextTick(() => $dispatch('modal-show', { name: 'person-form' }))" @endif
        class="flex flex-col gap-y-2 sm:gap-y-3">
        <x-basics13::list.page-header :list="$list" prefix="person" :description="__('Physical persons linked to entities.')">
            <x-slot:actions>
                @if ($list['create'])
                    <x-basics13::list.create-action prefix="person" :label="__('New person')"
                        click="createPerson()" />
                @endif
            </x-slot:actions>
        </x-basics13::list.page-header>

        <x-basics13::list.flash prefix="person" />

        @fragment('list-results')
            <x-basics13::list.searchable-results :search="$list['search']" :total="$persons->total()" :scope="$scope" :filters="$filters">
                <x-basics13::list.table prefix="person" :paginator="$persons">
                    <x-slot:toolbar>
                        <x-basics13::list.search :search="$list['search']" prefix="person" :scope="$scope" :searchableFields="$searchableFields" :filters="$filters" />
                    </x-slot:toolbar>

                    <flux:table.columns>
                        <flux:table.column scope="col">{{ __('Name') }}</flux:table.column>
                        <flux:table.column scope="col">{{ __('First last name') }}</flux:table.column>
                        <flux:table.column scope="col">{{ __('Second last name') }}</flux:table.column>
                        <flux:table.column scope="col">{{ __('Document') }}</flux:table.column>
                        <flux:table.column scope="col">{{ __('Language') }}</flux:table.column>
                        <flux:table.column scope="col">{{ __('Entity') }}</flux:table.column>
                        <flux:table.column scope="col">{{ __('Types') }}</flux:table.column>
                        <flux:table.column scope="col" align="end" sticky class="text-end">
                            <span class="sr-only">{{ __('Actions') }}</span>
                        </flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($list['rows'] as $row)
                            <flux:table.row :key="$row['id']"
                                class="transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <flux:table.cell class="max-w-[16rem]">
                                    <span class="block max-w-full truncate" title="{{ $row['name'] }}"
                                        data-test="person-name-{{ $row['id'] }}">
                                        {{ $row['name'] }}
                                    </span>
                                </flux:table.cell>
                                <flux:table.cell class="max-w-[16rem]">
                                    {{ $row['last_name_1'] ?? '—' }}
                                </flux:table.cell>
                                <flux:table.cell class="max-w-[16rem]">
                                    {{ $row['last_name_2'] ?? '—' }}
                                </flux:table.cell>
                                <flux:table.cell class="max-w-[16rem]">
                                    {{ $row['document_number'] ?? '—' }}
                                </flux:table.cell>
                                <flux:table.cell class="max-w-[8rem]">
                                    {{ $row['lang'] ? strtoupper($row['lang']) : '—' }}
                                </flux:table.cell>
                                <flux:table.cell class="max-w-[16rem]">
                                    {{ $row['entity']['name'] ?? '—' }}
                                </flux:table.cell>
                                <flux:table.cell class="max-w-[16rem]">
                                    @foreach ($row['personTypes'] as $type)
                                        <span class="inline-flex items-center rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 mr-1 mb-1">
                                            {{ $type['name'] }} @if (! $type['active']) <span class="ml-1 text-zinc-400">({{ __('archived') }})</span> @endif
                                        </span>
                                    @endforeach
                                </flux:table.cell>
                                <flux:table.cell align="end" sticky
                                    class="bg-white dark:bg-zinc-900">
                                    <x-basics13::list.row-actions :actions="$row['actions']" prefix="person"
                                        edit-handler="editPerson" />
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <x-basics13::list.empty-state :narrowed="$list['narrowed']" :colspan="8" :message="$list['emptyMessage']">
                                @if ($list['create'] && ! $list['narrowed'])
                                    <flux:modal.trigger name="person-form">
                                        <flux:button size="sm" variant="primary" icon="plus"
                                            x-on:click="createPerson()"
                                            data-test="person-empty-create">
                                            {{ __('New person') }}
                                        </flux:button>
                                    </flux:modal.trigger>
                                @endif
                            </x-basics13::list.empty-state>
                        @endforelse
                    </flux:table.rows>
                </x-basics13::list.table>
            </x-basics13::list.searchable-results>
        @endfragment

        @if ($list['create'])
            <flux:modal name="person-form" aria-labelledby="person-form-heading"
                flyout variant="floating" position="right"
                x-on:close="window.clearForm($el.querySelector('form')); window.resetTrackedForms($el)"
                x-on:cancel.prevent="window.requestTrackedModalClose($el)"
                class="form-drawer max-w-none">
                @include('entities13::persons.form')
            </flux:modal>
        @endif

        <livewire:basics13.advanced-search-modal
            :fields="$searchableFields"
            :filters="$filters"
            :scope="$scope" />

        <x-basics13::list.confirm-modal prefix="person" />
    </div>
</x-layouts::app>