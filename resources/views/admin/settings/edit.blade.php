@extends('admin.layout')

@section('title', 'Pengaturan Website')
@section('heading', 'Pengaturan Website')
@section('subheading', 'Atur nama, branding, SEO, media sosial, dan mode perawatan.')

@section('content')
<form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="settings-form">
    @csrf
    @method('PUT')

    <div class="settings-grid">
        <section class="panel">
            <div class="panel__head"><h2>Identitas situs</h2></div>
            <div class="panel__body form-stack">
                <label>
                    <span>Nama website</span>
                    <input class="field" type="text" name="site_name" value="{{ old('site_name', $settings['site_name']) }}" required>
                </label>
                <label>
                    <span>Tagline</span>
                    <input class="field" type="text" name="tagline" value="{{ old('tagline', $settings['tagline']) }}">
                </label>
                <label>
                    <span>Deskripsi (SEO)</span>
                    <textarea class="field" name="description" rows="4">{{ old('description', $settings['description']) }}</textarea>
                </label>
                <label>
                    <span>Keywords</span>
                    <input class="field" type="text" name="keywords" value="{{ old('keywords', $settings['keywords']) }}">
                </label>
                <div class="form-row">
                    <label>
                        <span>Author</span>
                        <input class="field" type="text" name="author" value="{{ old('author', $settings['author']) }}">
                    </label>
                    <label>
                        <span>Creator</span>
                        <input class="field" type="text" name="creator" value="{{ old('creator', $settings['creator']) }}">
                    </label>
                </div>
                <div class="form-row">
                    <label>
                        <span>Theme color</span>
                        <input class="field" type="color" name="theme_color" value="{{ old('theme_color', $settings['theme_color'] ?: '#07080c') }}">
                    </label>
                    <label>
                        <span>Email kontak</span>
                        <input class="field" type="email" name="contact_email" value="{{ old('contact_email', $settings['contact_email']) }}">
                    </label>
                </div>
                <label>
                    <span>Teks footer</span>
                    <input class="field" type="text" name="footer_text" value="{{ old('footer_text', $settings['footer_text']) }}">
                </label>
            </div>
        </section>

        <section class="panel">
            <div class="panel__head"><h2>Logo &amp; favicon</h2></div>
            <div class="panel__body form-stack">
                <div class="asset-preview">
                    <div>
                        <span class="muted">Logo saat ini</span>
                        <div class="asset-box">
                            @if ($logoUrl)
                                <img src="{{ $logoUrl }}" alt="Logo">
                            @else
                                <span class="muted">Belum ada logo</span>
                            @endif
                        </div>
                        <label class="file-label">
                            <span>Upload logo</span>
                            <input type="file" name="logo" accept="image/png,image/jpeg,image/webp,image/svg+xml">
                        </label>
                        @if ($logoUrl)
                            <label class="check-line"><input type="checkbox" name="remove_logo" value="1"> Hapus logo</label>
                        @endif
                    </div>
                    <div>
                        <span class="muted">Favicon saat ini</span>
                        <div class="asset-box asset-box--sm">
                            <img src="{{ $faviconUrl }}" alt="Favicon">
                        </div>
                        <label class="file-label">
                            <span>Upload favicon</span>
                            <input type="file" name="favicon" accept="image/png,image/x-icon,image/svg+xml,image/jpeg,image/webp,.ico">
                        </label>
                        @if (!empty($settings['favicon_path']))
                            <label class="check-line"><input type="checkbox" name="remove_favicon" value="1"> Hapus favicon (kembali default)</label>
                        @endif
                    </div>
                </div>

                <div>
                    <span class="muted">Gambar Open Graph / share</span>
                    <div class="asset-box asset-box--og">
                        <img src="{{ $ogImageUrl }}" alt="OG image">
                    </div>
                    <label class="file-label">
                        <span>Upload OG image</span>
                        <input type="file" name="og_image" accept="image/png,image/jpeg,image/webp">
                    </label>
                    @if (!empty($settings['og_image_path']))
                        <label class="check-line"><input type="checkbox" name="remove_og_image" value="1"> Hapus OG image (kembali default)</label>
                    @endif
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel__head"><h2>Media sosial &amp; verifikasi</h2></div>
            <div class="panel__body form-stack">
                <label>
                    <span>TikTok URL</span>
                    <input class="field" type="url" name="social_tiktok" value="{{ old('social_tiktok', $settings['social_tiktok']) }}">
                </label>
                <label>
                    <span>Saweria URL</span>
                    <input class="field" type="url" name="social_saweria" value="{{ old('social_saweria', $settings['social_saweria']) }}">
                </label>
                <label>
                    <span>SociaBuzz URL</span>
                    <input class="field" type="url" name="social_sociabuzz" value="{{ old('social_sociabuzz', $settings['social_sociabuzz']) }}">
                </label>
                <label>
                    <span>Twitter / X handle</span>
                    <input class="field" type="text" name="twitter_handle" value="{{ old('twitter_handle', $settings['twitter_handle']) }}">
                </label>
                <label>
                    <span>Google site verification</span>
                    <input class="field" type="text" name="google_site_verification" value="{{ old('google_site_verification', $settings['google_site_verification']) }}">
                </label>
            </div>
        </section>

        <section class="panel">
            <div class="panel__head"><h2>Operasional</h2></div>
            <div class="panel__body form-stack">
                <label class="switch-card">
                    <div>
                        <strong>Mode perawatan</strong>
                        <p class="muted">Sembunyikan website publik (admin tetap bisa masuk).</p>
                    </div>
                    <input type="checkbox" name="maintenance_mode" value="1" @checked(old('maintenance_mode', $settings['maintenance_mode']) == '1')>
                </label>
                <label>
                    <span>Pesan perawatan</span>
                    <textarea class="field" name="maintenance_message" rows="3">{{ old('maintenance_message', $settings['maintenance_message']) }}</textarea>
                </label>
                <label class="switch-card">
                    <div>
                        <strong>Izinkan registrasi</strong>
                        <p class="muted">Flag untuk SPA / API branding (bisa dipakai frontend).</p>
                    </div>
                    <input type="checkbox" name="allow_registration" value="1" @checked(old('allow_registration', $settings['allow_registration'] ?? '1') == '1')>
                </label>
            </div>
        </section>
    </div>

    @if ($errors->any())
        <div class="flash flash--error" style="margin-top:1rem">
            <ul style="margin:0;padding-left:1.1rem">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="settings-actions">
        <button class="btn" type="submit">Simpan pengaturan</button>
        <a class="btn btn--ghost" href="/" target="_blank" rel="noopener">Lihat website</a>
    </div>
</form>
@endsection
