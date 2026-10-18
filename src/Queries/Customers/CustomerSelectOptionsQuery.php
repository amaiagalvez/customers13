<?php

namespace entities13\Queries\Entities;

use entities13\Models\Entity;
use Illuminate\Support\Collection;
use Basics13\Queries\ListQueryBase;
use Illuminate\Database\Eloquent\Builder;
use entities13\Contracts\EntityIntegration;

/**
 * @extends ListQueryBase<Entity>
 */
final class EntitySelectOptionsQuery extends ListQueryBase
{
    public function __construct(private readonly EntityIntegration $integration) {}

    /**
     * @return Collection<int, array{id: int, text: string}>
     */
    public function search(string $search): Collection
    {
        $pattern = $this->searchPattern($search);

        return Entity::query()
            ->where('active', true)
            ->when($search !== '', fn(Builder $query) => $query->whereRaw(
                'CUM_entities.name LIKE ? ESCAPE \'' . self::LIKE_ESCAPE . '\'',
                [$pattern],
            ))
            ->orderBy('CUM_entities.name')
            ->orderBy('CUM_entities.id')
            ->limit(self::PER_PAGE)
            ->get(['id', 'name'])
            ->map(fn(Entity $entity): array => [
                'id' => $entity->id,
                'text' => $entity->name,
            ]);
    }

    /**
     * @return class-string<Entity>
     */
    protected function getModelClass(): string
    {
        return Entity::class;
    }

    protected function integration(): EntityIntegration
    {
        return $this->integration;
    }
}
