<?php

declare(strict_types=1);

namespace Commerce\Cms\Http\Requests;

use Commerce\Cms\Models\Popup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class UpsertPopupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $timezone = is_string($this->input('timezone')) && $this->input('timezone') !== ''
            ? $this->input('timezone')
            : 'Asia/Bangkok';

        $title = trim((string) $this->input('title', ''));
        $slug = trim((string) $this->input('slug', ''));
        if ($slug === '' && $title !== '') {
            $slug = Str::slug($title) ?: 'popup';
        }

        $payload = [
            'timezone' => $timezone,
            'slug' => $slug,
            'status' => $this->input('status', Popup::STATUS_DRAFT),
            'popup_type' => $this->input('popup_type', Popup::TYPE_IMAGE),
            'button_target' => $this->input('button_target', Popup::TARGET_SELF),
        ];

        foreach (['start_at', 'end_at'] as $key) {
            $value = $this->input($key);
            if (! is_string($value) || trim($value) === '') {
                $payload[$key] = null;

                continue;
            }

            $payload[$key] = Carbon::parse($value, $timezone)->utc()->toDateTimeString();
        }

        if ($this->input('auto_close') === '' || $this->input('auto_close') === '0') {
            $payload['auto_close'] = null;
        }

        $this->merge($payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Popup|null $popup */
        $popup = $this->route('popup');

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('cms_popups', 'slug')->ignore($popup?->id)->whereNull('deleted_at'),
            ],
            'status' => ['required', Rule::in([Popup::STATUS_DRAFT, Popup::STATUS_PUBLISHED])],
            'priority' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
            'headline' => ['nullable', 'string', 'max:255'],
            'subheadline' => ['nullable', 'string', 'max:2000'],
            'image_media_uuid' => [
                'nullable',
                'uuid',
                Rule::requiredIf($this->input('popup_type') === Popup::TYPE_IMAGE),
            ],
            'button_text' => ['nullable', 'string', 'max:120'],
            'button_url' => ['nullable', 'string', 'max:2048'],
            'button_target' => ['required', Rule::in([Popup::TARGET_SELF, Popup::TARGET_BLANK])],
            'popup_type' => ['required', Rule::in([Popup::TYPE_IMAGE, Popup::TYPE_CONTENT, Popup::TYPE_PROMOTION])],
            'show_delay' => ['nullable', 'integer', 'min:0', 'max:120'],
            'auto_close' => ['nullable', 'integer', 'min:0', 'max:300'],
            'closable' => ['nullable', 'boolean'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'timezone' => ['required', 'timezone'],
        ];
    }
}
