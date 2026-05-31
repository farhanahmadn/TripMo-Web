<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Postingan;

class SmartSystemController extends Controller
{
    // 1. BUDGET INSIGHT
    // Statistik budget dari postingan di lokasi serupa.
    public function budgetInsight(Request $request)
    {
        $q = trim($request->get('q', ''));

        if (strlen($q) < 2) {
            return response()->json(['trips' => 0]);
        }

        $data = DB::table('travel_posts')
            ->where('location', 'like', "%{$q}%")
            ->where('total_budget', '>', 0)
            ->selectRaw('
                COUNT(*)           AS trips,
                AVG(total_budget)  AS avg_budget,
                MIN(total_budget)  AS min_budget,
                MAX(total_budget)  AS max_budget
            ')
            ->first();

        if (!$data || $data->trips == 0) {
            return response()->json(['trips' => 0, 'location' => $q]);
        }

        $samples = DB::table('travel_posts')
            ->where('location', 'like', "%{$q}%")
            ->where('total_budget', '>', 0)
            ->orderBy('total_budget')
            ->pluck('total_budget')
            ->map(fn($v) => (int) $v)
            ->toArray();

        $q1 = $this->percentile($samples, 25);
        $q3 = $this->percentile($samples, 75);

        return response()->json([
            'success'    => true,
            'location'   => $q,
            'trips'      => (int)   $data->trips,
            'avg_budget' => (int)   round($data->avg_budget),
            'min_budget' => (int)   $data->min_budget,
            'max_budget' => (int)   $data->max_budget,
            'q1'         => (int)   $q1,
            'q3'         => (int)   $q3,
        ]);
    }

    // 2. TRENDING DESTINATIONS
    // Lokasi paling banyak diposting dalam 30 hari terakhir.
    public function trending()
    {
        $data = DB::table('travel_posts')
            ->where('created_at', '>=', now()->subDays(30))
            ->select('location', DB::raw('COUNT(*) as count'))
            ->groupBy('location')
            ->orderByDesc('count')
            ->limit(8)
            ->get();

        $enriched = $data->map(function ($row) {
            $avg = DB::table('ratings')
                ->join('travel_posts', 'ratings.travel_post_id', '=', 'travel_posts.id')
                ->where('travel_posts.location', $row->location)
                ->avg('ratings.score');

            return [
                'location'   => $row->location,
                'count'      => (int) $row->count,
                'avg_rating' => $avg ? round($avg, 1) : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $enriched,
        ]);
    }

    // 3. SIMILAR TRIPS
    // Postingan lain di lokasi yang sama, diurutkan berdasarkan rating.
    public function similar($postId)
    {
        $post = Postingan::findOrFail($postId);

        // Fase 1: match lokasi persis
        $similar = Postingan::with(['user', 'photos'])
            ->withAvg('ratings', 'score')
            ->where('location', $post->location)
            ->where('id', '!=', $postId)
            ->orderByDesc('ratings_avg_score')
            ->orderByDesc('created_at')
            ->limit(4)
            ->get();

        // Fase 2: jika kurang dari 4, expand ke LIKE match
        if ($similar->count() < 4) {
            $needed     = 4 - $similar->count();
            $excludeIds = $similar->pluck('id')->push($postId)->toArray();

            $extra = Postingan::with(['user', 'photos'])
                ->withAvg('ratings', 'score')
                ->where('location', 'like', "%{$post->location}%")
                ->whereNotIn('id', $excludeIds)
                ->orderByDesc('ratings_avg_score')
                ->limit($needed)
                ->get();

            $similar = $similar->concat($extra);
        }

        if ($similar->isEmpty()) {
            return response()->json(['success' => true, 'data' => []]);
        }

        return response()->json([
            'success' => true,
            'data'    => $similar->map(fn ($p) => [
                'id'         => $p->id,
                'title'      => $p->title,
                'location'   => $p->location,
                'thumbnail'  => $p->photos->first()
                                   ? \Storage::url($p->photos->first()->file_path)
                                   : null,
                'avg_rating' => round($p->ratings_avg_score ?? 0, 1),
                'author'     => $p->user->name ?? 'Unknown',
                'travel_date'=> $p->travel_date
                                   ? \Carbon\Carbon::parse($p->travel_date)->isoFormat('MMM YYYY')
                                   : null,
                'url'        => route('post.show', $p->id),
            ]),
        ]);
    }

    // 4. PERSONAL TRAVEL STATS
    // Statistik perjalanan user yang sedang login.
    public function personalStats()
    {
        $userId = Auth::id();

        $stats = DB::table('travel_posts')
            ->where('user_id', $userId)
            ->selectRaw('
                COUNT(*)                 AS total_trips,
                COUNT(DISTINCT location) AS unique_cities,
                SUM(total_budget)        AS total_spent,
                AVG(total_budget)        AS avg_per_trip
            ')
            ->first();

        $avgRatingReceived = DB::table('ratings')
            ->join('travel_posts', 'ratings.travel_post_id', '=', 'travel_posts.id')
            ->where('travel_posts.user_id', $userId)
            ->avg('ratings.score');

        $favDest = DB::table('travel_posts')
            ->where('user_id', $userId)
            ->select('location', DB::raw('COUNT(*) as c'))
            ->groupBy('location')
            ->orderByDesc('c')
            ->value('location');

        return response()->json([
            'success' => true,
            'data'    => [
                'total_trips'         => (int)   ($stats->total_trips    ?? 0),
                'unique_cities'       => (int)   ($stats->unique_cities  ?? 0),
                'total_spent'         => (int)   ($stats->total_spent    ?? 0),
                'avg_per_trip'        => (int)   round($stats->avg_per_trip ?? 0),
                'avg_rating_received' =>          round($avgRatingReceived ?? 0, 1),
                'fav_destination'     =>          $favDest,
            ],
        ]);
    }

    // HELPER: Hitung percentile dari array yang sudah terurut
    private function percentile(array $sorted, int $pct): float
    {
        $n = count($sorted);
        if ($n === 0) return 0;
        if ($n === 1) return $sorted[0];

        $index = ($pct / 100) * ($n - 1);
        $lower = (int) floor($index);
        $upper = (int) ceil($index);
        $frac  = $index - $lower;

        return $sorted[$lower] + $frac * ($sorted[$upper] - $sorted[$lower]);
    }
}
