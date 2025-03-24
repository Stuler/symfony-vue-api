<?php

namespace App\Model\Service;

readonly class JobService
{
    public function __construct(
        private RecruitisApiService $apiService,
        private JobCacheService     $cacheService
    ) {}

    /**
     * Returns job listings from the Recruitis API.
     * If the data is already cached, it will be returned from the cache, otherwise it will be fetched from the API.
     */
    public function getJobs(int $page, int $limit): array
    {
        return $this->cacheService->getJobs($page, $limit, fn() => $this->apiService->fetchJobs($page, $limit));
    }

    /**
     * Returns job details by specified param from the Recruitis API.
     * If the data is already cached, it will be returned from the cache, otherwise it will be fetched from the API.
     */
    public function getJobDetail(int $jobId): array
    {
        return $this->cacheService->getJobDetail($jobId, fn() => $this->apiService->fetchJobDetail($jobId));
    }


    /**
     * Submits a job application to the Recruitis API.
     */
    public function submitJobApplication(mixed $data): array
    {
        return $this->apiService->submitJobApplication($data);
    }
}
