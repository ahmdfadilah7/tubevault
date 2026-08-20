@extends('admin.layout')

@section('title', 'Ringkasan')
@section('heading', 'Command Center')
@section('subheading')
    Monitoring {{ $siteName }} — aktivitas, konten, dan kesehatan sistem.
@endsection

@section('actions')
    <div class="toolbar">
        <a class="btn btn--ghost btn--sm" href="{{ route('admin.settings.edit') }}">⚙ Settings</a>
        <a class="btn btn--sm" href="/" target="_blank" rel="noopener">Buka site</a>
    </div>
@endsection

@section('content')
<div class="stats stats--rich">
    <a class="stat stat--link" href="{{ route('admin.users.index') }}">
        <div class="stat__label">Pengguna</div>
        <div class="stat__value">{{ number_format($stats['users']) }}</div>
        <div class="stat__meta">+{{ $stats['users_week'] }} minggu ini · {{ $stats['admins'] }} admin</div>
    </a>
    <a class="stat stat--link" href="{{ route('admin.videos.index') }}">
        <div class="stat__label">Media</div>
        <div class="stat__value">{{ number_format($stats['videos']) }}</div>
        <div class="stat__meta">YT {{ $stats['youtube'] }} · SP {{ $stats['spotify'] }} · +{{ $stats['videos_week'] }} minggu ini</div>
    </a>
    <a class="stat stat--link" href="{{ route('admin.playlists.index') }}">
        <div class="stat__label">Playlist</div>
        <div class="stat__value">{{ number_format($stats['playlists']) }}</div>
        <div class="stat__meta">{{ number_format($stats['plays']) }} total plays</div>
    </a>
    <a class="stat stat--link" href="{{ route('admin.feedback.index') }}">
        <div class="stat__label">Feedback</div>
        <div class="stat__value">{{ number_format($stats['feedback']) }}</div>
        <div class="stat__meta">+{{ $stats['feedback_week'] }} minggu ini</div>
    </a>
</div>

<div class="grid-2" style="margin-bottom:1rem">
    <section class="panel">
        <div class="panel__head">
            <h2>Tren aktivitas</h2>
            <span class="muted" style="font-size:.8rem">14 hari terakhir</span>
        </div>
        <div class="panel__body">
            <div class="chart-legend">
                <span><i style="background:#8b5cff"></i> Users</span>
                <span><i style="background:#47bfff"></i> Media</span>
                <span><i style="background:#5ad4a0"></i> Feedback</span>
            </div>
            <div class="chart-shell" id="activityChart"></div>
        </div>
    </section>

    <section class="panel">
        <div class="panel__head">
            <h2>Komposisi konten</h2>
            <span class="muted" style="font-size:.8rem">Media &amp; feedback</span>
        </div>
        <div class="panel__body">
            <div class="grid-2">
                <div>
                    <p class="muted" style="margin:0 0 .5rem;font-size:.78rem;text-transform:uppercase;letter-spacing:.06em">Sumber media</p>
                    <div class="chart-shell chart-shell--sm" id="mediaMixChart"></div>
                </div>
                <div>
                    <p class="muted" style="margin:0 0 .5rem;font-size:.78rem;text-transform:uppercase;letter-spacing:.06em">Feedback</p>
                    <div class="chart-shell chart-shell--sm" id="categoryChart"></div>
                </div>
            </div>
        </div>
    </section>
</div>

<div class="grid-3" style="margin-bottom:1rem">
    <section class="panel">
        <div class="panel__head"><h2>Status sistem</h2></div>
        <div class="panel__body status-list">
            <div class="status-row">
                <span>Maintenance</span>
                @if ($system['maintenance'])
                    <span class="badge badge--warn">ON</span>
                @else
                    <span class="badge badge--ok">OFF</span>
                @endif
            </div>
            <div class="status-row">
                <span>Registrasi</span>
                @if ($system['registration'])
                    <span class="badge badge--ok">Dibuka</span>
                @else
                    <span class="badge badge--user">Ditutup</span>
                @endif
            </div>
            <div class="status-row"><span>Environment</span><span class="mono">{{ $system['env'] }}</span></div>
            <div class="status-row"><span>Laravel</span><span class="mono">{{ $system['laravel'] }}</span></div>
            <div class="status-row"><span>PHP</span><span class="mono">{{ $system['php'] }}</span></div>
        </div>
    </section>

    <section class="panel" style="grid-column: span 2">
        <div class="panel__head">
            <h2>Top plays</h2>
            <a class="btn btn--ghost btn--sm" href="{{ route('admin.videos.index') }}">Semua media</a>
        </div>
        <div class="panel__body">
            <div class="chart-shell" id="topPlaysChart"></div>
        </div>
    </section>
</div>

