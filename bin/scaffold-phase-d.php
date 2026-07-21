<?php

declare(strict_types=1);

/**
 * Scaffold Phase D business modules: CMS, POS, CRM, Marketplace.
 * Run: php bin/scaffold-phase-d.php
 */

$root = dirname(__DIR__);

$modules = [
    'Cms' => [
        'alias' => 'cms',
        'description' => 'Content management — pages and posts',
        'priority' => 30,
        'permissions' => ['cms.page.view', 'cms.page.manage', 'cms.post.view', 'cms.post.manage'],
        'menu' => ['label' => 'CMS', 'icon' => 'document-text', 'route' => 'admin.cms.pages.index', 'permission' => 'cms.page.view', 'order' => 35],
        'entities' => [
            'pages' => ['title', 'slug', 'content', 'status'],
            'posts' => ['title', 'slug', 'excerpt', 'content', 'status', 'published_at'],
        ],
    ],
    'Pos' => [
        'alias' => 'pos',
        'description' => 'Point of sale registers and sessions',
        'priority' => 25,
        'permissions' => ['pos.register.view', 'pos.register.manage', 'pos.session.view'],
        'menu' => ['label' => 'POS', 'icon' => 'device-tablet', 'route' => 'admin.pos.registers.index', 'permission' => 'pos.register.view', 'order' => 15],
        'entities' => [
            'registers' => ['name', 'code', 'location', 'is_active'],
            'sessions' => ['register_id', 'opened_by', 'opened_at', 'closed_at', 'status'],
        ],
    ],
    'Crm' => [
        'alias' => 'crm',
        'description' => 'Customer relationship management — leads and deals',
        'priority' => 30,
        'permissions' => ['crm.lead.view', 'crm.lead.manage', 'crm.deal.view', 'crm.deal.manage'],
        'menu' => ['label' => 'CRM', 'icon' => 'users', 'route' => 'admin.crm.leads.index', 'permission' => 'crm.lead.view', 'order' => 40],
        'entities' => [
            'leads' => ['name', 'email', 'phone', 'source', 'status'],
            'deals' => ['title', 'lead_id', 'amount', 'stage', 'status'],
        ],
    ],
    'Marketplace' => [
        'alias' => 'marketplace',
        'description' => 'Multi-vendor marketplace sellers and commissions',
        'priority' => 30,
        'permissions' => ['marketplace.seller.view', 'marketplace.seller.manage'],
        'menu' => ['label' => 'Marketplace', 'icon' => 'building-storefront', 'route' => 'admin.marketplace.sellers.index', 'permission' => 'marketplace.seller.view', 'order' => 45],
        'entities' => [
            'sellers' => ['name', 'slug', 'email', 'commission_rate', 'status'],
        ],
    ],
];

