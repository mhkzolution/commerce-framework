<?php

declare(strict_types=1);

namespace Commerce\Barcode\Validation;

use Commerce\Barcode\DTO\BarcodeQueueItemData;
use Illuminate\Validation\ValidationException;

final class BarcodeQueueItemValidator
{
    private const int MAX_BARCODE_LENGTH = 100;

    private const string CODE128_PATTERN = '/^[\x20-\x7E]+$/';

    /**
     * @throws ValidationException
     */
    public function validate(BarcodeQueueItemData $item): void
    {
        $errors = [];

        if ($item->barcode === '') {
            $errors['barcode'] = [__('barcode::admin.validation.barcode_required')];
        }

        if (strlen($item->barcode) > self::MAX_BARCODE_LENGTH) {
            $errors['barcode'] = [__('barcode::admin.validation.barcode_too_long', ['max' => self::MAX_BARCODE_LENGTH])];
        }

        if ($item->barcode !== '' && ! preg_match(self::CODE128_PATTERN, $item->barcode)) {
            $errors['barcode'] = [__('barcode::admin.validation.barcode_invalid_format')];
        }

        if ($item->title === '') {
            $errors['title'] = [__('barcode::admin.validation.title_required')];
        }

        if ($item->ownerName === '') {
            $errors['owner_name'] = [__('barcode::admin.validation.owner_required')];
        }

        if ($item->quantity < 1 || $item->quantity > 10000) {
            $errors['quantity'] = [__('barcode::admin.validation.quantity_invalid')];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  list<BarcodeQueueItemData>  $queue
     *
     * @throws ValidationException
     */
    public function assertNotDuplicate(BarcodeQueueItemData $item, array $queue): void
    {
        foreach ($queue as $queued) {
            if ($queued->source === $item->source && $queued->barcode === $item->barcode) {
                throw ValidationException::withMessages([
                    'barcode' => [__('barcode::admin.validation.duplicate_barcode')],
                ]);
            }
        }
    }
}