<div class="grid-3">
    <section class="panel">
        <div class="panel__head">
            <h2>Pengguna baru</h2>
            <a class="btn btn--ghost btn--sm" href="{{ route('admin.users.index') }}">Semua</a>
        </div>
        <div class="table-wrap">
            <table>
                <tbody>
                    @forelse ($recentUsers as $user)
                        @php
                            $userDetail = [
                                'title' => $user->name,
                                'badge' => $user->is_admin ? 'Admin' : 'User',
                                'badgeClass' => $user->is_admin ? 'badge--admin' : '',
                                'fields' => [
                                    ['label' => 'Email', 'value' => $user->email, 'mono' => true],
                                    ['label' => 'Role', 'value' => $user->is_admin ? 'Admin' : 'User'],
                                    ['label' => 'Google', 'value' => $user->google_id ? 'Terhubung' : 'Tidak'],
                                    ['label' => 'Bergabung', 'value' => optional($user->created_at)->format('d M Y H:i')],
                                ],
                            ];
                        @endphp
                        <tr>
                            <td>
                                <button type="button" class="btn btn--ghost btn--sm" style="padding:0;border:0;background:transparent;text-align:left"
                                    data-detail='@json($userDetail, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_UNESCAPED_UNICODE)'>
                                    <strong>{{ $user->name }}</strong><br>
                                    <span class="muted">{{ $user->email }}</span>
                                </button>
                            </td>
                            <td>
                                @if ($user->is_admin)
                                    <span class="badge badge--admin">Admin</span>
                                @else
                                    <span class="badge badge--user">User</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td class="empty">Kosong</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel">
        <div class="panel__head">
            <h2>Feedback masuk</h2>
            <a class="btn btn--ghost btn--sm" href="{{ route('admin.feedback.index') }}">Semua</a>
        </div>
        <div class="table-wrap">
            <table>
                <tbody>
                    @forelse ($recentFeedback as $item)
                        @php
                            $feedbackDetail = [
                                'title' => $item->subject ?: 'Tanpa subjek',
                                'badge' => $item->category,
                                'fields' => [
                                    ['label' => 'Pengirim', 'value' => ($item->name ?: 'Anonim').' <'.($item->email ?: '—').'>'],
                                    ['label' => 'Kategori', 'value' => $item->category],
                                    ['label' => 'Pesan', 'value' => $item->message],
                                    ['label' => 'Waktu', 'value' => optional($item->created_at)->format('d M Y H:i')],
                                ],
                            ];
                        @endphp
                        <tr>
                            <td>
                                <button type="button" class="btn btn--ghost btn--sm" style="padding:0;border:0;background:transparent;text-align:left;width:100%"
                                    data-detail='@json($feedbackDetail, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_UNESCAPED_UNICODE)'>
                                    <strong>{{ $item->subject ?: Str::limit($item->message, 36) }}</strong><br>
                                    <span class="badge badge--cat">{{ $item->category }}</span>
                                    <span class="muted"> · {{ $item->created_at?->diffForHumans() }}</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="empty">Kosong</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="panel">
        <div class="panel__head">
            <h2>Media baru</h2>
            <a class="btn btn--ghost btn--sm" href="{{ route('admin.videos.index') }}">Semua</a>
        </div>
        <div class="table-wrap">
            <table>
                <tbody>
                    @forelse ($recentVideos as $video)
                        @php
                            $videoDetail = [
                                'title' => $video->title ?: 'Tanpa judul',
                                'badge' => $video->media_type,
                                'image' => $video->thumbnail_url,
                                'fields' => [
                                    ['label' => 'Channel', 'value' => $video->channel_name ?: '—'],
                                    ['label' => 'Tipe', 'value' => $video->media_type],
                                    ['label' => 'Pemilik', 'value' => $video->user?->email ?? '—', 'mono' => true],
                                    ['label' => 'Plays', 'value' => (string) ($video->watch_count ?? 0)],
                                    ['label' => 'YouTube ID', 'value' => $video->youtube_id ?: '—', 'mono' => true],
                                    ['label' => 'Spotify ID', 'value' => $video->spotify_id ?: '—', 'mono' => true],
                                ],
                            ];
                        @endphp
                        <tr>
                            <td>
                                <button type="button" class="btn btn--ghost btn--sm" style="padding:0;border:0;background:transparent;text-align:left;width:100%"
                                    data-detail='@json($videoDetail, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_UNESCAPED_UNICODE)'>
                                    <strong>{{ Str::limit($video->title ?: 'Tanpa judul', 40) }}</strong><br>
                                    <span class="badge {{ $video->media_type === 'spotify' ? 'badge--sp' : 'badge--yt' }}">{{ $video->media_type }}</span>
                                    <span class="muted"> · {{ $video->user?->email ?? '—' }}</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="empty">Kosong</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.54.1/dist/apexcharts.min.js"></script>
