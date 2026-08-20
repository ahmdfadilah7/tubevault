@extends('admin.layout')

@section('title', 'Media')
@section('heading', 'Media')
@section('subheading', 'Semua video/lagu yang disimpan pengguna.')

@section('content')
<section class="panel">
    <div class="panel__head">
        <h2>{{ $videos->total() }} item</h2>
        <form class="toolbar" method="GET" action="{{ route('admin.videos.index') }}">
            <input class="field" type="search" name="q" value="{{ $q }}" placeholder="Cari judul, channel, ID…">
            <select class="field" name="type">
                <option value="">Semua tipe</option>
                <option value="youtube" @selected($type === 'youtube')>YouTube</option>
                <option value="spotify" @selected($type === 'spotify')>Spotify</option>
            </select>
            <button class="btn" type="submit">Filter</button>
        </form>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Media</th>
                    <th>Tipe</th>
                    <th>Pemilik</th>
                    <th>Watch</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($videos as $video)
                    @php
                        $detail = [
                            'title' => $video->title ?: 'Tanpa judul',
                            'badge' => $video->media_type,
                            'image' => $video->thumbnail_url,
                            'fields' => [
                                ['label' => 'Channel', 'value' => $video->channel_name ?: '—'],
                                ['label' => 'Tipe', 'value' => $video->media_type],
                                ['label' => 'Pemilik', 'value' => $video->user?->email ?? '—', 'mono' => true],
                                ['label' => 'Plays', 'value' => number_format($video->watch_count ?? 0)],
                                ['label' => 'Last watch', 'value' => optional($video->last_watched_at)->format('d M Y H:i') ?: '—'],
                                ['label' => 'YouTube ID', 'value' => $video->youtube_id ?: '—', 'mono' => true],
                                ['label' => 'Spotify ID', 'value' => $video->spotify_id ?: '—', 'mono' => true],
                                ['label' => 'Notes', 'value' => $video->notes ?: '—'],
                                ['label' => 'Disimpan', 'value' => optional($video->created_at)->format('d M Y H:i')],
                            ],
                        ];
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $video->title ?: 'Tanpa judul' }}</strong><br>
                            <span class="muted">{{ $video->channel_name ?: '—' }}</span>
                            @if ($video->youtube_id)
                                <br><span class="mono muted">yt: {{ $video->youtube_id }}</span>
                            @endif
                            @if ($video->spotify_id)
                                <br><span class="mono muted">sp: {{ $video->spotify_id }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge {{ $video->media_type === 'spotify' ? 'badge--sp' : 'badge--yt' }}">
                                {{ $video->media_type }}
                            </span>
                        </td>
                        <td class="muted">{{ $video->user?->email ?? '—' }}</td>
                        <td class="mono">{{ number_format($video->watch_count ?? 0) }}</td>
                        <td>
                            <div class="actions">
                                <button type="button" class="btn btn--ghost btn--sm"
                                    data-detail='@json($detail, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_UNESCAPED_UNICODE)'>
                                    Detail
                                </button>
                                <form method="POST" action="{{ route('admin.videos.destroy', $video) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn--danger btn--sm" type="submit"
                                        data-confirm="Hapus media “{{ $video->title ?: 'Tanpa judul' }}” dari perpustakaan? Item playlist terkait ikut terhapus."
                                        data-confirm-title="Hapus media?"
                                        data-confirm-label="Ya, hapus">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty">Tidak ada media.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $videos->links('admin.pagination') }}</div>
</section>
@endsection
