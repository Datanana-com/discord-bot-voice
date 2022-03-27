<?php

declare(strict_types=1);

namespace App\Models;

class ModelRules
{
    /**
     * Rules to validate each column of the model.
     *
     * @var array
     */
    protected $validationRules = [
        // Requirement rules
        'nullable',
        'required',
        'unique',

        // Variable type rules
        'int',
        'integer',
        'string',
        'boolean',
        'bool',
        'float',
        'double',
        'array',
        'object',

        // Date rules
        'datetime',
        'date',
        'time',
        'timestamp',
        'year',
        'month',
        'day',
        'hour',
        'minute',
        'second',
        'date_format',
        'time_format',
        'timestamp_format',
        'year_format',
        'month_format',
        'day_format',
        'hour_format',
        'minute_format',
        'second_format',
    ];

}
