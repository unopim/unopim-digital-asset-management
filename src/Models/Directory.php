<?php

namespace Webkul\DAM\Models;

use Illuminate\Database\Eloquent\Model;
use Kalnoy\Nestedset\NodeTrait;
use Webkul\DAM\Contracts\Directory as DirectoryContract;
use Webkul\DAM\Database\Eloquent\Builder;

class Directory extends Model implements DirectoryContract
{
    use NodeTrait;

    const ASSETS_DIRECTORY = 'assets';

    const ASSETS_DISK = 'private';

    const NON_DELETABLE_DRECTORIES = [1];

    protected $table = 'dam_directories';

    protected $fillable = ['name', 'parent_id'];

    public function assets()
    {
        return $this->belongsToMany(Asset::class, 'dam_asset_directory');
    }

    public function parent()
    {
        return $this->belongsTo(Directory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Directory::class, 'parent_id');
    }

    /**
     * check if possible to delete this directory
     */
    public function isDeletable()
    {
        return ! in_array($this->id, self::NON_DELETABLE_DRECTORIES);
    }

    /**
     * check if possible to copy this directory
     */
    public function isCopyable()
    {
        return ! in_array($this->id, self::NON_DELETABLE_DRECTORIES);
    }

    /**
     * Overrides the default Eloquent query builder.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Webkul\DAM\Database\Eloquent\Builder
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    /**
     * Generate path for directory
     */
    public function generatePath(): string
    {
        $path = [];

        foreach ($this->ancestorsAndSelfAndDefaultOrder($this->id) as $directory) {
            $path[] = $directory->name;
        }

        return implode('/', $path);
    }
}
