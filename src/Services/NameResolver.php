<?php

namespace Lyre\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Finds the existing record a typed name refers to, or tells the caller which
 * near-duplicates to confirm against before creating a new one.
 */
class NameResolver
{
    public const EXISTING = 'existing';
    public const SIMILAR = 'similar';
    public const MISSING = 'missing';

    public function __construct(protected Model $model)
    {
    }

    /**
     * @return array{outcome: string, match: ?Model, similar: Collection}
     *   - existing: a record with the same normalised name already exists in scope
     *   - similar:  no exact match, but near-duplicates exist (match is null)
     *   - missing:  nothing close; safe to create
     */
    public function lookup(string $name, array $input = []): array
    {
        $column = $this->column();
        $normalized = normalize_name($name);
        $threshold = (float) config('lyre.search.similar_threshold', 0.75);

        $scored = $this->candidates($name, $input)
            ->map(fn (Model $candidate) => [
                'model' => $candidate,
                'normalized' => normalize_name((string) $candidate->{$column}),
                'similarity' => name_similarity($name, (string) $candidate->{$column}),
            ]);

        $exact = $scored->first(fn (array $row) => $row['normalized'] === $normalized);
        if ($exact) {
            return ['outcome' => self::EXISTING, 'match' => $exact['model'], 'similar' => collect()];
        }

        $similar = $scored
            ->filter(fn (array $row) => $row['similarity'] >= $threshold)
            ->sortByDesc('similarity')
            ->take(5)
            ->values();

        return [
            'outcome' => $similar->isEmpty() ? self::MISSING : self::SIMILAR,
            'match' => null,
            'similar' => $similar,
        ];
    }

    protected function column(): string
    {
        return method_exists($this->model, 'resolveNameColumn') ? $this->model->resolveNameColumn() : 'name';
    }

    /**
     * Scoped records that could be this name. With pg_trgm the database pre-filters
     * to plausible matches; otherwise a bounded scan of the scope is compared in PHP.
     */
    protected function candidates(string $name, array $input): Collection
    {
        $column = $this->column();
        $query = $this->model->newQuery();

        if (method_exists($this->model, 'resolutionScope')) {
            $query = $this->model->resolutionScope($query, $input);
        }

        if (trigram_search_available($this->model->getConnection())) {
            $wrapped = $query->getQuery()->getGrammar()->wrap($this->model->getTable() . '.' . $column);
            $threshold = (float) config('lyre.search.fuzzy_threshold', 0.3);
            $query->where(function ($query) use ($name, $wrapped, $threshold, $column) {
                $query->whereRaw("word_similarity(?, {$wrapped}::text) >= ?", [$name, $threshold])
                    ->orWhereRaw("{$wrapped}::text ilike ?", ['%' . escape_like($name) . '%']);
            });
            // Accent-only differences ("Sciénce") score high on similarity, but also pull in
            // the plain-substring rows so exact normalised matches are never missed.
            return $query->limit(200)->get();
        }

        return $query->limit(1000)->get();
    }
}
