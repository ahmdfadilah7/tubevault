<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\Playlist;
use App\Models\SavedVideo;
use App\Models\User;
use App\Services\SiteSettingsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private SiteSettingsService $settings) {}

    public function __invoke(): View
    {
        $now = Carbon::now();
        $start14 = $now->copy()->subDays(13)->startOfDay();

        $stats = [
            'users' => User::query()->count(),
            'admins' => User::query()->where('is_admin', true)->count(),
            'videos' => SavedVideo::query()->count(),
            'youtube' => SavedVideo::query()->where('media_type', 'youtube')->count(),
            'spotify' => SavedVideo::query()->where('media_type', 'spotify')->count(),
            'playlists' => Playlist::query()->count(),
            'feedback' => Feedback::query()->count(),
            'plays' => (int) SavedVideo::query()->sum('watch_count'),
            'users_week' => User::query()->where('created_at', '>=', $now->copy()->subDays(7))->count(),
            'videos_week' => SavedVideo::query()->where('created_at', '>=', $now->copy()->subDays(7))->count(),
            'feedback_week' => Feedback::query()->where('created_at', '>=', $now->copy()->subDays(7))->count(),
            'feedback_open' => Feedback::query()->where('created_at', '>=', $now->copy()->subDays(30))->count(),
        ];

        $usersByDay = $this->dailyCounts(User::query(), $start14);
        $videosByDay = $this->dailyCounts(SavedVideo::query(), $start14);
        $feedbackByDay = $this->dailyCounts(Feedback::query(), $start14);

        $chartLabels = [];
        $chartUsers = [];
        $chartVideos = [];
        $chartFeedback = [];

        for ($i = 0; $i < 14; $i++) {
            $day = $start14->copy()->addDays($i);
            $key = $day->toDateString();
            $chartLabels[] = $day->format('d M');
            $chartUsers[] = $usersByDay[$key] ?? 0;
            $chartVideos[] = $videosByDay[$key] ?? 0;
            $chartFeedback[] = $feedbackByDay[$key] ?? 0;
        }

        $feedbackByCategory = Feedback::query()
            ->select('category', DB::raw('count(*) as total'))
            ->groupBy('category')
            ->pluck('total', 'category')
            ->all();

        $topVideos = SavedVideo::query()
            ->with('user')
            ->orderByDesc('watch_count')
            ->limit(8)
            ->get();

        return view('admin.dashboard', [
            'siteName' => $this->settings->get('site_name'),
            'stats' => $stats,
            'chart' => [
                'labels' => $chartLabels,
                'users' => $chartUsers,
                'videos' => $chartVideos,
                'feedback' => $chartFeedback,
            ],
            'mediaMix' => [
                'youtube' => $stats['youtube'],
                'spotify' => $stats['spotify'],
            ],
            'feedbackByCategory' => $feedbackByCategory,
            'topVideos' => $topVideos,
            'topPlaysLabels' => $topVideos->map(fn ($v) => \Illuminate\Support\Str::limit($v->title ?: 'Tanpa judul', 28))->values()->all(),
            'topPlaysValues' => $topVideos->map(fn ($v) => (int) ($v->watch_count ?? 0))->values()->all(),
            'recentUsers' => User::query()->latest()->limit(6)->get(),
            'recentFeedback' => Feedback::query()->latest()->limit(6)->get(),
            'recentVideos' => SavedVideo::query()->with('user')->latest()->limit(6)->get(),
            'system' => [
                'maintenance' => $this->settings->bool('maintenance_mode'),
                'registration' => $this->settings->bool('allow_registration'),
                'php' => PHP_VERSION,
                'laravel' => app()->version(),
                'env' => config('app.env'),
            ],
        ]);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @return array<string, int>
     */
    private function dailyCounts($query, Carbon $start): array
    {
        $driver = DB::connection()->getDriverName();
        $dateExpr = match ($driver) {
            'sqlite' => "strftime('%Y-%m-%d', created_at)",
            'pgsql' => "to_char(created_at, 'YYYY-MM-DD')",
            default => 'DATE(created_at)',
        };

        return $query
            ->where('created_at', '>=', $start)
            ->selectRaw("{$dateExpr} as day, count(*) as total")
            ->groupBy('day')
            ->pluck('total', 'day')
            ->map(fn ($v) => (int) $v)
            ->all();
    }
}
