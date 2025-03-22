<?php

namespace App\Controller;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\ItemInterface;
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
}
