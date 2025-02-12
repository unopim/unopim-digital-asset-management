<?php

namespace Webkul\DAM\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\DAM\Models\Tag;

class AssetTagRepository extends Repository
{
    protected $assets = [];

    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return Tag::class;
    }

    /**
     * Create asset Tag.
     *
     * @return \Webkul\DAM\Contracts\Tag
     */
    public function create(array $data)
    {
        $asset = $this->model->create($data);

        return $asset;
    }

    /**
     * Update asset Tag.
     *
     * @param  int  $id
     * @param  string  $asset
     * @return \Webkul\DAM\Contracts\Tag
     */
    public function update(array $data, $id, $asset = 'id')
    {
        $asset = $this->find($id);

        $asset->update($data);

        return $asset;
    }

    /**
     * Get asset Tag.
     */
    public function getTagsByAssetId(int $asset_Id)
    {
        return Tag::whereHas('assets', function ($query) use ($asset_Id) {
            $query->where('asset_id', $asset_Id);
        })->get();
    }
}
