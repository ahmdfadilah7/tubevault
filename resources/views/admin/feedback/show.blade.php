@extends('admin.layout')

@section('title', 'Detail Feedback')
@section('heading', 'Detail Feedback')
@section('subheading', 'Informasi lengkap masukan pengguna.')

@section('actions')
    <a class="btn btn--ghost" href="{{ route('admin.feedback.index') }}">← Kembali</a>
@endsection

@section('content')
<section class="panel">
    <div class="panel__body">
        <dl class="detail-grid">
            <div class="detail-row">
                <dt>Subjek</dt>
                <dd>{{ $feedback->subject ?: '—' }}</dd>
            </div>
            <div class="detail-row">
                <dt>Pesan</dt>
                <dd style="white-space:pre-wrap">{{ $feedback->message }}</dd>
            </div>
            <div class="detail-row">
                <dt>Kategori</dt>
                <dd><span class="badge badge--cat">{{ $feedback->category }}</span></dd>
            </div>
            <div class="detail-row">
                <dt>Nama</dt>
                <dd>{{ $feedback->name ?: 'Anonim' }}</dd>
            </div>
            <div class="detail-row">
                <dt>Email</dt>
                <dd>{{ $feedback->email ?: '—' }}</dd>
            </div>
            <div class="detail-row">
                <dt>Akun</dt>
                <dd>{{ $feedback->user?->email ?? 'Tamu (tanpa login)' }}</dd>
            </div>
            <div class="detail-row">
                <dt>IP</dt>
                <dd class="mono">{{ $feedback->ip_address ?: '—' }}</dd>
            </div>
            <div class="detail-row">
                <dt>User agent</dt>
                <dd class="muted" style="font-size:0.85rem">{{ $feedback->user_agent ?: '—' }}</dd>
            </div>
            <div class="detail-row">
                <dt>Dikirim</dt>
                <dd>{{ $feedback->created_at?->format('d M Y H:i') }}</dd>
            </div>
        </dl>

        <form method="POST" action="{{ route('admin.feedback.destroy', $feedback) }}" style="margin-top:1.25rem">
            @csrf
            @method('DELETE')
            <button class="btn btn--danger" type="submit"
                data-confirm="Hapus feedback ini secara permanen?"
                data-confirm-title="Hapus feedback?"
                data-confirm-label="Ya, hapus">
                Hapus feedback
            </button>
        </form>
    </div>
</section>
@endsection
