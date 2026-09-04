<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Two-factor authentication — {{ config('commerce.name', 'Commerce Framework') }}</title>
    <x-app-fonts />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-gray-100 antialiased">
    <div class="w-full max-w-md rounded-lg bg-white p-8 shadow-sm">
        <h1 class="text-xl font-semibold text-gray-900">Two-factor authentication</h1>
        <p class="mt-1 text-sm text-gray-500">Enter the 6-digit code from your authenticator app.</p>

        @error('code')
            <div class="mt-4 rounded-md bg-red-50 p-3 text-sm text-red-700">
                {{ $message }}
            </div>
        @enderror

        <form method="POST" action="{{ route('admin.login.two-factor.submit') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="code" class="block text-sm font-medium text-gray-700">Authentication code</label>
                <input
                    id="code"
                    name="code"
                    type="text"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    required
                    autofocus
                    class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500"
                >
            </div>
            <button type="submit" class="w-full rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">
                Verify
            </button>
        </form>
    </div>
</body>
</html>
