<?php

declare(strict_types=1);

namespace Commerce\Product\Import;

final readonly class ProductCsvImportResult
{
    /**
     * @param  list<string>  $messages
     * @param  list<string>  $duplicateSkus
     * @param  list<string>  $errors
     */
    public function __construct(
        public int $created = 0,
        public int $updated = 0,
        public int $skipped = 0,
        public int $duplicates = 0,
        public int $linkedImages = 0,
        public array $messages = [],
        public array $duplicateSkus = [],
        public array $errors = [],
    ) {}

    public function totalProcessed(): int
    {
        return $this->created + $this->updated + $this->skipped + $this->duplicates;
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    public function withCreated(string $message): self
    {
        return new self(
            created: $this->created + 1,
            updated: $this->updated,
            skipped: $this->skipped,
            duplicates: $this->duplicates,
            linkedImages: $this->linkedImages,
            messages: [...$this->messages, $message],
            duplicateSkus: $this->duplicateSkus,
            errors: $this->errors,
        );
    }

    public function withUpdated(string $message): self
    {
        return new self(
            created: $this->created,
            updated: $this->updated + 1,
            skipped: $this->skipped,
            duplicates: $this->duplicates,
            linkedImages: $this->linkedImages,
            messages: [...$this->messages, $message],
            duplicateSkus: $this->duplicateSkus,
            errors: $this->errors,
        );
    }

    public function withSkipped(string $message): self
    {
        return new self(
            created: $this->created,
            updated: $this->updated,
            skipped: $this->skipped + 1,
            duplicates: $this->duplicates,
            linkedImages: $this->linkedImages,
            messages: [...$this->messages, $message],
            duplicateSkus: $this->duplicateSkus,
            errors: $this->errors,
        );
    }

    public function withLinkedImages(int $count): self
    {
        return new self(
            created: $this->created,
            updated: $this->updated,
            skipped: $this->skipped,
            duplicates: $this->duplicates,
            linkedImages: $this->linkedImages + $count,
            messages: $this->messages,
            duplicateSkus: $this->duplicateSkus,
            errors: $this->errors,
        );
    }
}
