<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadImageRequest;
use App\Http\Resources\UserImageResource;
use App\Jobs\ProcessImageJob;
use App\Models\UserImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * @tags Images
 */
class ImageController extends Controller
{
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

        $tempPath = 'temp/' . Str::uuid() . '.' . $file->getClientOriginalExtension();

        Storage::disk('s3')->put($tempPath, $file->getContent());

        $userImage = UserImage::create([
            'user_id' => $user->id,
            'original_name' => $file->getClientOriginalName(),
            'status' => 'pending',
            'temp_path' => $tempPath,
        ]);

        ProcessImageJob::dispatch($userImage->id);

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

        $images = $user->images()
            ->with('imageFile')
            ->orderByDesc('created_at')
            ->paginate(20);

        return UserImageResource::collection($images);
    }

    /**
     * Получить изображение (signed URL).
     *
     * @unauthenticated
     */
    public function show(int $id): StreamedResponse|JsonResponse
    {
        $userImage = UserImage::with('imageFile')->find($id);

        if (! $userImage || ! $userImage->isReady() || ! $userImage->imageFile) {
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
        $userImage = UserImage::with('imageFile')->find($id);

        if (! $userImage || ! $userImage->isReady() || ! $userImage->imageFile?->thumbnail_path) {
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

        $userImage = UserImage::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (! $userImage) {
            return response()->json(['error' => 'Image not found'], 404);
        }

        if ($userImage->temp_path) {
            Storage::disk('s3')->delete($userImage->temp_path);
        }

        $userImage->delete();

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
