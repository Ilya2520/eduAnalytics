<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\Input\Program\ListProgramsQueryDTO;
use App\DTO\Output\ProgramOutput;
use App\Entity\Program;
use App\Factory\Program\ListProgramsQueryDTOFactory;
use App\Service\CacheService;
use App\Service\ProgramService;
use Doctrine\ORM\EntityNotFoundException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Presentation\Http\ApiResponseTrait;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[OA\Tag(name: 'Programs', description: 'Manage academic programs.')]
#[Route('/api/programs')]
class ProgramController extends AbstractController
{
    use ApiResponseTrait;
    private const TAG = 'Programs';
    public function __construct(
        private readonly ProgramService $programService,
        private readonly CacheService $cacheService,
        private readonly ListProgramsQueryDTOFactory $listProgramsQueryDTOFactory,
        private readonly \App\Service\CacheKeyBuilder $cacheKeyBuilder,
    ) {
    }

    #[OA\Get(
        summary: 'List academic programs',
        description: 'Retrieves a paginated list of academic programs, with optional filtering.',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)),
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10, minimum: 1, maximum: 100)),
            new OA\Parameter(name: 'isActive', in: 'query', required: false, schema: new OA\Schema(type: 'boolean'), description: 'Filter by active status.'),
            new OA\Parameter(name: 'degree', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['bachelor', 'master', 'phd']), description: 'Filter by degree level.')
        ]
    )]
    #[OA\Response(
        response: 200,
        description: 'A paginated list of programs.',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: ProgramOutput::class))
        )
    )]
    #[IsGranted('ROLE_USER')]
    #[Route('', name: 'programs_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $query = $this->listProgramsQueryDTOFactory->create($request);

        $cacheKey = $this->cacheKeyBuilder->build(
            className: get_class($this),
            methodName: __FUNCTION__,
            params: [
                'page' => $query->page,
                'limit' => $query->limit,
                'isActive' => $query->isActive,
                'degree' => $query->degree,
            ],
        );

        $result = $this->cacheService->fetchFromCache(
            key: $cacheKey,
            tag: self::TAG,
            callback: fn () => $this->programService->getProgramsList($query->page, $query->limit, $query->isActive, $query->degree)
        );

        $items = array_map(
            fn (Program $program) => ProgramOutput::fromEntity($program),
            iterator_to_array($result['items'])
        );

        return $this->ok([
            'items' => $items,
            'total' => $result['total'],
            'page' => $result['page'],
            'limit' => $result['limit'],
            'pages' => ceil($result['total'] / $result['limit'])
        ]);
    }

    #[OA\Get(
        summary: 'Get a specific program by ID',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ]
    )]
    #[OA\Response(
        response: 200,
        description: 'Details of the program.',
        content: new OA\JsonContent(ref: new Model(type: ProgramOutput::class))
    )]
    #[OA\Response(response: 404, description: 'Program not found.')]
    #[IsGranted('ROLE_USER')]
    #[Route('/{id<\d+>}', name: 'program_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try {
            $cacheKey = $this->cacheService->generateCacheKey(
                className: get_class($this),
                prefix: __FUNCTION__,
                params: ['id' => $id]
            );

            $program = $this->cacheService->fetchFromCache(
                key: $cacheKey,
                tag: self::TAG,
                callback: fn () => $this->programService->getProgramById($id)
            );

            return $this->json(ProgramOutput::fromEntity($program)); // Assuming ProgramOutput has fromEntity
        } catch (EntityNotFoundException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }
}