<script>
(() => {
    const labels = @json($chart['labels']);
    const users = @json($chart['users']);
    const videos = @json($chart['videos']);
    const feedback = @json($chart['feedback']);
    const mediaMix = @json($mediaMix);
    const cats = @json($feedbackByCategory);
    const topLabels = @json($topPlaysLabels);
    const topValues = @json($topPlaysValues);

    const tipTheme = {
        theme: 'dark',
        style: { fontSize: '12px', fontFamily: 'DM Sans, sans-serif' },
    };

    const axisStyle = {
        labels: { style: { colors: '#8b90a5', fontSize: '11px', fontFamily: 'DM Sans, sans-serif' } },
        axisBorder: { show: false },
        axisTicks: { show: false },
    };

    new ApexCharts(document.querySelector('#activityChart'), {
        chart: {
            type: 'area',
            height: 280,
            toolbar: { show: false },
            fontFamily: 'DM Sans, sans-serif',
            animations: { enabled: true, easing: 'easeinout', speed: 700 },
            background: 'transparent',
            dropShadow: { enabled: true, top: 8, left: 0, blur: 12, opacity: 0.18, color: '#8b5cff' },
        },
        colors: ['#8b5cff', '#47bfff', '#5ad4a0'],
        dataLabels: { enabled: false },
        stroke: { curve: 'smooth', width: 2.5 },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.45,
                opacityTo: 0.02,
                stops: [0, 90, 100],
            },
        },
        series: [
            { name: 'Users', data: users },
            { name: 'Media', data: videos },
            { name: 'Feedback', data: feedback },
        ],
        xaxis: { categories: labels, ...axisStyle, tooltip: { enabled: false } },
        yaxis: { ...axisStyle, min: 0, forceNiceScale: true, decimalsInFloat: 0 },
        grid: { borderColor: 'rgba(255,255,255,0.06)', strokeDashArray: 4 },
        legend: { show: false },
        tooltip: { ...tipTheme, shared: true, intersect: false },
        markers: { size: 0, hover: { size: 5 } },
    }).render();

    const mixLabels = ['YouTube', 'Spotify'];
    const mixValues = [mediaMix.youtube || 0, mediaMix.spotify || 0];
    const mixTotal = mixValues.reduce((a, b) => a + b, 0);

    new ApexCharts(document.querySelector('#mediaMixChart'), {
        chart: { type: 'donut', height: 220, fontFamily: 'DM Sans, sans-serif', background: 'transparent' },
        labels: mixTotal ? mixLabels : ['Belum ada'],
        series: mixTotal ? mixValues : [1],
        colors: mixTotal ? ['#ff6b6b', '#1ed760'] : ['#2a2d3a'],
        stroke: { width: 0 },
        plotOptions: {
            pie: {
                donut: {
                    size: '72%',
                    labels: {
                        show: true,
                        name: { show: true, color: '#8b90a5', fontSize: '12px' },
                        value: { show: true, color: '#eef0f6', fontSize: '20px', fontWeight: 700 },
                        total: {
                            show: true,
                            label: 'Total',
                            color: '#8b90a5',
                            formatter: () => String(mixTotal),
                        },
                    },
                },
            },
        },
        legend: { position: 'bottom', labels: { colors: '#8b90a5' }, fontSize: '12px' },
        dataLabels: { enabled: false },
        tooltip: tipTheme,
    }).render();

    const catLabels = Object.keys(cats);
    const catValues = Object.values(cats).map(Number);
    const catTotal = catValues.reduce((a, b) => a + b, 0);

    new ApexCharts(document.querySelector('#categoryChart'), {
        chart: { type: 'polarArea', height: 220, fontFamily: 'DM Sans, sans-serif', background: 'transparent' },
        labels: catTotal ? catLabels : ['Kosong'],
        series: catTotal ? catValues : [1],
        colors: ['#8b5cff', '#47bfff', '#5ad4a0', '#e6b84d', '#f07178'],
        stroke: { colors: ['#12141e'] },
        fill: { opacity: 0.85 },
        yaxis: { show: false },
        legend: { position: 'bottom', labels: { colors: '#8b90a5' }, fontSize: '11px' },
        plotOptions: { polarArea: { rings: { strokeWidth: 0 }, spokes: { strokeWidth: 0 } } },
        tooltip: tipTheme,
    }).render();

    new ApexCharts(document.querySelector('#topPlaysChart'), {
        chart: {
            type: 'bar',
            height: 280,
            toolbar: { show: false },
            fontFamily: 'DM Sans, sans-serif',
            background: 'transparent',
            animations: { enabled: true, speed: 650 },
        },
        series: [{ name: 'Plays', data: topValues.length ? topValues : [0] }],
        plotOptions: {
            bar: {
                horizontal: true,
                borderRadius: 8,
                barHeight: '62%',
                distributed: true,
            },
        },
        colors: ['#8b5cff', '#7a52f0', '#6a45e0', '#47bfff', '#3aa8e8', '#5ad4a0', '#4bc090', '#e6b84d'],
        dataLabels: {
            enabled: true,
            style: { colors: ['#fff'], fontSize: '11px', fontWeight: 600 },
            offsetX: 4,
        },
        xaxis: {
            categories: topLabels.length ? topLabels : ['Belum ada data'],
            ...axisStyle,
        },
        yaxis: { labels: { style: { colors: '#c5c9d8', fontSize: '11px' }, maxWidth: 160 } },
        grid: { borderColor: 'rgba(255,255,255,0.06)', strokeDashArray: 4, xaxis: { lines: { show: true } } },
        legend: { show: false },
        tooltip: tipTheme,
    }).render();
})();
</script>
@endpush
