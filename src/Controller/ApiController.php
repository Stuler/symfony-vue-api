<?php

namespace App\Controller;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ApiController extends AbstractController
{
    private HttpClientInterface $httpClient;
    private ParameterBagInterface $params;
    private FilesystemAdapter $cache;

    public function __construct(HttpClientInterface $httpClient, ParameterBagInterface $params)
    {
        $this->httpClient = $httpClient;
        $this->params = $params;
        $this->cache = new FilesystemAdapter();
    }

    #[Route('/', name: 'homepage', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('base.html.twig');
    }


    #[Route('/api/jobs', name: 'api_jobs', methods: ['GET'])]
    public function getJobs(Request $request): JsonResponse
    {
        $apiUrl = $this->params->get('recruitis_api_url') . 'jobs/';
        $apiToken = $this->params->get('recruitis_api_token');
        if (empty($apiToken)) {
            return new JsonResponse(['error' => 'API token není nastaven'], 500);
        }

        $page = max(1, (int)$request->query->get('page', 1));
        $limit = 5;
        // cache the results
        $cacheKey = 'jobs_' . $page . '_limit_' . $limit;

        try {
            // retrieve data from cache or fetch from API if not in cache
            $data = $this->cache->get($cacheKey, function (ItemInterface $item) use ($apiUrl, $apiToken, $limit) {
                $item->expiresAfter($this->params->get('cache_duration'));

                // fetch jobs from API
                $response = $this->httpClient->request('GET', $apiUrl, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $apiToken,
                        'Accept' => 'application/json',
                    ],
                ]);

                if ($response->getStatusCode() !== 200) {
                    throw new \Exception('Failed to fetch jobs from API');
                }

                return $response->toArray();
            });

            // pagination
            $jobs = $data['payload'] ?? [];
            $totalJobs = count($jobs);
            $totalPages = ceil($totalJobs / $limit);
            $paginatedJobs = array_slice($jobs, ($page - 1) * $limit, $limit);

            return new JsonResponse([
                'jobs' => $paginatedJobs,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total_jobs' => $totalJobs,
                    'total_pages' => $totalPages,
                ],
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Failed to fetch jobs', 'details' => $e->getMessage()], 500);
        }
    }

    #[Route('/api/respond', name: 'api_respond', methods: ['POST'])]
    public function respondToJob(
        Request $request,
        HttpClientInterface $httpClient,
        ParameterBagInterface $params
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        // Validate required fields
        $requiredFields = ['job_id', 'name', 'email', 'gdpr_agreement'];
        $missingFields = array_filter($requiredFields, fn($field) => empty($data[$field]));

        if (!empty($missingFields)) {
            return new JsonResponse([
                'error' => 'Chybí povinná pole: ' . implode(', ', $missingFields),
                'flash' => 'error'
            ], 400);
        }

        // Prepare API credentials
        $apiUrl = $params->get('recruitis_api_url') . 'answers';
        $apiToken = $params->get('recruitis_api_token');

        if (empty($apiToken)) {
            return new JsonResponse([
                'error' => 'API token není nastaven',
                'flash' => 'error'
            ], 500);
        }

        try {
            $postData = [
                "job_id" => (int) $data['job_id'],
                "name" => trim($data['name']),
                "email" => trim($data['email']),
                "phone" => $data['phone'] ?? null,
                "linkedin" => $data['linkedin'] ?? null,
                "cover_letter" => $data['cover_letter'] ?? "",
                "salary" => !empty($data['salary']['amount']) && is_numeric($data['salary']['amount'])
                    ? [
                        "amount" => (int) $data['salary']['amount'],
                        "currency" => $data['salary']['currency'] ?? "CZK",
                        "unit" => $data['salary']['unit'] ?? "month",
                        "type" => $data['salary']['type'] ?? 0,
                        "note" => $data['salary']['note'] ?? "",
                    ]
                    : null,
                "gdpr_agreement" => [
                    "date_expiration" => date("Y-m-d", strtotime("+1 year")),
                    "source" => "manual",
                ],
                "attachments" => array_map(fn($attachment) => [
                    "path" => $attachment['path'] ?? null,
                    "filename" => $attachment['filename'] ?? 'file.pdf',
                    "type" => $attachment['type'] ?? 1,
                ], $data['attachments'] ?? []),
                "send_notification" => false
            ];

            // Send response to Recruitis API
            $submitResponse = $httpClient->request('POST', $apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiToken,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => $postData,
            ]);

            $statusCode = $submitResponse->getStatusCode();
            $responseContent = $submitResponse->getContent(false); // prevent exceptions on non-2xx responses

            if ($statusCode === 201) {
                return new JsonResponse([
                    'message' => 'Odpověď byla úspěšně odeslána. Teď budete přesměrováni na domovskou stránku.',
                    'flash' => 'success'
                ], 201);
            }

            return new JsonResponse([
                'error' => 'Nepodařilo se odeslat odpověď. Zkuste to prosím znovu.',
                'flash' => 'error',
                'details' => json_decode($responseContent, true) ?? 'Unknown API response'
            ], $statusCode);

        } catch (HttpExceptionInterface $e) {
            return new JsonResponse([
                'error' => 'Chyba při komunikaci s API: ' . $e->getMessage(),
                'flash' => 'error'
            ], 500);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Neočekávaná chyba: ' . $e->getMessage(),
                'flash' => 'error'
            ], 500);
        }
    }


}
