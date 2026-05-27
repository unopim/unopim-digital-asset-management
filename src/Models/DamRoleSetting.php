<?php

namespace Webkul\DAM\Models;

use Illuminate\Database\Eloquent\Model;

class DamRoleSetting extends Model
{
    protected $table = 'dam_role_settings';

    protected $primaryKey = 'role_id';

    public $incrementing = false;

    protected $fillable = ['role_id', 'all_directories', 'inherit_children'];

    protected $casts = [
        'all_directories'  => 'boolean',
        'inherit_children' => 'boolean',
    ];
}
