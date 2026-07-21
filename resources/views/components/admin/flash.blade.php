@if (session('status'))
    <div class="cf-flash cf-flash--success mb-4" role="status">
        {{ session('status') }}
    </div>
@endif

@if ($errors->any())
    <div class="cf-flash cf-flash--danger mb-4" role="alert">
        <ul class="list-disc space-y-1 pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
