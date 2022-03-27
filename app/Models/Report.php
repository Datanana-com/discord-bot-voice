<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;

class Report extends BaseModel
{
    protected $table = 'reports';

    protected $attributes = [
        'id' => ['primary_key', 'auto_increment'],
        'server_id' => ['nullable', 'int',], // TODO: Add server Model
        'reporter_id' => ['required', 'int', '@isSteamCommunityId'], // TODO: Add Steam Community Id Validation
        'target_id' => ['required', 'int', '@isSteamCommunityId'], // TODO: Add Steam Community Id Validation
        'reason' => ['nullable', 'string'],
        'claimed_by' => ['nullable', 'int',], // TODO: Add staff Model
        'created_at' => ['nullable', 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
        'updated_at' => ['nullable', 'datetime', 'default' => 'CURRENT_TIMESTAMP'],
        'deleted_at' => ['nullable', 'datetime', 'default' => null],
    ];
    
    protected $relationships = [
        'server_id' => '',
        'claimed_by' => '',
    ];
}
