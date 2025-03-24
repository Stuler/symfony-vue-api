<?php

namespace App\Model\Service;

use RuntimeException;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RecruitisApiService
{
    private string $apiUrl;
    private string $apiToken;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        string $apiUrl,
        string $apiToken
    ) {
        $this->apiUrl = rtrim($apiUrl, '/') . '/';
        $this->apiToken = $apiToken;

        if (empty($this->apiToken)) {
            throw new RuntimeException('API token není nastaven.');
        }
    }

    /**
     * Fetch job listings from the Recruitis API.
     */
    public function fetchJobs(int $page, int $limit): array
    {
        try {
            $response = $this->httpClient->request('GET', $this->apiUrl . 'jobs/', [
                'headers' => $this->getApiHeaders(),
                'query' => [
                    'page' => $page,
                    'limit' => min($limit, 50),
                    'activity_state' => 1,
                    'access_state' => 1,
                    'order_by' => 'date_created'
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new RuntimeException('Failed to fetch jobs from API');
            }

            $responseData = json_decode($response->getContent(false), true);

            // Extract job list and meta data
            $jobs = $responseData['payload'] ?? [];
            $totalJobs = $responseData['meta']['entries_total'] ?? count($jobs);
            $totalPages = max(1, ceil($totalJobs / $limit));

            return [
                'jobs' => $jobs,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total_jobs' => $totalJobs,
                    'total_pages' => $totalPages,
                ]
            ];
        } catch (HttpExceptionInterface $e) {
            throw new RuntimeException('Chyba při komunikaci s API: ' . $e->getMessage());
        }
    }

    /**
     * Fetch job details from the Recruitis API.
     */
    public function fetchJobDetail(int $jobId): array
    {
        try {
            $response = $this->httpClient->request('GET', $this->apiUrl . 'jobs/' . $jobId, [
                'headers' => $this->getApiHeaders(),
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new RuntimeException("Job not found (ID: $jobId)");
            }

            return json_decode($response->getContent(false), true);
        } catch (HttpExceptionInterface $e) {
            throw new RuntimeException('Error fetching job details: ' . $e->getMessage());
        }
    }

    /**
     * Submit job application to Recruitis API.
     */
    public function submitJobApplication(array $data): array
    {
        $postData = $this->preparePostData($data);

        $response = $this->httpClient->request('POST', $this->apiUrl . 'answers', [
            'headers' => $this->getApiHeaders(),
            'json' => $postData,
        ]);

        return $this->handleResponse($response);
    }

    /**
     * Prepare the job application payload for the API request.
     */
    private function preparePostData(array $data): array
    {
        return [
            "job_id" => (int) $data['job_id'],
            "name" => trim($data['name']),
            "email" => trim($data['email']),
            "phone" => $data['phone'] ?? null,
            "linkedin" => $data['linkedin'] ?? null,
            "cover_letter" => $data['cover_letter'] ?? "",
            "salary" => $this->prepareSalaryData($data['salary'] ?? null),
            "gdpr_agreement" => [
                "date_expiration" => date("Y-m-d", strtotime("+1 year")),
                "source" => "manual",
            ],
            "attachments" => $this->prepareAttachments($data['attachments'] ?? []),
            "send_notification" => false
        ];
    }

    /**
     * Prepare salary data if available.
     */
    private function prepareSalaryData(?array $salary): ?array
    {
        if (!isset($salary['min']) || !is_numeric($salary['min'])) {
            return null;
        }

        return [
            "amount" => (int) $salary['min'], // Minimum salary as base amount
            "currency" => $salary['currency'] ?? "CZK",
            "unit" => $salary['unit'] ?? "month",
            "note" => $salary['note'] ?? "",
        ];
    }

    /**
     * Prepare attachments data.
     */
    private function prepareAttachments(array $attachments): array
    {
        return array_map(fn($attachment) => [
            "path" => $attachment['path'] ?? null,
            "filename" => $attachment['filename'] ?? 'file.pdf',
            "type" => $attachment['type'] ?? 1,
        ], $attachments);
    }

    /**
     * Handle API response.
     */
    private function handleResponse($response): array
    {
        $statusCode = $response->getStatusCode();
        $responseData = json_decode($response->getContent(false), true);

        if (in_array($statusCode, [200, 201, 202])) {
            return [
                'success' => true,
                'message' => $responseData['message'] ?? 'Odpověď byla úspěšně odeslána.',
                'flash' => 'success'
            ];
        }

        return [
            'success' => false,
            'error' => $responseData['error'] ?? 'Nepodařilo se odeslat odpověď.',
            'details' => $responseData,
            'flash' => 'error'
        ];
    }

    /**
     * Get API request headers.
     */
    private function getApiHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiToken,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }
}
