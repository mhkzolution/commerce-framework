<?php

declare(strict_types=1);

namespace Commerce\ModuleManager\Admin;

use Commerce\Contracts\Admin\AdminGlobalSearchServiceInterface;
use Commerce\Contracts\Admin\AdminNavigationBuilderInterface;
use Commerce\Contracts\Authorization\AuthorizationServiceInterface;
use Commerce\Contracts\Search\SearchQueryInterface;
use Commerce\Cms\Models\Page;
use Commerce\Cms\Models\Post;
use Commerce\Crm\Models\Deal;
use Commerce\Crm\Models\Lead;
use Commerce\Customers\Models\Customer;
use Commerce\Iam\Models\User;
use Commerce\Orders\Models\Order;
use Commerce\Product\Models\Product;
use Commerce\Product\Services\ProductSearchIndexer;
use Illuminate\Support\Facades\Route;

final class AdminGlobalSearchService implements AdminGlobalSearchServiceInterface
{
    public function __construct(
        private readonly AdminNavigationBuilderInterface $navigation,
        private readonly AuthorizationServiceInterface $authorization,
        private readonly SearchQueryInterface $searchQuery,
    ) {}

    public function search(string $query, ?object $user = null, int $limit = 20): array
    {
        $query = trim($query);

        if ($query === '') {
            return array_slice($this->navigation->searchableItems($user), 0, $limit);
        }

        $normalized = strtolower($query);
        $results = [];

        foreach ($this->navigation->searchableItems($user) as $item) {
            if (str_contains($item['keywords'] ?? '', $normalized) || str_contains(strtolower($item['label']), $normalized)) {
                if (! empty($item['url'])) {
                    $results[] = [
                        'label' => $item['label'],
                        'url' => (string) $item['url'],
                        'group' => (string) ($item['group'] ?? 'Navigation'),
                        'keywords' => (string) ($item['keywords'] ?? strtolower($item['label'])),
                    ];
                }
            }
        }

        if ($user !== null && $this->authorization->can($user, 'product.product.view')) {
            $results = array_merge($results, $this->searchProducts($query, $limit));
        }

        if ($user !== null && $this->authorization->can($user, 'orders.order.view')) {
            $results = array_merge($results, $this->searchOrders($query, $limit));
        }

        if ($user !== null && $this->authorization->can($user, 'customers.customer.view')) {
            $results = array_merge($results, $this->searchCustomers($query, $limit));
        }

        if ($user !== null && $this->authorization->can($user, 'iam.user.view')) {
            $results = array_merge($results, $this->searchUsers($query, $limit));
        }

        if ($user !== null && $this->authorization->can($user, 'cms.page.view')) {
            $results = array_merge($results, $this->searchCmsPages($query, $limit));
        }

        if ($user !== null && $this->authorization->can($user, 'cms.post.view')) {
            $results = array_merge($results, $this->searchCmsPosts($query, $limit));
        }

        if ($user !== null && $this->authorization->can($user, 'crm.lead.view')) {
            $results = array_merge($results, $this->searchCrmLeads($query, $limit));
        }

        if ($user !== null && $this->authorization->can($user, 'crm.deal.view')) {
            $results = array_merge($results, $this->searchCrmDeals($query, $limit));
        }

        return array_slice($results, 0, $limit);
    }

    /**
     * @return list<array{label: string, url: string, group: string, keywords: string}>
     */
    private function searchProducts(string $query, int $limit): array
    {
        if (! Route::has('admin.products.edit')) {
            return [];
        }

        $hits = $this->searchQuery->search(ProductSearchIndexer::INDEX, $query, [], 1, $limit)->getHits();
        $uuids = array_values(array_filter(array_map(
            static fn (array $hit): ?string => isset($hit['uuid']) ? (string) $hit['uuid'] : null,
            $hits,
        )));

        if ($uuids === []) {
            return [];
        }

        return Product::query()
            ->whereIn('uuid', $uuids)
            ->get()
            ->map(static fn (Product $product): array => [
                'label' => $product->name,
                'url' => route('admin.products.edit', $product),
                'group' => 'Products',
                'keywords' => strtolower($product->name . ' ' . $product->slug),
            ])
            ->all();
    }

