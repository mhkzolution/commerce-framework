@extends('layouts.admin')
@section('title', 'Pipeline Board')
@section('page')
    <x-admin.page title="Pipeline board" description="Open deals grouped by stage.">
        <x-slot:primaryActions>
            <x-admin.button variant="secondary" :href="route('admin.crm.deals.index')">List view</x-admin.button>
            <x-admin.button variant="primary" :href="route('admin.crm.deals.create')">New deal</x-admin.button>
        </x-slot:primaryActions>

        @session('status')
            <div class="cf-flash cf-flash--success mb-4">{{ $value }}</div>
        @endsession

        <div class="grid gap-4 lg:grid-cols-5">
            @foreach ($stages as $stageKey => $stageLabel)
                <section class="rounded-lg border border-border bg-surface p-3 shadow-sm">
                    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-muted">{{ $stageLabel }}</h2>
                    <div class="space-y-2">
                        @forelse ($dealsByStage[$stageKey] ?? [] as $deal)
                            <article class="rounded border border-border bg-background p-3">
                                <p class="font-medium text-text">{{ $deal->title }}</p>
                                @if ($deal->lead)
                                    <p class="mt-1 text-xs text-muted">{{ $deal->lead->name }}</p>
                                @endif
                                <p class="mt-2 text-sm font-medium">{{ number_format($deal->amount / 100, 2) }}</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <x-admin.button variant="link" :href="route('admin.crm.deals.edit', $deal)">Edit</x-admin.button>
                                    @php $nextStages = config('crm.deal_stage_transitions.' . $deal->stage, []); @endphp
                                    @foreach ($nextStages as $nextStage)
                                        <form method="POST" action="{{ route('admin.crm.deals.stage', $deal) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="stage" value="{{ $nextStage }}">
                                            <button type="submit" class="text-xs text-primary hover:underline">
                                                → {{ $stages[$nextStage] ?? $nextStage }}
                                            </button>
                                        </form>
                                    @endforeach
                                </div>
                            </article>
                        @empty
                            <p class="text-xs text-muted">No deals</p>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
    </x-admin.page>
@endsection
