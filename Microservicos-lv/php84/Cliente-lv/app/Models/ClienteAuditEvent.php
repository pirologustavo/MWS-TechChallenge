<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class ClienteAuditEvent extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'cliente_audit_events';

    protected $fillable = [
        'client_id', 'event', 'fields', 'correlation_id', 'occurred_at',
    ];

    protected $casts = [
        'client_id' => 'integer',
        'fields' => 'array',
        'occurred_at' => 'immutable_datetime',
    ];

    public $timestamps = false;
}
