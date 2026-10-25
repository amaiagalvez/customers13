<?php

namespace Entities13\Transformers;

use Entities13\Models\Entity;
use Illuminate\Database\Eloquent\Model;
use Basics13\Transformers\ListTransformer;
use Entities13\Contracts\EntityIntegration;

/**
 * @extends ListTransformer<Entity>
 */
class EntityListTransformer extends ListTransformer
{
    /**
     * Row key an integration sets when something hangs from the entity, which is what turns the
     * delete action into an archive one. Optional: without it the row is always deletable.
     */
    private const BLOCKS_DELETE = 'blocks_delete';

    public function __construct(
        private readonly ?EntityIntegration $integration = null,
    ) {}

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    protected function columns(Model $record, array $extra): array
    {
        /** @var Entity $record */
        return [
            'id' => $record->id,
            'name' => $record->name,
            'types' => $record->entityTypes->map(fn ($type) => [
                'name' => $type->name_eu,
                'active' => $type->active,
            ])->all(),
            'editPayload' => $this->editPayload($record),
            ...$extra,
        ];
    }

    protected function getDeleteBlockCondition(Model $record): bool
    {
        $extra = $this->integrationRow($record);

        // Block delete if the integration says so (projects) OR if the entity has persons
        return ($extra[self::BLOCKS_DELETE] ?? false) || (int) $record->getAttribute('persons_count') > 0;
    }

    protected function getForceDeleteBlockCondition(Model $record): bool
    {
        return false;
    }

    protected function getForceDeleteBlockHint(Model $record): string
    {
        return '';
    }

    protected function integrationRow(Model $record): array
    {
        return $this->integration?->row($record) ?? [];
    }

    /**
     * The type ids come back as strings because that is what an HTML `<option>` value is: the
     * drawer binds the multi-select with x-model, which compares the option values by identity.
     *
     * @return array{id: int, name: string, notes: string|null, entity_type_ids: list<string>}
     */
    protected function editPayload(Model $record): array
    {
        return [
            ...$record->only(['id', 'name', 'notes']),
            'entity_type_ids' => array_values($record->entityTypes
                ->pluck('id')
                ->map(static fn (int $id): string => (string) $id)
                ->all()),
        ];
    }

    protected function resourceLabel(): string
    {
        return __('Entities');
    }

    protected function resourceKey(): string
    {
        return 'entity';
    }
}
