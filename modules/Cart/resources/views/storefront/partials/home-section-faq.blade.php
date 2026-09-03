@if ($faqEntries !== [])
    <section class="storefront-home__section storefront-home-faq" aria-labelledby="home-faq-title" data-home-reveal>
        <x-storefront.layout.page-container variant="narrow" class="storefront-home__inner">
            <header class="storefront-home-section-header storefront-home-section-header--center">
                <div>
                    <h2 id="home-faq-title" class="storefront-home-section-header__title">{{ __('storefront::storefront.home_faq') }}</h2>
                    <p class="storefront-home-section-header__subtitle">{{ __('storefront::storefront.home_faq_subtitle') }}</p>
                </div>
            </header>

            <x-storefront.navigation.accordion :items="$faqEntries" />
        </x-storefront.layout.page-container>
    </section>
@endif