foreach ($modules as $name => $config) {
    $alias = $config['alias'];
    $ns = "Commerce\\{$name}";
    $dir = "{$root}/modules/{$name}";
    $lower = strtolower($name);

    mkdir("{$dir}/src/Models", 0777, true);
    mkdir("{$dir}/src/Http/Controllers/Admin", 0777, true);
    mkdir("{$dir}/database/migrations", 0777, true);
    mkdir("{$dir}/routes", 0777, true);
    mkdir("{$dir}/resources/views/admin", 0777, true);
    mkdir("{$dir}/config", 0777, true);

    $permissionsJson = json_encode($config['permissions'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    $menuJson = json_encode($config['admin_menu'] ?? $config['menu'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    file_put_contents("{$dir}/module.json", <<<JSON
{
    "name": "{$name}",
    "alias": "{$alias}",
    "description": "{$config['description']}",
    "version": "1.0.0",
    "priority": {$config['priority']},
    "providers": ["Commerce\\\\{$name}\\\\{$name}ServiceProvider"],
    "dependencies": {"hard": [], "soft": ["iam"]},
    "permissions": {$permissionsJson},
    "admin_menu": {$menuJson}
}
JSON);

    file_put_contents("{$dir}/composer.json", json_encode([
        'name' => "commerce/{$alias}",
        'description' => $config['description'],
        'type' => 'library',
        'license' => 'MIT',
        'require' => [
            'php' => '^8.4',
            'commerce/contracts' => '*',
            'commerce/core' => '*',
            'commerce/module-manager' => '*',
            'commerce/support' => '*',
            'illuminate/support' => '^13.0',
            'illuminate/database' => '^13.0',
        ],
        'autoload' => ['psr-4' => ["{$ns}\\" => 'src/']],
        'minimum-stability' => 'dev',
        'prefer-stable' => true,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

    file_put_contents("{$dir}/src/{$name}Module.php", <<<PHP
<?php

declare(strict_types=1);

namespace {$ns};

use Commerce\Contracts\Module\ModuleInterface;

final class {$name}Module implements ModuleInterface
{
    public function getName(): string { return '{$name}'; }
    public function getAlias(): string { return '{$alias}'; }
    public function getVersion(): string { return '1.0.0'; }
    public function getPriority(): int { return {$config['priority']}; }
    public function getDependencies(): array { return []; }
    public function getSoftDependencies(): array { return ['iam']; }
}
PHP);

    file_put_contents("{$dir}/src/{$name}ServiceProvider.php", <<<PHP
<?php

declare(strict_types=1);

namespace {$ns};

use Commerce\Core\Base\BaseModuleServiceProvider;

final class {$name}ServiceProvider extends BaseModuleServiceProvider
{
    public function getModuleAlias(): string { return '{$alias}'; }

    public function boot(): void
    {
        \$this->loadMigrationsFrom(\$this->modulePath('database/migrations'));
        \$this->loadRoutesFrom(\$this->modulePath('routes/web.php'));
        \$this->loadViewsFrom(\$this->modulePath('resources/views'), '{$alias}');
    }
}
PHP);

    file_put_contents("{$dir}/config/{$alias}.php", "<?php\n\ndeclare(strict_types=1);\n\nreturn [\n    'statuses' => ['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'],\n];\n");

    $migrationIndex = 0;
    foreach ($config['entities'] as $table => $fields) {
        $className = str_replace('_', '', ucwords($table, '_'));
        $modelName = rtrim($className, 's');
        if (str_ends_with($className, 'ies')) {
            $modelName = substr($className, 0, -3) . 'y';
        } elseif (str_ends_with($className, 'ses')) {
            $modelName = substr($className, 0, -2);
        } elseif (str_ends_with($className, 's')) {
            $modelName = substr($className, 0, -1);
        }

        $migrationFile = sprintf('%s/database/migrations/2026_07_21_%s_create_%s_table.php', $dir, str_pad((string) (900000 + $migrationIndex++), 6, '0', STR_PAD_LEFT), $table);

        $schemaFields = "            \$table->id();\n            \$table->uuid('uuid')->unique();\n            \$table->unsignedBigInteger('tenant_id')->nullable();\n";
        foreach ($fields as $field) {
            if ($field === 'content') {
                $schemaFields .= "            \$table->longText('{$field}')->nullable();\n";
            } elseif (str_ends_with($field, '_at') || $field === 'published_at') {
                $schemaFields .= "            \$table->timestamp('{$field}')->nullable();\n";
            } elseif (str_ends_with($field, '_id')) {
                $schemaFields .= "            \$table->unsignedBigInteger('{$field}')->nullable();\n";
            } elseif ($field === 'amount' || $field === 'commission_rate') {
                $schemaFields .= "            \$table->unsignedInteger('{$field}')->default(0);\n";
            } elseif ($field === 'is_active') {
                $schemaFields .= "            \$table->boolean('{$field}')->default(true);\n";
            } elseif ($field === 'status') {
                $schemaFields .= "            \$table->string('{$field}', 30)->default('draft');\n";
            } else {
                $schemaFields .= "            \$table->string('{$field}')->nullable();\n";
            }
        }
        $schemaFields .= "            \$table->timestamps();\n            \$table->softDeletes();\n";

        file_put_contents($migrationFile, <<<PHP
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$alias}_{$table}', function (Blueprint \$table): void {
{$schemaFields}        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$alias}_{$table}');
    }
};
PHP);

        $fillable = implode(",\n        ", array_map(static fn ($f) => "'{$f}'", array_merge(['uuid', 'tenant_id'], $fields)));
        file_put_contents("{$dir}/src/Models/{$modelName}.php", <<<PHP
<?php

declare(strict_types=1);

namespace {$ns}\Models;

use Commerce\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class {$modelName} extends Model
{
    use HasUuid;
    use SoftDeletes;

    protected \$table = '{$alias}_{$table}';

    protected \$fillable = [
        {$fillable},
    ];
}
PHP);

        $routeName = "admin.{$alias}.{$table}";
        $controllerName = ucfirst(rtrim($className, 's')) . 'Controller';
        if (str_ends_with($className, 'ies')) {
            $controllerName = substr($className, 0, -3) . 'yController';
        } elseif (str_ends_with($className, 'ses')) {
            $controllerName = substr($className, 0, -2) . 'Controller';
        } else {
            $controllerName = rtrim($className, 's') . 'Controller';
        }

        $permView = $config['permissions'][0] ?? "{$alias}.view";
        $permManage = $config['permissions'][1] ?? "{$alias}.manage";
        $paramName = strtolower($modelName);
        $viewNs = $alias;

        file_put_contents("{$dir}/src/Http/Controllers/Admin/{$controllerName}.php", <<<PHP
<?php

declare(strict_types=1);

namespace {$ns}\Http\Controllers\Admin;

use {$ns}\Models\\{$modelName};
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

final class {$controllerName} extends Controller
{
    public function index(): View
    {
        return view('{$viewNs}::admin.{$table}.index', [
            'items' => {$modelName}::query()->latest()->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('{$viewNs}::admin.{$table}.create', [
            'statuses' => config('{$alias}.statuses', []),
        ]);
    }

    public function store(Request \$request): RedirectResponse
    {
        \$data = \$request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['nullable', 'string', 'max:30'],
            'content' => ['nullable', 'string'],
        ]);

        \$item = {$modelName}::query()->create(array_filter(\$data));

        return redirect()->route('{$routeName}.edit', \$item)->with('status', 'Created.');
    }

    public function edit({$modelName} \${$paramName}): View
    {
        return view('{$viewNs}::admin.{$table}.edit', [
            'item' => \${$paramName},
            'statuses' => config('{$alias}.statuses', []),
        ]);
    }

    public function update(Request \$request, {$modelName} \${$paramName}): RedirectResponse
    {
        \${$paramName}->update(\$request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['nullable', 'string', 'max:30'],
            'content' => ['nullable', 'string'],
        ]));

        return redirect()->route('{$routeName}.edit', \${$paramName})->with('status', 'Saved.');
    }

    public function destroy({$modelName} \${$paramName}): RedirectResponse
    {
        \${$paramName}->delete();

        return redirect()->route('{$routeName}.index')->with('status', 'Deleted.');
    }
}
PHP);

        mkdir("{$dir}/resources/views/admin/{$table}", 0777, true);
        $label = ucfirst(rtrim($table, 's'));
        file_put_contents("{$dir}/resources/views/admin/{$table}/index.blade.php", <<<BLADE
@extends('layouts.admin')
@section('title', '{$label}')
@section('page')
    <x-admin.page title="{$label}" description="Manage {$table}.">
        <x-slot:primaryActions>
            <x-admin.button variant="primary" :href="route('{$routeName}.create')">New</x-admin.button>
        </x-slot:primaryActions>
        <x-admin.table.shell>
            <x-slot:head><tr><th class="px-4 py-3">Name</th><th class="px-4 py-3">Status</th><th class="px-4 py-3 text-right">Actions</th></tr></x-slot:head>
            @forelse (\$items as \$item)
                <tr>
                    <td class="px-4 py-3">{{ \$item->title ?? \$item->name ?? \$item->uuid }}</td>
                    <td class="px-4 py-3">{{ \$item->status ?? '—' }}</td>
                    <td class="px-4 py-3 text-right"><x-admin.button variant="link" :href="route('{$routeName}.edit', \$item)">Edit</x-admin.button></td>
                </tr>
            @empty
                <tr><td colspan="3" class="px-4 py-8 text-center text-muted">No records.</td></tr>
            @endforelse
            @if (\$items->hasPages())<x-slot:pagination>{{ \$items->links() }}</x-slot:pagination>@endif
        </x-admin.table.shell>
    </x-admin.page>
@endsection
BLADE);

        file_put_contents("{$dir}/resources/views/admin/{$table}/create.blade.php", <<<BLADE
@extends('layouts.admin')
@section('title', 'New {$label}')
@section('page')
    <x-admin.form.shell action="{{ route('{$routeName}.store') }}" method="POST" class="max-w-2xl">
        @csrf
        <x-admin.form.section title="Details">
            <input name="name" class="cf-input" placeholder="Name">
            <input name="title" class="cf-input mt-2" placeholder="Title">
            <input name="slug" class="cf-input mt-2" placeholder="Slug">
            <input name="email" type="email" class="cf-input mt-2" placeholder="Email">
            <textarea name="content" class="cf-input mt-2" rows="4" placeholder="Content"></textarea>
            <select name="status" class="cf-input mt-2">@foreach(\$statuses as \$k=>\$v)<option value="{{ \$k }}">{{ \$v }}</option>@endforeach</select>
        </x-admin.form.section>
        <x-slot:actions><x-admin.button variant="primary" type="submit">Create</x-admin.button></x-slot:actions>
    </x-admin.form.shell>
@endsection
BLADE);

        file_put_contents("{$dir}/resources/views/admin/{$table}/edit.blade.php", <<<BLADE
@extends('layouts.admin')
@section('title', 'Edit')
@section('page')
    <x-admin.form.shell action="{{ route('{$routeName}.update', \$item) }}" method="POST" class="max-w-2xl">
        @csrf @method('PUT')
        <x-admin.form.section title="Details">
            <input name="name" value="{{ old('name', \$item->name) }}" class="cf-input" placeholder="Name">
            <input name="title" value="{{ old('title', \$item->title) }}" class="cf-input mt-2" placeholder="Title">
            <input name="slug" value="{{ old('slug', \$item->slug) }}" class="cf-input mt-2" placeholder="Slug">
            <input name="email" value="{{ old('email', \$item->email) }}" type="email" class="cf-input mt-2" placeholder="Email">
            <textarea name="content" class="cf-input mt-2" rows="4">{{ old('content', \$item->content) }}</textarea>
            <select name="status" class="cf-input mt-2">@foreach(\$statuses as \$k=>\$v)<option value="{{ \$k }}" @selected(old('status', \$item->status)==\$k)>{{ \$v }}</option>@endforeach</select>
        </x-admin.form.section>
        <x-slot:actions><x-admin.button variant="primary" type="submit">Save</x-admin.button></x-slot:actions>
    </x-admin.form.shell>
@endsection
BLADE);
    }

    // routes
    $routes = "<?php\n\ndeclare(strict_types=1);\n\nuse Illuminate\Support\Facades\Route;\n";
    foreach ($config['entities'] as $table => $_) {
        $className = str_replace('_', '', ucwords($table, '_'));
        if (str_ends_with($className, 'ies')) {
            $controllerName = substr($className, 0, -3) . 'yController';
            $param = strtolower(substr($className, 0, -3) . 'y');
        } elseif (str_ends_with($className, 'ses')) {
            $controllerName = substr($className, 0, -2) . 'Controller';
            $param = strtolower(substr($className, 0, -2));
        } else {
            $controllerName = rtrim($className, 's') . 'Controller';
            $param = strtolower(rtrim($className, 's'));
        }
        $routes .= "use {$ns}\\Http\\Controllers\\Admin\\{$controllerName};\n";
    }
    $routes .= "\nRoute::middleware(['web', 'auth'])->prefix('admin/{$alias}')->name('admin.{$alias}.')->group(function (): void {\n";
    foreach ($config['entities'] as $table => $_) {
        $className = str_replace('_', '', ucwords($table, '_'));
        if (str_ends_with($className, 'ies')) {
            $controllerName = substr($className, 0, -3) . 'yController';
            $param = strtolower(substr($className, 0, -3) . 'y');
        } elseif (str_ends_with($className, 'ses')) {
            $controllerName = substr($className, 0, -2) . 'Controller';
            $param = strtolower(substr($className, 0, -2));
        } else {
            $controllerName = rtrim($className, 's') . 'Controller';
            $param = strtolower(rtrim($className, 's'));
        }
        $routes .= "    Route::get('/{$table}', [{$controllerName}::class, 'index'])->name('{$table}.index');\n";
        $routes .= "    Route::get('/{$table}/create', [{$controllerName}::class, 'create'])->name('{$table}.create');\n";
        $routes .= "    Route::post('/{$table}', [{$controllerName}::class, 'store'])->name('{$table}.store');\n";
        $routes .= "    Route::get('/{$table}/{{$param}}/edit', [{$controllerName}::class, 'edit'])->name('{$table}.edit');\n";
        $routes .= "    Route::put('/{$table}/{{$param}}', [{$controllerName}::class, 'update'])->name('{$table}.update');\n";
        $routes .= "    Route::delete('/{$table}/{{$param}}', [{$controllerName}::class, 'destroy'])->name('{$table}.destroy');\n";
    }
    $routes .= "});\n";
    file_put_contents("{$dir}/routes/web.php", $routes);

    echo "Scaffolded {$name}\n";
}

// Update commerce.php modules config
$commerceConfig = file_get_contents("{$root}/packages/commerce/core/config/commerce.php");
foreach (array_keys($modules) as $name) {
    $alias = strtolower($name);
    if (! str_contains($commerceConfig, "'{$alias}'")) {
        $commerceConfig = str_replace(
            "'currency' => true,\n    ],",
            "'currency' => true,\n        '{$alias}' => true,\n    ],",
            $commerceConfig,
        );
    }
}
file_put_contents("{$root}/packages/commerce/core/config/commerce.php", $commerceConfig);

// Update root composer.json autoload
$composer = json_decode(file_get_contents("{$root}/composer.json"), true);
foreach (array_keys($modules) as $name) {
    $alias = strtolower($name);
    $composer['require']["commerce/{$alias}"] = '@dev';
    $composer['autoload']['psr-4']["Commerce\\{$name}\\"] = "modules/{$name}/src/";
}
file_put_contents("{$root}/composer.json", json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

echo "Done. Run: composer dump-autoload\n";
