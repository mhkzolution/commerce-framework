<?php

declare(strict_types=1);

return [
    'lead_statuses' => [
        'new' => 'New',
        'contacted' => 'Contacted',
        'qualified' => 'Qualified',
        'lost' => 'Lost',
        'won' => 'Won',
    ],
    'deal_stages' => [
        'prospecting' => 'Prospecting',
        'proposal' => 'Proposal',
        'negotiation' => 'Negotiation',
        'closed_won' => 'Closed won',
        'closed_lost' => 'Closed lost',
    ],
    'deal_statuses' => [
        'open' => 'Open',
        'won' => 'Won',
        'lost' => 'Lost',
    ],
    'deal_stage_order' => [
        'prospecting',
        'proposal',
        'negotiation',
        'closed_won',
        'closed_lost',
    ],
    'deal_stage_transitions' => [
        'prospecting' => ['proposal', 'closed_lost'],
        'proposal' => ['negotiation', 'closed_lost'],
        'negotiation' => ['closed_won', 'closed_lost'],
        'closed_won' => [],
        'closed_lost' => [],
    ],
];
