<?php

declare(strict_types=1);

namespace Commerce\Product\Models;

use Illuminate\Database\Eloquent\Model;

class SearchSynonym extends Model
{
    protected $table = 'product_search_synonyms';

    protected $fillable = [
        'from_term',
        'to_term',
    ];
}
