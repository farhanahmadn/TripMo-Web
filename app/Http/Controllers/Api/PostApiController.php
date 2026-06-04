<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Postingan;
use App\Models\FotoPostingan;
use App\Models\RatingPostingan;
use App\Models\User;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Post API untuk mobile app (Flutter). Mengembalikan JSON dengan URL foto absolut.
 */
class PostApiController extends Controller
{
    private CloudinaryService $cloudinary;

    public function __construct(CloudinaryService $cloudinary)
    {
        $this->cloudinary = $cloudinary;
    }

    /** GET /api/posts — feed semua postingan */
    public function index(Request $request)
    {
        $posts = Postingan::with(['user', 'photos'])
            ->withAvg('ratings', 'score')
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return response()->json([
            'data' => collect($posts->items())->map(fn ($p) => $this->postCard($p)),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page'    => $posts->lastPage(),
                'total'        => $posts->total(),
            ],
        ]);
    }

    /** GET /api/posts/{id} — detail postingan */
    public function show($id)
    {
        $post = Postingan::with(['user', 'photos', 'ratings'])->findOrFail($id);

        return response()->json(['data' => $this->postDetail($post)]);
    }

    /** POST /api/posts (auth) — buat postingan */
    public function store(Request $request)
    {
        $request->validate([
            'title'    => 'required|string|max:200',
            'location' => 'nullable|string|max:200',
        ]);

        $destinations = $request->input('destinations', []);
        if (is_string($destinations)) {
            $destinations = json_decode($destinations, true) ?? [];
        }

        $post = Postingan::create([
            'user_id'      => $request->user()->id,
            'title'        => $request->title,
            'location'     => $request->location ?? $request->title,
            'story'        => $request->story,
            'destinations' => json_encode($destinations),
            'total_budget' => $request->total_budget ?? 0,
            'travel_date'  => $request->travel_date,
        ]);

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $foto) {
                $cloud = $this->cloudinary->upload($foto, 'post_photos');
                FotoPostingan::create([
                    'travel_post_id' => $post->id,
                    'file_path'      => $cloud ?: $foto->store('post_photos', 'public'),
                ]);
            }
        }

        $post->load(['user', 'photos']);

        return response()->json([
            'message' => 'Postingan dibuat',
            'data'    => $this->postDetail($post),
        ], 201);
    }

    /** DELETE /api/posts/{id} (auth, pemilik) */
    public function destroy(Request $request, $id)
    {
        $post = Postingan::with('photos')->findOrFail($id);

        if ($post->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Akses ditolak'], 403);
        }

        foreach ($post->photos as $foto) {
            if (CloudinaryService::isCloudinaryUrl($foto->file_path)) {
                $this->cloudinary->delete($foto->file_path);
            } else {
                Storage::disk('public')->delete($foto->file_path);
            }
        }
        $post->delete();

        return response()->json(['message' => 'Postingan dihapus']);
    }

    /** POST /api/posts/{id}/rate (auth) */
    public function rate(Request $request, $id)
    {
        $request->validate(['score' => 'required|integer|min:1|max:5']);

        RatingPostingan::updateOrCreate(
            ['user_id' => $request->user()->id, 'travel_post_id' => $id],
            ['score'   => $request->score]
        );

        return response()->json(['message' => 'Rating disimpan']);
    }

    /** GET /api/users/{id} — profil + postingannya */
    public function userProfile($id)
    {
        $user = User::with(['postingan.photos', 'postingan.ratings'])->findOrFail($id);

        return response()->json([
            'data' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'bio'   => $user->bio,
                'photo' => $user->photo,
                'posts' => $user->postingan->map(fn ($p) => $this->postCard($p)),
            ],
        ]);
    }

    /* ── Transformers ── */

    private function postCard(Postingan $p): array
    {
        return [
            'id'          => $p->id,
            'title'       => $p->title,
            'location'    => $p->location,
            'travel_date' => $p->travel_date,
            'total_budget'=> (int) $p->total_budget,
            'author'      => $p->user->name ?? 'Unknown',
            'author_id'   => $p->user_id,
            'photo'       => $this->photoUrl($p->photos->first()),
            'photos_count'=> $p->photos->count(),
            'rating'      => $p->ratings_avg_score ? round($p->ratings_avg_score, 1) : null,
        ];
    }

    private function postDetail(Postingan $p): array
    {
        $dests = $p->destinations
            ? (is_array($p->destinations) ? $p->destinations : json_decode($p->destinations, true))
            : [];

        return [
            'id'           => $p->id,
            'title'        => $p->title,
            'location'     => $p->location,
            'story'        => $p->story,
            'travel_date'  => $p->travel_date,
            'total_budget' => (int) $p->total_budget,
            'destinations' => $dests,
            'author'       => [
                'id'    => $p->user_id,
                'name'  => $p->user->name ?? 'Unknown',
                'bio'   => $p->user->bio ?? null,
            ],
            'photos'       => $p->photos->map(fn ($f) => $this->photoUrl($f))->filter()->values(),
            'rating'       => $p->relationLoaded('ratings') && $p->ratings->count()
                                ? round($p->ratings->avg('score'), 1)
                                : ($p->ratings_avg_score ? round($p->ratings_avg_score, 1) : null),
        ];
    }

    /** URL foto absolut; null jika file lokal hilang (Flutter tampilkan placeholder sendiri). */
    private function photoUrl(?FotoPostingan $foto): ?string
    {
        if (!$foto) return null;
        $path = $foto->file_path;

        if (CloudinaryService::isCloudinaryUrl($path)) {
            return $path;
        }
        if (Storage::disk('public')->exists($path)) {
            return asset('storage/' . $path);
        }
        return null;
    }
}
