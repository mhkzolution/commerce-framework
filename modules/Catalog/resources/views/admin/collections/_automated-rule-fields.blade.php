@include('catalog::admin.collections._automated-rule-builder', [
    'namePrefix' => $namePrefix ?? 'rules',
    'ruleValues' => $ruleValues ?? [],
    'showGroupMatch' => $showGroupMatch ?? true,
])
