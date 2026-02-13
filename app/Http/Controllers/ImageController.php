<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadImageRequest;
use App\Http\Resources\UserImageResource;
use App\Services\ImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ImageController extends Controller
{
    public function __construct(
        private readonly ImageService $imageService
    ) {}

    public function store(UploadImageRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $userImage = $this->imageService->upload($user, $request->toDTO());

        return UserImageResource::make($userImage)
            ->response()
            ->setStatusCode(201);
    }

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

    public function destroy(int $id, Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $this->imageService->delete($user, $id);

        return response()->json(['message' => 'Image deleted']);
    }
}
