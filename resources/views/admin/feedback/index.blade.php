@extends('admin.layout')

@section('title', 'Feedback')
@section('heading', 'Feedback')
@section('subheading', 'Masukan lengkap termasuk identitas pengirim.')

@section('content')
<section class="panel">
    <div class="panel__head">
        <h2>{{ $feedback->total() }} masukan</h2>
        <form class="toolbar" method="GET" action="{{ route('admin.feedback.index') }}">
            <input class="field" type="search" name="q" value="{{ $q }}" placeholder="Cari subjek, pesan, email…">
            <select class="field" name="category">
                <option value="">Semua kategori</option>
                @foreach (['criticism', 'suggestion', 'bug', 'other'] as $cat)
                    <option value="{{ $cat }}" @selected($category === $cat)>{{ $cat }}</option>
                @endforeach
            </select>
            <button class="btn" type="submit">Filter</button>
        </form>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Masukan</th>
                    <th>Pengirim</th>
                    <th>Kategori</th>
                    <th>Waktu</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($feedback as $item)
                    @php
                        $detail = [
                            'title' => $item->subject ?: 'Tanpa subjek',
                            'badge' => $item->category,
                            'fields' => [
                                ['label' => 'Nama', 'value' => $item->name ?: 'Anonim'],
                                ['label' => 'Email', 'value' => $item->email ?: '—', 'mono' => true],
                                ['label' => 'Akun', 'value' => $item->user?->email ?? 'Tamu', 'mono' => true],
                                ['label' => 'Kategori', 'value' => $item->category],
                                ['label' => 'Pesan', 'value' => $item->message],
                                ['label' => 'IP', 'value' => $item->ip_address ?: '—', 'mono' => true],
                                ['label' => 'User agent', 'value' => $item->user_agent ?: '—', 'muted' => true],
                                ['label' => 'Dikirim', 'value' => optional($item->created_at)->format('d M Y H:i')],
                            ],
                        ];
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $item->subject ?: 'Tanpa subjek' }}</strong>
                            <br><span class="muted">{{ Str::limit($item->message, 90) }}</span>
                        </td>
                        <td class="muted">
                            {{ $item->name ?: 'Anonim' }}<br>
                            {{ $item->email ?: '—' }}
                        </td>
                        <td><span class="badge badge--cat">{{ $item->category }}</span></td>
                        <td class="muted">{{ $item->created_at?->format('d M Y H:i') }}</td>
                        <td>
                            <div class="actions">
                                <button type="button" class="btn btn--ghost btn--sm"
                                    data-detail='@json($detail, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_UNESCAPED_UNICODE)'>
                                    Detail
                                </button>
                                <a class="btn btn--ghost btn--sm" href="{{ route('admin.feedback.show', $item) }}">Halaman</a>
                                <form method="POST" action="{{ route('admin.feedback.destroy', $item) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn--danger btn--sm" type="submit"
                                        data-confirm="Hapus feedback dari {{ $item->name ?: 'Anonim' }}? Pesan tidak bisa dipulihkan."
                                        data-confirm-title="Hapus feedback?"
                                        data-confirm-label="Ya, hapus">
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty">Tidak ada feedback.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination">{{ $feedback->links('admin.pagination') }}</div>
</section>
@endsection
