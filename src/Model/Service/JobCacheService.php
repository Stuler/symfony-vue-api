<?php

namespace App\Model\Service;

use Symfony\Contracts\Cache\CacheInterface;

class JobCacheService
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly int $cacheDuration // Inject cache duration
    ) {}

    public function getJobs(int $page, int $limit, callable $fetchFunction): array
    {
        $cacheKey = "jobs_{$page}_limit_{$limit}";

        return $this->cache->get($cacheKey, function () use ($fetchFunction) {
            return $fetchFunction();
        }, $this->cacheDuration);
    }

    public function getJobDetail(int $jobId, callable $fetchFunction): array
    {
        $cacheKey = "job_detail_{$jobId}";

        return $this->cache->get($cacheKey, function () use ($fetchFunction) {
            return $fetchFunction();
        }, $this->cacheDuration);
    }

}
