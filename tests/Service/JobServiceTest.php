<?php

namespace App\Tests\Service;

use App\Model\Service\JobService;
use App\Model\Service\RecruitisApiService;
use App\Model\Service\JobCacheService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpClient\HttpClient;

class JobServiceTest extends TestCase
{
    private JobService $jobService;

    protected function setUp(): void
    {
        // load environment variables
        $dotenv = new Dotenv();
        $dotenv->loadEnv(__DIR__ . '/../../.env.test'); // Ensure you have an .env.test file

        $apiToken = $_ENV['RECRUITIS_API_TOKEN'] ?? getenv('RECRUITIS_API_TOKEN');

        if (!$apiToken) {
            throw new \RuntimeException("Missing API token for tests.");
        }

        $httpClient = HttpClient::create();
        $cache = new ArrayAdapter();
        $cacheDuration = 3600;

        $apiService = new RecruitisApiService(
            $httpClient,
            'https://app.recruitis.io/api2/',
            $apiToken
        );

        $cacheService = new JobCacheService($cache, $cacheDuration);
        $this->jobService = new JobService($apiService, $cacheService);
    }

    public function testGetJobs(): void
    {
        $page = 1;
        $limit = 5;

        $result = $this->jobService->getJobs($page, $limit);

        $this->assertIsArray($result, "Result should be an array");
        $this->assertArrayHasKey('jobs', $result, "Response should contain 'jobs' key");
        $this->assertArrayHasKey('pagination', $result, "Response should contain 'pagination' key");

        $pagination = $result['pagination'];
        $this->assertEquals($page, $pagination['current_page'], "Current page should match requested page");
        $this->assertEquals($limit, $pagination['per_page'], "Per page should match requested limit");
    }

    public function testGetJobDetail(): void
    {
        $jobId = 430652; // example job ID for testing

        $result = $this->jobService->getJobDetail($jobId);

        $this->assertIsArray($result, "Result should be an array");
        $this->assertArrayHasKey('job_id', $result, "Response should contain 'job_id' key");
        $this->assertEquals($jobId, $result['job_id'], "Returned job ID should match requested job ID");

        $this->assertArrayHasKey('title', $result, "Response should contain 'title' key");
        $this->assertNotEmpty($result['title'], "Job title should not be empty");

        $this->assertArrayHasKey('description', $result, "Response should contain 'description' key");
        $this->assertNotEmpty($result['description'], "Job description should not be empty");
    }

}
