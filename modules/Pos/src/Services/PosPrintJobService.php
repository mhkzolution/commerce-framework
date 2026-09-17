<?php

declare(strict_types=1);

namespace Commerce\Pos\Services;

use Commerce\Contracts\Settings\SettingQueryServiceInterface;
use Commerce\Core\Base\BaseService;
use Commerce\Core\Exceptions\DomainException;
use Commerce\Orders\Models\Order;
use Commerce\Pos\Enums\PaperWidth;
use Commerce\Pos\Enums\PrintJobType;
use Commerce\Pos\Enums\PrintRenderer;
use Commerce\Pos\Models\PosPrintJob;
use Illuminate\View\View;
use Throwable;

final class PosPrintJobService extends BaseService
{
    public function __construct(
        private readonly PosReceiptService $receiptService,
        private readonly PosSlipSequenceService $slipSequence,
    ) {}

    /**
     * @return array{print_url: string, print_urls: array<string, string>}
     */
    public function printUrls(Order $order): array
    {
        return [
            'print_url' => route('pos.receipt.show', [
                'orderUuid' => $order->uuid,
                'paper_width' => PaperWidth::default()->value,
            ]),
            'print_urls' => [
                PaperWidth::FiftyEight->value => route('pos.receipt.show', [
                    'orderUuid' => $order->uuid,
                    'paper_width' => PaperWidth::FiftyEight->value,
                ]),
                PaperWidth::Eighty->value => route('pos.receipt.show', [
                    'orderUuid' => $order->uuid,
                    'paper_width' => PaperWidth::Eighty->value,
                ]),
            ],
        ];
    }

    public function createSlip(Order $order, PaperWidth $width): PosPrintJob
    {
        if ($order->channel !== 'pos') {
            throw new DomainException('Only POS orders can print a thermal slip.');
        }

        if (trim((string) $order->pos_slip_number) === '') {
            $order->update([
                'pos_slip_number' => $this->slipSequence->allocate($order->created_at),
            ]);
            $order->refresh();
        }

        $order->loadMissing('lineItems');

        $payload = $this->receiptService->build($order);
        $payload['paper_width'] = $width->value;
        $payload['store_name'] = $this->storeName();

        return PosPrintJob::query()->create([
            'order_id' => $order->id,
            'type' => PrintJobType::Slip,
            'template' => 'slip',
            'paper_width' => $width,
            'renderer' => PrintRenderer::BrowserPrint,
            'slip_number' => $order->pos_slip_number,
            'payload' => $payload,
            'status' => 'created',
        ]);
    }

    public function render(PosPrintJob $job): View
    {
        if ($job->type !== PrintJobType::Slip) {
            throw new DomainException('Only slip print jobs can be rendered in this slice.');
        }

        if ($job->renderer !== PrintRenderer::BrowserPrint) {
            throw new DomainException('Only the browser-print renderer is available in this slice.');
        }

        $job->forceFill([
            'status' => 'printed',
            'printed_at' => now(),
        ])->save();

        $payload = is_array($job->payload) ? $job->payload : [];

        return view('pos::print.slip', [
            'job' => $job,
            'slip' => $payload,
            'paperWidth' => $job->paper_width->value,
        ]);
    }

    private function storeName(): string
    {
        try {
            if (app()->bound(SettingQueryServiceInterface::class)) {
                $storeName = app(SettingQueryServiceInterface::class)->get('store.name');
                if (is_string($storeName) && trim($storeName) !== '') {
                    return trim($storeName);
                }
            }
        } catch (Throwable) {
        }

        $appName = config('app.name');

        return is_string($appName) && trim($appName) !== '' ? trim($appName) : 'Store';
    }
}
