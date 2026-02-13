<?php

namespace App\Http\Requests;

use App\DTO\UploadImageDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class UploadImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'image' => ['required', 'image', 'mimes:png,jpeg,jpg', 'max:5120'],
        ];
    }

    public function toDTO(): UploadImageDTO
    {
        /** @var array{image: UploadedFile} $validated */
        $validated = $this->validated();

        return UploadImageDTO::fromArray($validated);
    }
}
