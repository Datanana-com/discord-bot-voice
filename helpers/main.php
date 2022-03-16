<?php

if (! function_exists('env')) {
    /**
     * Gets the value of an environment variable.
     *
     * @param mixed $key
     * @param mixed|null $default
     * @return mixed
     */
    function env($key, $default = null)
    {
        return $_ENV[$key] ?? $default;
    }
}