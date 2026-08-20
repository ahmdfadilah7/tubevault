@extends('admin.layout')

@section('title', 'Pengguna')
@section('heading', 'Pengguna')
@section('subheading', 'Kelola akun, status admin, dan hapus data pengguna.')

@section('content')
<section class="panel">
    <div class="panel__head">
        <h2>{{ $users->total() }} pengguna</h2>
        <form class="toolbar" method="GET" action="{{ route('admin.users.index') }}">
            <input class="field" type="search" name="q" value="{{ $q }}" placeholder="Cari nama atau email…">
            <button class="btn" type="submit">Cari</button>
        </form>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Pengguna</th>
                    <th>Role</th>
                    <th>Data</th>
                    <th>Daftar</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    @php
                        $detail = [
                            'title' => $user->name,
                            'badge' => $user->is_admin ? 'Admin' : 'User',
                            'fields' => [
                                ['label' => 'ID', 'value' => '#'.$user->id, 'mono' => true],
                                ['label' => 'Email', 'value' => $user->email, 'mono' => true],
                                ['label' => 'Role', 'value' => $user->is_admin ? 'Admin' : 'User'],
                                ['label' => 'Login Google', 'value' => $user->google_id ? 'Terhubung' : 'Tidak'],
                                ['label' => 'Media', 'value' => (string) $user->saved_videos_count],
                                ['label' => 'Playlist', 'value' => (string) $user->playlists_count],
                                ['label' => 'Feedback', 'value' => (string) $user->feedback_count],
                                ['label' => 'Bergabung', 'value' => optional($user->created_at)->format('d M Y H:i')],
                            ],
                            'note' => $user->id === auth()->id() ? 'Ini adalah akun yang sedang Anda pakai.' : null,
                        ];
                    @endphp
                    <tr>
                        <td class="mono muted">#{{ $user->id }}</td>
                        <td>
                            <strong>{{ $user->name }}</strong><br>
                            <span class="muted">{{ $user->email }}</span>
                            @if ($user->google_id)
                                <br><span class="badge badge--user">Google</span>
                            @endif
                        </td>
                        <td>
                            @if ($user->is_admin)
                                <span class="badge badge--admin">Admin</span>
                            @else
                                <span class="badge badge--user">User</span>
                            @endif
                        </td>
                        <td class="muted">
                            {{ $user->saved_videos_count }} media ·
                            {{ $user->playlists_count }} playlist ·
                            {{ $user->feedback_count }} feedback
                        </td>
                        <td class="muted">{{ $user->created_at?->format('d M Y') }}</td>
                        <td>
                            <div class="actions">
                                <button type="button" class="btn btn--ghost btn--sm"
                                    data-detail='@json($detail, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_UNESCAPED_UNICODE)'>
                                    Detail
                                </button>
                                <form method="POST" action="{{ route('admin.users.toggle-admin', $user) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn--ghost btn--sm" type="submit"
                                        @disabled($user->id === auth()->id())
                                        data-confirm="{{ $user->is_admin ? 'Cabut akses admin dari '.$user->name.'?' : 'Jadikan '.$user->name.' sebagai admin?' }}"
                                        data-confirm-title="{{ $user->is_admin ? 'Cabut admin' : 'Promosi admin' }}"
                                        data-confirm-tone="{{ $user->is_admin ? 'warn' : 'accent' }}"
                                        data-confirm-label="{{ $user->is_admin ? 'Ya, cabut' : 'Ya, jadikan admin' }}">
                                        {{ $user->is_admin ? 'Cabut admin' : 'Jadikan admin' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn--danger btn--sm" type="submit"
                                        @disabled($user->id === auth()->id())
                                        data-confirm="Hapus {{ $user->name }} beserta media, playlist, dan token terkait. Tindakan ini tidak bisa dibatalkan."
                                        data-confirm-title="Hapus pengguna?"
                                        data-confirm-label="Ya, hapus">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty">Tidak ada pengguna.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $users->links('admin.pagination') }}</div>
</section>
@endsection