    /**
     * @return list<array{label: string, url: string, group: string, keywords: string}>
     */
    private function searchOrders(string $query, int $limit): array
    {
        if (! Route::has('admin.orders.show')) {
            return [];
        }

        return Order::query()
            ->where(function ($inner) use ($query): void {
                $inner->where('order_number', 'like', "%{$query}%")
                    ->orWhere('customer_email', 'like', "%{$query}%")
                    ->orWhere('customer_name', 'like', "%{$query}%");
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->map(static fn (Order $order): array => [
                'label' => $order->order_number . ' — ' . ($order->customer_name ?? $order->customer_email ?? 'Guest'),
                'url' => route('admin.orders.show', $order),
                'group' => 'Orders',
                'keywords' => strtolower($order->order_number . ' ' . ($order->customer_email ?? '')),
            ])
            ->all();
    }

    /**
     * @return list<array{label: string, url: string, group: string, keywords: string}>
     */
    private function searchCustomers(string $query, int $limit): array
    {
        if (! Route::has('admin.customers.edit')) {
            return [];
        }

        return Customer::query()
            ->where(function ($inner) use ($query): void {
                $inner->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%");
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->map(static fn (Customer $customer): array => [
                'label' => $customer->name . ' (' . $customer->email . ')',
                'url' => route('admin.customers.edit', $customer),
                'group' => 'Customers',
                'keywords' => strtolower($customer->name . ' ' . $customer->email),
            ])
            ->all();
    }

    /**
     * @return list<array{label: string, url: string, group: string, keywords: string}>
     */
    private function searchUsers(string $query, int $limit): array
    {
        if (! Route::has('admin.iam.users.edit')) {
            return [];
        }

        return User::query()
            ->where(function ($inner) use ($query): void {
                $inner->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(static fn (User $user): array => [
                'label' => $user->name . ' (' . $user->email . ')',
                'url' => route('admin.iam.users.edit', $user),
                'group' => 'Users',
                'keywords' => strtolower($user->name . ' ' . $user->email),
            ])
            ->all();
    }

    /**
     * @return list<array{label: string, url: string, group: string, keywords: string}>
     */
    private function searchCmsPages(string $query, int $limit): array
    {
        if (! class_exists(Page::class) || ! Route::has('admin.cms.pages.edit')) {
            return [];
        }

        return Page::query()
            ->where(function ($inner) use ($query): void {
                $inner->where('title', 'like', "%{$query}%")
                    ->orWhere('slug', 'like', "%{$query}%");
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->map(static fn (Page $page): array => [
                'label' => $page->title ?? $page->slug,
                'url' => route('admin.cms.pages.edit', $page),
                'group' => 'CMS Pages',
                'keywords' => strtolower(($page->title ?? '') . ' ' . ($page->slug ?? '')),
            ])
            ->all();
    }

    /**
     * @return list<array{label: string, url: string, group: string, keywords: string}>
     */
    private function searchCmsPosts(string $query, int $limit): array
    {
        if (! class_exists(Post::class) || ! Route::has('admin.cms.posts.edit')) {
            return [];
        }

        return Post::query()
            ->where(function ($inner) use ($query): void {
                $inner->where('title', 'like', "%{$query}%")
                    ->orWhere('slug', 'like', "%{$query}%");
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->map(static fn (Post $post): array => [
                'label' => $post->title ?? $post->slug,
                'url' => route('admin.cms.posts.edit', $post),
                'group' => 'CMS Posts',
                'keywords' => strtolower(($post->title ?? '') . ' ' . ($post->slug ?? '')),
            ])
            ->all();
    }

    /**
     * @return list<array{label: string, url: string, group: string, keywords: string}>
     */
    private function searchCrmLeads(string $query, int $limit): array
    {
        if (! class_exists(Lead::class) || ! Route::has('admin.crm.leads.edit')) {
            return [];
        }

        return Lead::query()
            ->where(function ($inner) use ($query): void {
                $inner->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%");
            })
            ->latest()
            ->limit($limit)
            ->get()
            ->map(static fn (Lead $lead): array => [
                'label' => $lead->name . ($lead->email ? " ({$lead->email})" : ''),
                'url' => route('admin.crm.leads.edit', $lead),
                'group' => 'CRM Leads',
                'keywords' => strtolower(($lead->name ?? '') . ' ' . ($lead->email ?? '')),
            ])
            ->all();
    }

    /**
     * @return list<array{label: string, url: string, group: string, keywords: string}>
     */
    private function searchCrmDeals(string $query, int $limit): array
    {
        if (! class_exists(Deal::class) || ! Route::has('admin.crm.deals.edit')) {
            return [];
        }

        return Deal::query()
            ->where('title', 'like', "%{$query}%")
            ->latest()
            ->limit($limit)
            ->get()
            ->map(static fn (Deal $deal): array => [
                'label' => $deal->title ?? 'Deal',
                'url' => route('admin.crm.deals.edit', $deal),
                'group' => 'CRM Deals',
                'keywords' => strtolower($deal->title ?? ''),
            ])
            ->all();
    }
}
