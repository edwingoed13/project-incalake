<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TagController extends Controller
{
    /**
     * Public listing — used by the admin wizard and the public tour pages.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Tag::query();

        if (!$request->boolean('include_inactive')) {
            $query->where('active', true);
        }

        if ($request->boolean('with_tours_count')) {
            $query->withCount(['tours' => function ($q) {
                $q->where('active', true);
            }]);
        }

        if ($search = trim((string) $request->get('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('slug', 'like', "%{$search}%")
                  ->orWhere('translations', 'like', "%{$search}%");
            });
        }

        $tags = $query->orderBy('slug')->get();

        return response()->json([
            'success' => true,
            'data' => TagResource::collection($tags),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateData($request);

        $tag = Tag::create([
            'slug' => Tag::generateUniqueSlug($data['slug'] ?? ($data['translations']['ES'] ?? 'tag')),
            'translations' => $data['translations'],
            'active' => $data['active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'data' => new TagResource($tag),
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $tag = Tag::findOrFail($id);
        $data = $this->validateData($request, $tag->id);

        if (!empty($data['slug']) && $data['slug'] !== $tag->slug) {
            $tag->slug = Tag::generateUniqueSlug($data['slug'], $tag->id);
        }
        if (isset($data['translations'])) {
            $tag->translations = $data['translations'];
        }
        if (array_key_exists('active', $data)) {
            $tag->active = (bool) $data['active'];
        }
        $tag->save();

        return response()->json([
            'success' => true,
            'data' => new TagResource($tag),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $tag = Tag::findOrFail($id);
        $tag->tours()->detach();
        $tag->delete();

        return response()->json([
            'success' => true,
            'message' => 'Etiqueta eliminada.',
        ]);
    }

    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'slug' => ['nullable', 'string', 'max:120', Rule::unique('tags', 'slug')->ignore($ignoreId)],
            'translations' => ['required', 'array'],
            'translations.*' => ['nullable', 'string', 'max:255'],
            'active' => ['nullable', 'boolean'],
        ]);
    }
}
