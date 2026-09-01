@props([
    'post',
    'blogService',
])

<x-storefront.blog.article-card :post="$post" :blog-service="$blogService" featured class="storefront-featured-article" />
