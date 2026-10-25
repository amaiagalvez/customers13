{{-- The persons of the entity being edited come with the drawer's form (`form.persons`). The panel only
     links to the persons list: creating and editing a person happen there, where the entity is preset. --}}
@php
    // The panel is only shown when the user has persons.view permission
    $canViewPersons = auth()->user()?->can('viewAny', \Entities13\Models\Person::class) ?? false;
@endphp

@if ($canViewPersons)
    <div class="mt-6" x-show="form.id" x-cloak data-test="entity-persons-panel" x-data="{
        personsUrl: @js(route('persons.index')),
        listUrl() {
            const filter = new URLSearchParams({ 'filters[entity_id][operator]': 'equals', 'filters[entity_id][value]': this.form.id });

            return this.personsUrl + '?' + filter.toString();
        },
        createUrl() {
            return this.personsUrl + '?create=1&entity_id=' + this.form.id;
        },
    }">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-zinc-900 dark:text-white">
                {{ __('Persons') }}
            </h3>
            @if (auth()->user()?->can('create', \Entities13\Models\Person::class))
                <flux:button size="sm" variant="primary" icon="plus" x-bind:href="createUrl()"
                    data-test="entity-persons-panel-create">
                    {{ __('New person') }}
                </flux:button>
            @endif
        </div>

        <div class="max-h-64 divide-y divide-zinc-100 overflow-y-auto rounded-lg border border-zinc-200 border-b-zinc-300/80 bg-white shadow-xs dark:divide-white/5 dark:border-white/10 dark:bg-white/10">
            <template x-for="person in form.persons" :key="person.id">
                <div class="flex items-center gap-3 px-3 py-2.5 text-sm text-zinc-700 transition-colors hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-white/5">
                    <div class="min-w-0 flex-1">
                        <a x-bind:href="listUrl()" class="font-medium truncate block"
                           x-bind:data-test="'entity-person-' + person.id" x-text="person.name"></a>
                        <div class="flex flex-wrap gap-1 mt-1" x-show="person.types.length > 0">
                            <template x-for="type in person.types" :key="type.name">
                                <span class="inline-flex items-center rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
                                      x-bind:class="{ 'opacity-60': !type.active }">
                                    <span x-text="type.name"></span>
                                    <span x-show="!type.active" class="ml-1 text-zinc-400">({{ __('basics13::messages.archived_label') }})</span>
                                </span>
                            </template>
                        </div>
                    </div>
                    @if (auth()->user()?->can('update', new \Entities13\Models\Person))
                        <flux:button size="xs" variant="ghost" icon="pencil-square"
                            aria-label="{{ __('Edit person') }}"
                            x-bind:href="listUrl()"
                            class="text-zinc-600 hover:text-brand-700 dark:text-zinc-400 dark:hover:text-brand-400"
                            x-bind:data-test="'entity-person-edit-' + person.id">
                        </flux:button>
                    @endif
                </div>
            </template>
            <p class="px-3 py-4 text-sm text-zinc-500 dark:text-zinc-400 text-center" x-show="!form.persons || form.persons.length === 0">
                {{ __('No persons linked to this entity yet.') }}
            </p>
        </div>
    </div>
@endif
