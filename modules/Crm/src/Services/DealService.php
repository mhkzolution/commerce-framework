<?php

declare(strict_types=1);

namespace Commerce\Crm\Services;

use Commerce\Contracts\Event\EventBusInterface;
use Commerce\Core\Base\BaseService;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Commerce\Crm\Events\DealStageChanged;
use Commerce\Crm\Events\DealWon;
use Commerce\Crm\Models\Deal;
use Commerce\Crm\Models\Lead;

final class DealService extends BaseService
{
    public function __construct(private readonly EventBusInterface $eventBus) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Deal
    {
        return Deal::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Deal $deal, array $data): Deal
    {
        $previousStage = $deal->stage;

        if (($data['stage'] ?? $deal->stage) === 'closed_won') {
            $data['status'] = 'won';
        }

        if (($data['stage'] ?? $deal->stage) === 'closed_lost') {
            $data['status'] = 'lost';
        }

        $deal->update($data);
        $deal->refresh();

        if ($previousStage !== $deal->stage) {
            $this->eventBus->dispatch(new DealStageChanged(
                dealUuid: $deal->uuid,
                fromStage: $previousStage,
                toStage: $deal->stage,
            ));
        }

        if ($previousStage !== 'closed_won' && $deal->stage === 'closed_won') {
            $this->eventBus->dispatch(new DealWon(
                leadUuid: $deal->lead?->uuid,
                amount: (int) $deal->amount,
                dealUuid: $deal->uuid,
            ));
        }

        return $deal->fresh(['lead']);
    }

    public function moveStage(Deal $deal, string $stage): Deal
    {
        $allowed = config('crm.deal_stage_transitions.'.$deal->stage, []);

        if (! in_array($stage, $allowed, true)) {
            throw new DomainException("Cannot move deal from [{$deal->stage}] to [{$stage}].");
        }

        return $this->update($deal, ['stage' => $stage]);
    }

    /**
     * @return array<string, list<Deal>>
     */
    public function dealsByStage(): array
    {
        $stages = config('crm.deal_stages', []);
        $grouped = [];

        foreach (array_keys($stages) as $stage) {
            $grouped[$stage] = [];
        }

        $deals = Deal::query()->with('lead')->where('status', 'open')->orderByDesc('updated_at')->get();

        foreach ($deals as $deal) {
            $grouped[$deal->stage][] = $deal;
        }

        return $grouped;
    }

    public function createFromLead(Lead $lead, string $title, int $amount): Deal
    {
        if ($lead->status !== 'qualified') {
            throw new DomainException('Only qualified leads can be converted to deals.');
        }

        return $this->create([
            'title' => $title,
            'lead_id' => $lead->id,
            'amount' => $amount,
            'stage' => 'prospecting',
            'status' => 'open',
        ]);
    }

    public function delete(Deal $deal): void
    {
        $deal->delete();
    }

    public function findOrFail(string $uuid): Deal
    {
        $deal = Deal::query()->where('uuid', $uuid)->first();

        if ($deal === null) {
            throw new EntityNotFoundException("Deal [{$uuid}] not found.");
        }

        return $deal;
    }
}
