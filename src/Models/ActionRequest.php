<?php

namespace Webkul\DAM\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\DAM\Contracts\ActionRequest as ActionRequestContract;

class ActionRequest extends Model implements ActionRequestContract
{
    protected $table = 'dam_action_request';

    protected $fillable = [
        'event_type',
        'status',
        'erorr_message',
        'admin_id',
    ];

    public static function findOneWhere($column)
    {
        return static::where($column)->first();
    }
}
