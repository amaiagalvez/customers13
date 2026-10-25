@php
    // The persons panel is only shown when the user has persons.view permission
    $canViewPersons = auth()->user()?->can('viewAny', \Entities13\Models\Person::class) ?? false;
    $persons = $entity->persons()->where('active', true)->with('personTypes')->get();
@endphp

@if ($canViewPersons)
    <div class="mt-6" x-data="{
        createPersonFromPanel() {
            this.$dispatch('modal-show', { name: 'person-from-entity-form' });
        },
        editPerson(personId) {
            window.location.href = '/persons/' + personId + '/edit';
        }
    }">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-zinc-900 dark:text-white">
                {{ __('Persons') }}
            </h3>
            @if (auth()->user()?->can('create', \Entities13\Models\Person::class))
                <flux:button size="sm" variant="primary" icon="plus" x-on:click="createPersonFromPanel()"
                    data-test="entity-persons-panel-create">
                    {{ __('New person') }}
                </flux:button>
            @endif
        </div>

        <div class="max-h-64 divide-y divide-zinc-100 overflow-y-auto rounded-lg border border-zinc-200 border-b-zinc-300/80 bg-white shadow-xs dark:divide-white/5 dark:border-white/10 dark:bg-white/10">
            @forelse ($persons as $person)
                <div class="flex items-center gap-3 px-3 py-2.5 text-sm text-zinc-700 transition-colors hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-white/5">
                    <div class="min-w-0 flex-1">
                        <a href="{{ route('persons.index', ['search' => 'entity_id:' . $entity->id]) }}"
                           class="font-medium truncate block"
                           data-test="entity-person-{{ $person->id }}">
                            {{ $person->getFullName() }}
                        </a>
                        @if ($person->personTypes->isNotEmpty())
                            <div class="flex flex-wrap gap-1 mt-1">
                                @foreach ($person->personTypes as $type)
                                    <span class="inline-flex items-center rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300"
                                          @unless ($type->active)
                                              style="opacity: 0.6;"
                                          @endunless>
                                        {{ $type->name_eu }}
                                        @unless ($type->active)
                                            <span class="ml-1 text-zinc-400">({{ __('basics13::messages.archived_label') }})</span>
                                        @endunless
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    @if (auth()->user()?->can('update', $person))
                        <flux:button size="xs" variant="ghost" icon="pencil-square"
                            aria-label="{{ __('Edit person') }}"
                            x-on:click="editPerson({{ $person->id }})"
                            class="text-zinc-600 hover:text-brand-700 dark:text-zinc-400 dark:hover:text-brand-400"
                            data-test="entity-person-edit-{{ $person->id }}">
                        </flux:button>
                    @endif
                </div>
            @empty
                <p class="px-3 py-4 text-sm text-zinc-500 dark:text-zinc-400 text-center">
                    {{ __('No persons linked to this entity yet.') }}
                </p>
            @endforelse
        </div>

        @if (auth()->user()?->can('create', \Entities13\Models\Person::class))
            <flux:modal name="person-from-entity-form" aria-labelledby="person-from-entity-form-heading"
                flyout variant="floating" position="right"
                x-on:close="window.clearForm($el.querySelector('form')); window.resetTrackedForms($el)"
                x-on:cancel.prevent="window.requestTrackedModalClose($el)"
                class="form-drawer max-w-none">
                @include('entities13::persons.form', ['fixedEntityId' => $entity->id])
            </flux:modal>
        @endif
    </div>
@endif