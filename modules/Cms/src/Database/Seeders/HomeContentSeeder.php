<?php

declare(strict_types=1);

namespace Commerce\Cms\Database\Seeders;

use Commerce\Cms\Models\FaqEntry;
use Commerce\Cms\Models\HomepageSection;
use Illuminate\Database\Seeder;

final class HomeContentSeeder extends Seeder
{
    public function run(): void
    {
        foreach (HomepageSection::defaultBlueprint() as $section) {
            HomepageSection::query()->updateOrCreate(
                ['key' => $section['key']],
                $section,
            );
        }

        $entries = [
            [
                'question' => 'How do I place an order?',
                'answer' => 'Browse the shop, add products to your cart, and continue to checkout. You can pay with the methods shown at checkout and will receive an order confirmation by email.',
                'sort_order' => 1,
            ],
            [
                'question' => 'How can I track my order?',
                'answer' => 'After your order ships, we send tracking details to the email used at checkout. You can also review order status from your account if you placed the order while signed in.',
                'sort_order' => 2,
            ],
            [
                'question' => 'What payment methods are supported?',
                'answer' => 'Available payment methods are shown at checkout and may include cards and other gateways configured for this store.',
                'sort_order' => 3,
            ],
            [
                'question' => 'Can I return products?',
                'answer' => 'Yes. Eligible products can be returned according to the store return policy. Start a return from your order confirmation or by contacting support.',
                'sort_order' => 4,
            ],
            [
                'question' => 'How do I contact support?',
                'answer' => 'Use the contact details in the footer, or reach out from your account. Include your order number so we can help faster.',
                'sort_order' => 5,
            ],
        ];

        foreach ($entries as $entry) {
            FaqEntry::query()->updateOrCreate(
                ['question' => $entry['question']],
                [
                    'answer' => $entry['answer'],
                    'sort_order' => $entry['sort_order'],
                    'is_active' => true,
                ],
            );
        }
    }
}
