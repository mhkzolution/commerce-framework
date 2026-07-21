<?php

declare(strict_types=1);

$root = dirname(__DIR__);

$entities = [
    ['Cms', 'cms', 'Post', 'posts', 'cms_posts', ['title', 'slug', 'excerpt', 'content', 'status', 'published_at']],
    ['Pos', 'pos', 'Register', 'registers', 'pos_registers', ['name', 'code', 'location', 'is_active']],
    ['Pos', 'pos', 'Session', 'sessions', 'pos_sessions', ['register_id', 'opened_by', 'opened_at', 'closed_at', 'status']],
    ['Crm', 'crm', 'Lead', 'leads', 'crm_leads', ['name', 'email', 'phone', 'source', 'status']],
    ['Crm', 'crm', 'Deal', 'deals', 'crm_deals', ['title', 'lead_id', 'amount', 'stage', 'status']],
    ['Marketplace', 'marketplace', 'Seller', 'sellers', 'marketplace_sellers', ['name', 'slug', 'email', 'commission_rate', 'status']],
];

foreach ($entities as [$module, $alias, $model, $table, $dbTable, $fields]) {
    $ns = "Commerce\\{$module}";
    $modelDir = "{$root}/modules/{$module}/src/Models";
    $controllerDir = "{$root}/modules/{$module}/src/Http/Controllers/Admin";
    mkdir($modelDir, 0777, true);
    mkdir($controllerDir, 0777, true);

    $fillable = implode(",\n        ", array_map(static fn ($f) => "'{$f}'", array_merge(['uuid', 'tenant_id'], $fields)));
    $param = lcfirst($model);
    $route = "admin.{$alias}.{$table}";

    $modelFile = "{$modelDir}/{$model}.php";
    if (! is_file($modelFile)) {
        file_put_contents($modelFile, <<<PHP
<?php

declare(strict_types=1);

namespace {$ns}\Models;

use Commerce\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class {$model} extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected \$table = '{$dbTable}';

    protected \$fillable = [
        {$fillable},
    ];
}
PHP);
        echo "Created {$modelFile}\n";
    }

    $controllerFile = "{$controllerDir}/{$model}Controller.php";
    if (! is_file($controllerFile)) {
        file_put_contents($controllerFile, <<<PHP
<?php

declare(strict_types=1);

namespace {$ns}\Http\Controllers\Admin;

use {$ns}\Models\\{$model};
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class {$model}Controller extends Controller
{
    public function index(): View
    {
        return view('{$alias}::admin.{$table}.index', [
            'items' => {$model}::query()->latest()->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('{$alias}::admin.{$table}.create', [
            'statuses' => config('{$alias}.statuses', []),
        ]);
    }

    public function store(Request \$request): RedirectResponse
    {
        \$item = {$model}::query()->create(\$request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['nullable', 'string', 'max:30'],
            'content' => ['nullable', 'string'],
        ]));

        return redirect()->route('{$route}.edit', \$item)->with('status', 'Created.');
    }

    public function edit({$model} \${$param}): View
    {
        return view('{$alias}::admin.{$table}.edit', [
            'item' => \${$param},
            'statuses' => config('{$alias}.statuses', []),
        ]);
    }

    public function update(Request \$request, {$model} \${$param}): RedirectResponse
    {
        \${$param}->update(\$request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['nullable', 'string', 'max:30'],
            'content' => ['nullable', 'string'],
        ]));

        return redirect()->route('{$route}.edit', \${$param})->with('status', 'Saved.');
    }

    public function destroy({$model} \${$param}): RedirectResponse
    {
        \${$param}->delete();

        return redirect()->route('{$route}.index')->with('status', 'Deleted.');
    }
}
PHP);
        echo "Created {$controllerFile}\n";
    }
}

echo "Complete.\n";
