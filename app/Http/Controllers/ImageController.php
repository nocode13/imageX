<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadImageRequest;
use App\Http\Resources\UserImageResource;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tymon\JWTAuth\Facades\JWTAuth;

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
        $user = JWTAuth::user();

        $file = $request->file('image');

        if (! $file instanceof \Illuminate\Http\UploadedFile) {
            return response()->json(['error' => 'No file uploaded'], 400);
        }

        $userImage = $this->imageService->store($user, $file);

        return (new UserImageResource($userImage))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Получить список своих изображений.
     */
    public function index(): AnonymousResourceCollection
    {
        /** @var \App\Models\User $user */
        $user = JWTAuth::user();

        $images = $this->imageService->index($user);

        return UserImageResource::collection($images);
    }

    /**
     * Получить изображение (signed URL).
     *
     * @unauthenticated
     */
    public function show(int $id): StreamedResponse|JsonResponse
    {
        $userImage = $this->imageService->findReadyImage($id);

        if (! $userImage) {
            return response()->json(['error' => 'Image not found'], 404);
        }

        return $this->streamFromS3(
            $userImage->imageFile->storage_path,
            $userImage->original_name
        );
    }

    /**
     * Получить thumbnail изображения (signed URL).
     *
     * @unauthenticated
     */
    public function thumbnail(int $id): StreamedResponse|JsonResponse
    {
        $userImage = $this->imageService->findReadyImageWithThumbnail($id);

        if (! $userImage) {
            return response()->json(['error' => 'Thumbnail not found'], 404);
        }

        return $this->streamFromS3(
            $userImage->imageFile->thumbnail_path,
            'thumb_' . $userImage->original_name
        );
    }

    /**
     * Удалить изображение.
     *
     * @response array{message: string}
     */
    public function destroy(int $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = JWTAuth::user();

        $deleted = $this->imageService->destroy($user, $id);

        if (! $deleted) {
            return response()->json(['error' => 'Image not found'], 404);
        }

        return response()->json(['message' => 'Image deleted']);
    }

    private function streamFromS3(string $path, string $filename): StreamedResponse
    {
        $filenameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);

        return Storage::disk('s3')->response($path, $filenameWithoutExt . '.webp', [
            'Content-Type' => 'image/webp',
        ]);
    }
}
