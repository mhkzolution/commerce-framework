@props([
    'indexRoute',
])

<x-admin.button variant="secondary" :href="$indexRoute">{{ __('cms::admin.cancel') }}</x-admin.button>
<x-admin.button variant="secondary" type="submit" name="intent" value="continue">{{ __('cms::admin.save_and_continue') }}</x-admin.button>
<x-admin.button variant="primary" type="submit" name="intent" value="save">{{ __('cms::admin.save') }}</x-admin.button>
