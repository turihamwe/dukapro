<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

class SearchQuery
{
    /**
     * Split user input into non-empty words (supports partial / multi-word queries).
     *
     * @return list<string>
     */
    public static function tokens(string $input): array
    {
        $input = trim($input);
        if ($input === '') {
            return [];
        }

        $parts = preg_split('/\s+/u', $input) ?: [];

        return array_values(array_filter($parts, fn ($part) => $part !== ''));
    }

    /**
     * Each token must match at least one column (or related column). Tokens are combined with AND.
     *
     * @param  list<string>  $columns
     * @param  array<string, list<string>>  $relationColumns  e.g. ['business' => ['name', 'slug']]
     */
    public static function applyTokenizedSearch(Builder $query, array $columns, string $term, array $relationColumns = []): void
    {
        $tokens = self::tokens($term);
        if ($tokens === [] || ($columns === [] && $relationColumns === [])) {
            return;
        }

        foreach ($tokens as $token) {
            $like = '%' . self::escapeLike($token) . '%';

            $query->where(function (Builder $group) use ($columns, $like, $relationColumns) {
                $first = true;
                foreach ($columns as $column) {
                    if ($first) {
                        $group->where($column, 'like', $like);
                        $first = false;
                    } else {
                        $group->orWhere($column, 'like', $like);
                    }
                }

                foreach ($relationColumns as $relation => $relColumns) {
                    $group->orWhereHas($relation, function (Builder $rel) use ($relColumns, $like) {
                        $relFirst = true;
                        foreach ($relColumns as $column) {
                            if ($relFirst) {
                                $rel->where($column, 'like', $like);
                                $relFirst = false;
                            } else {
                                $rel->orWhere($column, 'like', $like);
                            }
                        }
                    });
                }
            });
        }
    }

    public static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }
}
