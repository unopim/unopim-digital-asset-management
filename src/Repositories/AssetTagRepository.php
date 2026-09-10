<?php

namespace Webkul\DAM\Repositories;

use Illuminate\Database\QueryException;
use Webkul\Core\Eloquent\Repository;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\Tag;

class AssetTagRepository extends Repository
{
    protected $assets = [];

    public function model(): string
    {
        return Tag::class;
    }

    public function create(array $data)
    {
        $asset = $this->model->create($data);

        return $asset;
    }

    public function update(array $data, $id, $asset = 'id')
    {
        $asset = $this->find($id);

        $asset->update($data);

        return $asset;
    }

    public function getTagsByAssetId(int $asset_Id)
    {
        return Tag::whereHas('assets', function ($query) use ($asset_Id) {
            $query->where('asset_id', $asset_Id);
        })->get();
    }

    /**
     * Find-or-create the given tag names and attach them to the asset,
     * leaving any existing tags on it untouched. Idempotent: safe to
     * call repeatedly with the same names.
     *
     * @param  array<int, string>  $tagNames
     */
    public function attachTagsByName(Asset $asset, array $tagNames): void
    {
        $names = collect($tagNames)
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique(fn ($name) => mb_strtolower($name))
            ->values();

        if ($names->isEmpty()) {
            return;
        }

        $lowerNames = $names->map(fn ($name) => mb_strtolower($name))->all();

        $placeholders = implode(',', array_fill(0, count($lowerNames), '?'));

        $existingByLower = Tag::whereRaw("LOWER(name) IN ({$placeholders})", $lowerNames)
            ->get()
            ->keyBy(fn (Tag $tag) => mb_strtolower($tag->name));

        $tagIds = [];

        foreach ($names as $name) {
            $lower = mb_strtolower($name);
            $tag = $existingByLower->get($lower);

            if (! $tag) {
                try {
                    $tag = Tag::create(['name' => $name]);
                } catch (QueryException) {
                    $tag = Tag::whereRaw('LOWER(name) = ?', [$lower])->first();
                }
            }

            if ($tag) {
                $tagIds[] = $tag->id;
            }
        }

        $asset->tags()->syncWithoutDetaching($tagIds);
    }
}
