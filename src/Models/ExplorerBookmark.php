<?php

declare(strict_types=1);

namespace Webkul\DAM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\User\Models\Admin;

class ExplorerBookmark extends Model
{
    protected $table = 'dam_explorer_bookmarks';

    protected $fillable = ['user_id', 'directory_id', 'name', 'sort_order'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'user_id');
    }

    public function directory(): BelongsTo
    {
        return $this->belongsTo(Directory::class, 'directory_id');
    }
}
