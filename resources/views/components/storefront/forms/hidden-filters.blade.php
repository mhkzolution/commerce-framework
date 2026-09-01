@props(['filters', 'except' => []])

@php
    $except = (array) $except;
    $query = collect($filters->toQueryArray())->except($except);
@endphp

@foreach ($query as $key => $value)
    @if (is_array($value))
        @if ($key === 'attributes')
            @foreach ($value as $attributeId => $attributeValue)
                <input type="hidden" name="attributes[{{ $attributeId }}]" value="{{ $attributeValue }}">
            @endforeach
        @else
            @foreach ($value as $item)
                <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
            @endforeach
        @endif
    @else
        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
    @endif
@endforeach
