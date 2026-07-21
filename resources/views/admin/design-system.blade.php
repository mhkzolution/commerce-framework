@extends('layouts.admin')

@section('title', 'Design System')

@section('page')
    <x-admin.page
        title="Design System"
        description="Semantic color tokens for long-term maintainability. Brand colors are identity-only; UI actions use enterprise blue primary."
    >
        <x-slot:breadcrumb>
            <x-admin.breadcrumb :items="[
                ['label' => 'Admin', 'url' => route('admin.design-system')],
                ['label' => 'Design System', 'active' => true],
            ]" />
        </x-slot:breadcrumb>

        <x-slot:secondaryActions>
            <x-admin.button variant="ghost" type="button" onclick="document.getElementById('admin-theme-toggle')?.click()">
                Toggle theme
            </x-admin.button>
        </x-slot:secondaryActions>

        {{-- Philosophy --}}
        <x-admin.card title="Design philosophy">
            <div class="grid gap-4 text-sm text-text-secondary lg:grid-cols-2">
                <div class="space-y-2">
                    <p><strong class="text-text">Usability over branding.</strong> Admin UI is used 8+ hours daily across ecommerce, POS, inventory, CMS, CRM, and reports. Colors reduce eye fatigue and support fast scanning.</p>
                    <p><strong class="text-text">Semantic tokens, not palette names.</strong> Components reference <code class="rounded bg-primary-subtle px-1.5 py-0.5 text-primary-subtle-foreground">--color-primary</code>, never <code class="rounded bg-primary-subtle px-1.5 py-0.5">bg-blue-600</code>.</p>
                </div>
                <div class="space-y-2">
                    <p><strong class="text-text">Brand ≠ UI primary.</strong> Logo red/green stay in marketing and identity. Primary actions use enterprise blue for trust and positive intent.</p>
                    <p><strong class="text-text">White-label ready.</strong> Override tokens in <code class="rounded bg-primary-subtle px-1.5 py-0.5">config/design.php</code> or <code class="rounded bg-primary-subtle px-1.5 py-0.5">.env</code> — no component changes required.</p>
                </div>
            </div>
        </x-admin.card>

        {{-- Brand vs UI --}}
        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <x-admin.card title="Brand colors (identity only)">
                <p class="mb-4 text-sm text-muted">Logo, marketing, splash screens, landing pages — not for Save / Create / Confirm buttons.</p>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <div class="cf-swatch" style="background: var(--color-brand-red)"></div>
                        <p class="mt-2 text-xs font-medium text-text">Brand Red</p>
                        <p class="text-xs text-muted">--color-brand-red · #d72638</p>
                    </div>
                    <div>
                        <div class="cf-swatch" style="background: var(--color-brand-green)"></div>
                        <p class="mt-2 text-xs font-medium text-text">Brand Green</p>
                        <p class="text-xs text-muted">--color-brand-green · #2d9f4f</p>
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-3">
                    <div class="admin-brand-mark">C</div>
                    <span class="text-sm text-muted">Sidebar logo mark uses brand gradient</span>
                </div>
            </x-admin.card>

            <x-admin.card title="Primary color — Enterprise Blue">
                <p class="mb-4 text-sm text-muted">
                    <strong class="text-text">#2563eb</strong> (light) / <strong class="text-text">#3b82f6</strong> (dark).
                    Chosen for trust, positive action, low fatigue, and universal context (ecommerce → POS → CMS).
                    Red is reserved for destructive actions only.
                </p>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <div class="cf-swatch" style="background: var(--color-primary)"></div>
                        <p class="mt-1 text-xs text-muted">Default</p>
                    </div>
                    <div>
                        <div class="cf-swatch" style="background: var(--color-primary-hover)"></div>
                        <p class="mt-1 text-xs text-muted">Hover</p>
                    </div>
                    <div>
                        <div class="cf-swatch" style="background: var(--color-primary-active)"></div>
                        <p class="mt-1 text-xs text-muted">Active</p>
                    </div>
                </div>
            </x-admin.card>
        </div>

        {{-- Surfaces --}}
        <x-admin.card title="Surfaces & text" class="mt-6">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['Background', '--color-background'],
                    ['Surface', '--color-surface'],
                    ['Card', '--color-card'],
                    ['Sidebar', '--color-sidebar'],
                    ['Border', '--color-border'],
                    ['Divider', '--color-divider'],
                    ['Text', '--color-text'],
                    ['Muted', '--color-muted'],
                ] as [$label, $token])
                    <div class="cf-token-row">
                        <span class="text-muted">{{ $label }}</span>
                        <div class="cf-swatch" style="background: var({{ $token }})"></div>
                        <code class="truncate text-xs text-muted">{{ $token }}</code>
                    </div>
                @endforeach
            </div>
        </x-admin.card>

        {{-- Buttons --}}
        <x-admin.card title="Buttons" class="mt-6">
            <p class="mb-4 text-sm text-muted">All states use semantic tokens. Focus ring: <code class="text-xs">--color-ring</code></p>
            <div class="flex flex-wrap gap-3">
                <x-admin.button variant="primary">Primary</x-admin.button>
                <x-admin.button variant="secondary">Secondary</x-admin.button>
                <x-admin.button variant="ghost">Ghost</x-admin.button>
                <x-admin.button variant="outline">Outline</x-admin.button>
                <x-admin.button variant="success">Success</x-admin.button>
                <x-admin.button variant="danger">Danger</x-admin.button>
                <x-admin.button variant="warning">Warning</x-admin.button>
                <x-admin.button variant="link">Link</x-admin.button>
                <x-admin.button variant="primary" disabled>Disabled</x-admin.button>
            </div>
        </x-admin.card>

        {{-- Status badges --}}
        <x-admin.card title="Status & feedback" class="mt-6">
            <div class="flex flex-wrap gap-2">
                <x-admin.badge variant="success">Success</x-admin.badge>
                <x-admin.badge variant="danger">Error</x-admin.badge>
                <x-admin.badge variant="warning">Warning</x-admin.badge>
                <x-admin.badge variant="info">Information</x-admin.badge>
                <x-admin.badge variant="draft">Draft</x-admin.badge>
                <x-admin.badge variant="pending">Pending</x-admin.badge>
                <x-admin.badge variant="published">Published</x-admin.badge>
                <x-admin.badge variant="archived">Archived</x-admin.badge>
            </div>

            <div class="mt-6 grid gap-3 lg:grid-cols-2">
                <div class="cf-flash cf-flash--success">Order saved successfully.</div>
                <div class="cf-flash cf-flash--danger">Payment failed. Please try again.</div>
                <div class="cf-flash cf-flash--warning">Inventory low — 3 items below threshold.</div>
                <div class="cf-flash cf-flash--info">New tax rules apply from next month.</div>
            </div>
        </x-admin.card>

        {{-- Semantic feedback swatches --}}
        <x-admin.card title="Semantic feedback tokens" class="mt-6">
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach (['success', 'danger', 'warning', 'info'] as $semantic)
                    <div>
                        <p class="mb-2 text-xs font-medium uppercase tracking-wide text-muted">{{ $semantic }}</p>
                        <div class="space-y-2">
                            <div class="cf-swatch" style="background: var(--color-{{ $semantic }})"></div>
                            <div class="cf-swatch" style="background: var(--color-{{ $semantic }}-subtle)"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-admin.card>

        {{-- Forms & table demo --}}
        <x-admin.table.shell class="mt-6">
            <x-slot:toolbar>
                <x-admin.table.toolbar>
                    <x-slot:search>
                        <x-admin.search-input placeholder="Filter rows..." class="max-w-sm" />
                    </x-slot:search>
                    <x-slot:filters>
                        <x-admin.button variant="secondary">Filter</x-admin.button>
                    </x-slot:filters>
                </x-admin.table.toolbar>
            </x-slot:toolbar>

            <x-slot:head>
                <tr class="text-left text-xs uppercase tracking-wide text-muted">
                    <th class="px-4 py-3">Entity</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Amount</th>
                </tr>
            </x-slot:head>

            <tr>
                <td class="px-4 py-3 font-medium text-text">Order #1042</td>
                <td class="px-4 py-3"><x-admin.badge variant="published">Published</x-admin.badge></td>
                <td class="px-4 py-3 text-right">฿12,450</td>
            </tr>
            <tr>
                <td class="px-4 py-3 font-medium text-text">Product draft</td>
                <td class="px-4 py-3"><x-admin.badge variant="draft">Draft</x-admin.badge></td>
                <td class="px-4 py-3 text-right">—</td>
            </tr>
            <tr>
                <td class="px-4 py-3 font-medium text-text">Refund request</td>
                <td class="px-4 py-3"><x-admin.badge variant="pending">Pending</x-admin.badge></td>
                <td class="px-4 py-3 text-right">฿890</td>
            </tr>
        </x-admin.table.shell>

        <x-admin.form.shell title="Form controls" description="Inputs use cf-input with focus ring from --color-ring." class="mt-6" action="#" method="POST">
            @csrf
            <x-admin.form.section title="Example fields">
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="block text-sm">
                        <span class="font-medium text-text">Product name</span>
                        <input type="text" class="cf-input mt-1" value="Wireless headset">
                    </label>
                    <label class="block text-sm">
                        <span class="font-medium text-text">SKU</span>
                        <input type="text" class="cf-input mt-1" value="WH-001" disabled>
                    </label>
                </div>
            </x-admin.form.section>
            <x-slot:actions>
                <x-admin.button variant="secondary" type="button">Cancel</x-admin.button>
                <x-admin.button variant="primary" type="submit">Save</x-admin.button>
            </x-slot:actions>
        </x-admin.form.shell>

        {{-- Accessibility --}}
        <x-admin.card title="Accessibility" class="mt-6">
            <ul class="list-disc space-y-2 pl-5 text-sm text-text-secondary">
                <li>Primary on white: contrast ratio ≥ 4.5:1 for text, ≥ 3:1 for large UI elements (WCAG AA).</li>
                <li>Never rely on color alone — pair status badges with labels; destructive actions use both red and clear copy.</li>
                <li>Focus states use <code class="text-xs">--color-ring</code> with 2px offset ring on all interactive elements.</li>
                <li>Dark mode re-tunes luminance — semantic names stay identical; only token values change under <code class="text-xs">.dark</code>.</li>
                <li>Disabled state: 50% opacity + <code class="text-xs">pointer-events: none</code> on buttons.</li>
            </ul>
        </x-admin.card>

        {{-- Usage guidelines --}}
        <x-admin.card title="Usage guidelines" class="mt-6">
            <div class="overflow-x-auto text-sm">
                <table class="min-w-full text-left">
                    <thead>
                        <tr class="border-b border-border text-xs uppercase text-muted">
                            <th class="py-2 pr-4">Do</th>
                            <th class="py-2">Don't</th>
                        </tr>
                    </thead>
                    <tbody class="text-text-secondary">
                        <tr class="border-b border-divider">
                            <td class="py-2 pr-4"><code>bg-primary</code>, <code>text-muted</code>, <code>border-border</code></td>
                            <td class="py-2"><code>bg-blue-600</code>, <code>text-red-500</code></td>
                        </tr>
                        <tr class="border-b border-divider">
                            <td class="py-2 pr-4">Brand red for logo / marketing</td>
                            <td class="py-2">Brand red for primary Save button</td>
                        </tr>
                        <tr class="border-b border-divider">
                            <td class="py-2 pr-4"><code>variant="danger"</code> for Delete</td>
                            <td class="py-2">Red as default CTA</td>
                        </tr>
                        <tr>
                            <td class="py-2 pr-4">Override <code>config/design.php</code> for white-label</td>
                            <td class="py-2">Hardcode hex in Blade templates</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </x-admin.card>
    </x-admin.page>
@endsection
