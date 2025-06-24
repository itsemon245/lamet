<?php

namespace Itsemon245\Lamet\Traits;

trait HasMetricsIgnore
{
    /**
     * Check if the current request should be ignored.
     */
    protected function shouldIgnore(): bool
    {
        $request = $this->app['request'];
        $path = $request->path();

        return $this->isPathIgnored($path);
    }

    /**
     * Check if a path should be ignored.
     */
    protected function isPathIgnored(string $path): bool
    {
        if ($this->app->runningInConsole()) {
            return false;
        }
        $ignorePaths = $this->config['ignore']['paths'] ?? [];
        foreach ($ignorePaths as $ignorePath) {
            $ignorePath = trim($ignorePath, '/');
            $path = trim($path, '/');
            if (str_ends_with($ignorePath, '*') && str_starts_with($path, $ignorePath)) {
                return true;
            }
            if ($ignorePath === $path) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an exception should be ignored.
     */
    protected function shouldIgnoreException(\Throwable $th): bool
    {
        $ignoreExceptions = $this->config['ignore']['exceptions'] ?? [];
        foreach ($ignoreExceptions as $ignoreException) {
            if (str_contains($ignoreException, '*') && str_starts_with($th::class, $ignoreException)) {
                return true;
            }
            if ($th::class === $ignoreException) {
                return true;
            }
        }

        return false;
    }
}
