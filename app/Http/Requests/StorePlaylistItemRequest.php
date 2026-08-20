<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePlaylistItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'saved_video_id' => [
                'required_without_all:url,shared_saved_video_id',
                'nullable',
                'integer',
                'exists:saved_videos,id',
            ],
            'url' => [
                'required_without_all:saved_video_id,shared_saved_video_id',
                'nullable',
                'string',
                'max:500',
            ],
            'shared_saved_video_id' => [
                'required_without_all:saved_video_id,url',
                'nullable',
                'integer',
                'exists:saved_videos,id',
            ],
            'shared_playlist_id' => [
                'required_with:shared_saved_video_id',
                'nullable',
                'integer',
                'exists:playlists,id',
            ],
        ];
    }
}
