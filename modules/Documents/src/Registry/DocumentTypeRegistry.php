<?php

declare(strict_types=1);

namespace Commerce\Documents\Registry;

use Commerce\Core\Exceptions\DomainException;
use Commerce\Documents\Contracts\DocumentTypeHandler;
use Commerce\Documents\Enums\DocumentType;

final class DocumentTypeRegistry
{
    /** @var array<string, DocumentTypeHandler> */
    private array $handlers = [];

    public function register(DocumentTypeHandler $handler): void
    {
        if ($handler->prefix() !== $handler->type()->prefix()) {
            throw new DomainException('Document handler prefix must match DocumentType::prefix().');
        }

        if ($handler->numberWidth() < 1) {
            throw new DomainException('Document handler number width must be at least 1.');
        }

        $this->handlers[$handler->type()->value] = $handler;
    }

    public function has(DocumentType $type): bool
    {
        return isset($this->handlers[$type->value]);
    }

    public function handlerFor(DocumentType $type): DocumentTypeHandler
    {
        if (! $this->has($type)) {
            throw new DomainException("No document type handler registered for [{$type->value}].");
        }

        return $this->handlers[$type->value];
    }

    /**
     * @return list<DocumentTypeHandler>
     */
    public function all(): array
    {
        return array_values($this->handlers);
    }
}
