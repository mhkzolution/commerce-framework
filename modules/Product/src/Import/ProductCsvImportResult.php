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
        public int $warnings = 0,
        public array $messages = [],
        public array $duplicateSkus = [],
        public array $errors = [],
    ) {}

    public function totalProcessed(): int
    {
        return $this->created + $this->updated + $this->skipped;
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    public function withCreated(string $message): self
    {
        return $this->copy(
            created: $this->created + 1,
            messages: [...$this->messages, $message],
        );
    }

    public function withUpdated(string $message): self
    {
        return $this->copy(
            updated: $this->updated + 1,
            messages: [...$this->messages, $message],
        );
    }

    public function withSkipped(string $message): self
    {
        return $this->copy(
            skipped: $this->skipped + 1,
            messages: [...$this->messages, $message],
        );
    }

    public function withMessage(string $message): self
    {
        return $this->copy(
            warnings: $this->warnings + 1,
            messages: [...$this->messages, $message],
        );
    }

    public function withDuplicateSku(string $sku, string $message): self
    {
        return $this->copy(
            skipped: $this->skipped + 1,
            duplicates: $this->duplicates + 1,
            warnings: $this->warnings + 1,
            messages: [...$this->messages, $message],
            duplicateSkus: in_array($sku, $this->duplicateSkus, true)
                ? $this->duplicateSkus
                : [...$this->duplicateSkus, $sku],
        );
    }

    public function withLinkedImages(int $count): self
    {
        return $this->copy(
            linkedImages: $this->linkedImages + $count,
        );
    }

    public function withError(string $message): self
    {
        return $this->copy(
            errors: [...$this->errors, $message],
        );
    }

    /**
     * @param  list<string>|null  $messages
     * @param  list<string>|null  $duplicateSkus
     * @param  list<string>|null  $errors
     */
    private function copy(
        ?int $created = null,
        ?int $updated = null,
        ?int $skipped = null,
        ?int $duplicates = null,
        ?int $linkedImages = null,
        ?int $warnings = null,
        ?array $messages = null,
        ?array $duplicateSkus = null,
        ?array $errors = null,
    ): self {
        return new self(
            created: $created ?? $this->created,
            updated: $updated ?? $this->updated,
            skipped: $skipped ?? $this->skipped,
            duplicates: $duplicates ?? $this->duplicates,
            linkedImages: $linkedImages ?? $this->linkedImages,
            warnings: $warnings ?? $this->warnings,
            messages: $messages ?? $this->messages,
            duplicateSkus: $duplicateSkus ?? $this->duplicateSkus,
            errors: $errors ?? $this->errors,
        );
    }
}
