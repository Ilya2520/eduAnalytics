<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Program;
use App\Service\ProgramService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Programs')]
#[Route('/api/programs')]
class ProgramController extends AbstractController
{
    public function __construct(
        private readonly ProgramService $programService
    ) {
    }

    #[OA\Get(
        summary: 'Get list of programs',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1)
            ),
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 10)
            ),
            new OA\Parameter(
                name: 'isActive',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean')
            ),
            new OA\Parameter(
                name: 'degree',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['bachelor', 'master', 'phd'])
            )
        ]
    )]
    #[OA\Response(
        response: 200,
        description: 'List of programs',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Program::class))
        )
    )]
    #[IsGranted('ROLE_USER')]
    #[Route('', name: 'programs_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 10);
        $isActive = $request->query->get('isActive');
        $degree = $request->query->get('degree');

        $programs = $this->programService->getProgramsList($page, $limit, $isActive, $degree);

        return $this->json($programs);
    }

    #[OA\Get(
        summary: 'Get program by ID',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ]
    )]
    #[OA\Response(
        response: 200,
        description: 'Program details',
        content: new OA\JsonContent(ref: new Model(type: Program::class))
    )]
    #[OA\Response(
        response: 404,
        description: 'Program not found'
    )]
    #[IsGranted('ROLE_USER')]
    #[Route('/{id}', name: 'program_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $program = $this->programService->getProgramById($id);

        if (!$program) {
            return $this->json(['error' => 'Program not found'], 404);
        }

        return $this->json($program);
    }
}
