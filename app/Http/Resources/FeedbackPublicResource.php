<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Feedback */
class FeedbackPublicResource extends JsonResource
{
    private const CATEGORY_LABELS = [
        'suggestion' => 'Saran',
        'criticism' => 'Kritik',
        'bug' => 'Bug',
        'other' => 'Lainnya',
    ];

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category' => $this->category,
            'category_label' => self::CATEGORY_LABELS[$this->category] ?? 'Masukan',
            'subject' => $this->subject,
            'message' => $this->message,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
