<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadImageRequest;
use App\Http\Resources\UserImageResource;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Images
 */
class ImageController extends Controller
{
    public function __construct(
        private readonly ImageService $imageService
    ) {}

    /**
     * Загрузить изображение.
     *
     * @response 201 UserImageResource
     */
    public function store(UploadImageRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $file = $request->file('image');

        if (! $file instanceof \Illuminate\Http\UploadedFile) {
            return response()->json(['error' => 'No file uploaded'], 400);
        }

        $userImage = $this->imageService->upload($user, $file);

        return (new UserImageResource($userImage))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Получить список своих изображений.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $images = $user->images()
            ->with('imageFile')
            ->orderByDesc('created_at')
            ->paginate(20);

        return UserImageResource::collection($images);
    }

    /**
     * Удалить изображение.
     *
     * @response array{message: string}
     */
    public function destroy(int $id, Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $userImage = $this->imageService->delete($user, $id);

        if (! $userImage) {
            return response()->json(['error' => 'Image not found'], 404);
        }

        return response()->json(['message' => 'Image deleted']);
    }
}
