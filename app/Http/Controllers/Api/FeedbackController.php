<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFeedbackRequest;
use App\Http\Resources\FeedbackPublicResource;
use App\Models\Feedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function index(): JsonResponse
    {
        $items = Feedback::query()
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return FeedbackPublicResource::collection($items)->response();
    }

    public function store(StoreFeedbackRequest $request): JsonResponse
    {
        $feedback = Feedback::query()->create([
            'user_id' => $request->user()?->id,
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'subject' => $request->validated('subject'),
            'message' => $request->validated('message'),
            'category' => $request->validated('category', 'suggestion'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Terima kasih, kritik dan saran Anda sudah kami terima.',
            'data' => [
                'id' => $feedback->id,
                'created_at' => $feedback->created_at?->toIso8601String(),
            ],
        ], 201);
    }
}
