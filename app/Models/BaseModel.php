<?php

declare(strict_types=1);

namespace App\Models;

abstract class BaseModel
{
    /**
     * Table name to use for this model.
     *
     * @var string
     */
    protected $table;

    /**
     * Attributes to validate each column of the model.
     *
     * @var array
     */
    protected $attributes;

    /**
     * Model relationships via column names.
     *
     * @var array
     */
    protected $relationships;

    /**
     * Checks whether the given id is a valid steam community id.
     *
     * @param int $id
     *
     * @return bool
     */
    public function isSteamCommunityId(int $id): bool
    {
        return (bool) preg_match('/^\d{17}$/', (string) $id);
    }

    public function validateAttributes()
    {
        if (empty($this->attributes)) {
            return true;
        }

        foreach ($this->attributes as $attribute => $rules) {
            foreach ($rules as $rule) {
                if (method_exists($this, $rule)) {
                    if (! $this->$rule($this->$attribute)) {
                        return false;
                    }
                }
            }
        }
    }

    
}
