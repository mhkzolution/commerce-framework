@php
    $namePrefix = $namePrefix ?? 'rules';
    $ruleValues = $ruleValues ?? [];
    $showGroupMatch = $showGroupMatch ?? true;

    $activeRuleTypes = [];

    if (filter_var(old($namePrefix . '.on_sale', $ruleValues['on_sale'] ?? false), FILTER_VALIDATE_BOOL)) {
        $activeRuleTypes[] = 'on_sale';
    }

    $categoryIds = old($namePrefix . '.category_ids', $ruleValues['category_ids'] ?? array_filter([$ruleValues['category_id'] ?? null]));

    if ($categoryIds !== [] && $categoryIds !== null) {
        $activeRuleTypes[] = 'categories';
    }

    $brandUuids = old($namePrefix . '.brand_uuids', $ruleValues['brand_uuids'] ?? array_filter([$ruleValues['brand_uuid'] ?? null]));

    if ($brandUuids !== [] && $brandUuids !== null) {
        $activeRuleTypes[] = 'brands';
    }

    $tagIds = old($namePrefix . '.tag_ids', $ruleValues['tag_ids'] ?? array_filter([$ruleValues['tag_id'] ?? null]));

    if ($tagIds !== [] && $tagIds !== null) {
        $activeRuleTypes[] = 'tags';
    }

    if (filled(old($namePrefix . '.price_min', $ruleValues['price_min'] ?? null)) || filled(old($namePrefix . '.price_max', $ruleValues['price_max'] ?? null))) {
        $activeRuleTypes[] = 'price';
    }

    $availableRuleTypes = ['on_sale', 'categories', 'brands', 'tags', 'price'];
@endphp

<div class="rule-builder" data-rule-builder data-name-prefix="{{ $namePrefix }}">
    @if ($showGroupMatch)
        <div class="rule-builder__match">
            <label class="block text-sm font-medium text-text">Products must match</label>
            <select name="{{ $namePrefix }}[match]" class="cf-input mt-1">
                <option value="all" @selected(old($namePrefix . '.match', $ruleValues['match'] ?? 'all') === 'all')>All conditions (AND)</option>
                <option value="any" @selected(old($namePrefix . '.match', $ruleValues['match'] ?? 'all') === 'any')>Any condition (OR)</option>
            </select>
        </div>
    @endif

    <ul class="rule-builder__cards" data-rule-cards>
        @foreach ($activeRuleTypes as $type)
            @include('catalog::admin.collections._automated-rule-card', [
                'namePrefix' => $namePrefix,
                'ruleValues' => $ruleValues,
                'type' => $type,
            ])
        @endforeach
    </ul>

    <p class="rule-builder__empty text-sm text-muted" data-rule-empty @if ($activeRuleTypes !== []) hidden @endif>
        No conditions yet. Add a rule below to start building this group.
    </p>

    <div class="rule-builder__toolbar">
        <select class="cf-input" data-rule-add-select>
            @foreach ($availableRuleTypes as $type)
                <option value="{{ $type }}">
                    @switch($type)
                        @case('on_sale') On sale @break
                        @case('categories') Category @break
                        @case('brands') Brand @break
                        @case('tags') Tag @break
                        @case('price') Price range @break
                    @endswitch
                </option>
            @endforeach
        </select>
        <button type="button" class="cf-btn cf-btn--secondary" data-rule-add>Add condition</button>
    </div>

    @foreach ($availableRuleTypes as $type)
        <template data-rule-card-template="{{ $type }}">
            @include('catalog::admin.collections._automated-rule-card', [
                'namePrefix' => '__PREFIX__',
                'ruleValues' => [],
                'type' => $type,
            ])
        </template>
    @endforeach
</div>
