@extends('admin.layout')

@section('title', 'Playlist')
@section('heading', 'Playlist')
@section('subheading', 'Playlist dari seluruh pengguna.')

@section('content')
<section class="panel">
    <div class="panel__head">
        <h2>{{ $playlists->total() }} playlist</h2>
        <form class="toolbar" method="GET" action="{{ route('admin.playlists.index') }}">
            <input class="field" type="search" name="q" value="{{ $q }}" placeholder="Cari nama playlist…">
            <button class="btn" type="submit">Cari</button>
        </form>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Playlist</th>
                    <th>Pemilik</th>
                    <th>Item</th>
                    <th>Dibuat</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($playlists as $playlist)
                    @php
                        $detail = [
                            'title' => $playlist->name,
                            'badge' => $playlist->items_count.' item',
                            'fields' => [
                                ['label' => 'Pemilik', 'value' => $playlist->user?->email ?? '—', 'mono' => true],
                                ['label' => 'Jumlah item', 'value' => (string) $playlist->items_count],
                                ['label' => 'Deskripsi', 'value' => $playlist->description ?: '—'],
                                ['label' => 'Dibuat', 'value' => optional($playlist->created_at)->format('d M Y H:i')],
                                ['label' => 'Update', 'value' => optional($playlist->updated_at)->format('d M Y H:i')],
                            ],
                        ];
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $playlist->name }}</strong>
                            @if ($playlist->description)
                                <br><span class="muted">{{ Str::limit($playlist->description, 80) }}</span>
                            @endif
                        </td>
                        <td class="muted">{{ $playlist->user?->email ?? '—' }}</td>
                        <td class="mono">{{ $playlist->items_count }}</td>
                        <td class="muted">{{ $playlist->created_at?->format('d M Y') }}</td>
                        <td>
                            <div class="actions">
                                <button type="button" class="btn btn--ghost btn--sm"
                                    data-detail='@json($detail, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_UNESCAPED_UNICODE)'>
                                    Detail
                                </button>
                                <form method="POST" action="{{ route('admin.playlists.destroy', $playlist) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn--danger btn--sm" type="submit"
                                        data-confirm="Hapus playlist “{{ $playlist->name }}” beserta seluruh item di dalamnya?"
                                        data-confirm-title="Hapus playlist?"
                                        data-confirm-label="Ya, hapus">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty">Tidak ada playlist.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $playlists->links('admin.pagination') }}</div>
</section>
@endsection
