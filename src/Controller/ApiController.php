<?php

namespace App\Controller;

use App\Model\Service\JobService;
use RuntimeException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ApiController extends AbstractController
{
    public function __construct(private readonly JobService $jobService) {}

    #[Route('/', name: 'homepage', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('base.html.twig');
    }

    #[Route('/api/jobs', name: 'api_jobs', methods: ['GET'])]
    public function getJobs(Request $request): JsonResponse
    {
        try {
            $page = max(1, (int)$request->query->get('page', 1));
            $limit = min(max(5, (int)$request->query->get('limit', 10)), 50);

            $data = $this->jobService->getJobs($page, $limit);
            return new JsonResponse($data);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/api/jobs/{jobId}', name: 'api_job_detail', methods: ['GET'])]
    public function getJobDetail(int $jobId): JsonResponse
    {
        try {
            $job = $this->jobService->getJobDetail($jobId);
            return new JsonResponse($job);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 404);
        }
    }

    #[Route('/api/respond', name: 'api_respond', methods: ['POST'])]
    public function respondToJob(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validate required fields
        $requiredFields = ['job_id', 'name', 'email', 'gdpr_agreement'];
        $missingFields = array_filter($requiredFields, fn($field) => empty($data[$field]));

        if (!empty($missingFields)) {
            return new JsonResponse([
                'error' => 'Chybí povinná pole: ' . implode(', ', $missingFields)
            ], 400);
        }

        try {
            $response = $this->jobService->submitJobApplication($data);
            return new JsonResponse($response, $response['success'] ? 201 : 400);
        } catch (RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
