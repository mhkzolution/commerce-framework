<?php

declare(strict_types=1);

namespace Commerce\Product\Models;

use Commerce\Product\Support\SearchNormalizer;
use Illuminate\Database\Eloquent\Model;

class SearchSynonym extends Model
{
    protected $table = 'product_search_synonyms';

    protected $fillable = [
        'from_term',
        'to_term',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $synonym): void {
            $synonym->from_term = SearchNormalizer::textNormalize($synonym->from_term);
            $synonym->to_term = SearchNormalizer::textNormalize($synonym->to_term);
        });
    }
}
