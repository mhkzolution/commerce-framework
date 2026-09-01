<?php

declare(strict_types=1);

namespace Commerce\Crm\Services;

use Commerce\Contracts\Event\EventBusInterface;
use Commerce\Core\Base\BaseService;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Core\Exceptions\EntityNotFoundException;
use Commerce\Crm\Events\LeadCreated;
use Commerce\Crm\Models\Lead;

final class LeadService extends BaseService
{
    public function __construct(private readonly EventBusInterface $eventBus) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Lead
    {
        $lead = Lead::query()->create($data);

        $this->eventBus->dispatch(new LeadCreated(
            leadUuid: $lead->uuid,
            status: $lead->status,
        ));

        return $lead;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Lead $lead, array $data): Lead
    {
        $lead->update($data);

        return $lead->fresh();
    }

    public function qualify(Lead $lead): Lead
    {
        if (! in_array($lead->status, ['new', 'contacted'], true)) {
            throw new DomainException('Only new or contacted leads can be qualified.');
        }

        return $this->update($lead, ['status' => 'qualified']);
    }

    public function delete(Lead $lead): void
    {
        $lead->delete();
    }

    public function findOrFail(string $uuid): Lead
    {
        $lead = Lead::query()->where('uuid', $uuid)->first();

        if ($lead === null) {
            throw new EntityNotFoundException("Lead [{$uuid}] not found.");
        }

        return $lead;
    }
}
