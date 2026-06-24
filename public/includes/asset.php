<?php

function asset(string $path): string
{
    $normalizedPath = ltrim($path, '/');
    $fullPath = dirname(__DIR__) . '/' . $normalizedPath;
    $version = is_file($fullPath) ? (string) filemtime($fullPath) : '0';

    return $path . '?v=' . $version;
}
