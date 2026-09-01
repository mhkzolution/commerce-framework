<?php

declare(strict_types=1);

namespace Commerce\Contracts\Settings;

interface SiteIdentityServiceInterface
{
    public function name(): string;

    public function logoUrl(?string $variant = null): ?string;

    public function faviconUrl(): ?string;

    public function contactAddress(): ?string;

    public function contactEmail(): ?string;

    public function contactPhone(): ?string;

    /**
     * @return list<array{key: string, label: string, url: string}>
     */
    public function socialLinks(): array;

    /**
     * @return array{
     *     name: string,
     *     logo_url: ?string,
     *     favicon_url: ?string,
     *     contact_address: ?string,
     *     contact_email: ?string,
     *     contact_phone: ?string,
     *     social: list<array{key: string, label: string, url: string}>
     * }
     */
    public function toArray(): array;
}
