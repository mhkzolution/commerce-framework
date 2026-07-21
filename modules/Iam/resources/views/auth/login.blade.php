<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login — {{ config('commerce.name', 'Commerce Framework') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-gray-100 antialiased">
    <div class="w-full max-w-md rounded-lg bg-white p-8 shadow-sm">
        <h1 class="text-xl font-semibold text-gray-900">Sign in</h1>
        <p class="mt-1 text-sm text-gray-500">{{ config('commerce.name') }}</p>

        @error('email')
            <div class="mt-4 rounded-md bg-red-50 p-3 text-sm text-red-700">
                {{ $message }}
            </div>
        @enderror

        <form method="POST" action="{{ route('admin.login.submit') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    autofocus
                    class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500"
                >
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500"
                >
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" name="remember" value="1" @checked(old('remember')) class="rounded border-gray-300">
                Remember me
            </label>
            <button type="submit" class="w-full rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">
                Sign in
            </button>
        </form>

        @if (! empty($oauthProviders))
            <div class="mt-6 border-t border-gray-200 pt-6">
                <p class="text-center text-sm text-gray-500">Or continue with</p>
                <div class="mt-4 flex flex-col gap-2">
                    @foreach ($oauthProviders as $provider)
                        <a href="{{ route('admin.login.oauth.redirect', $provider) }}"
                           class="block rounded-md border border-gray-300 px-4 py-2 text-center text-sm font-medium text-gray-700 hover:bg-gray-50">
                            {{ ucfirst($provider) }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</body>
</html>
