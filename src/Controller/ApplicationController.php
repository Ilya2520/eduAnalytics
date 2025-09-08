<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\Input\Application\CreateApplicationInputDTO;
use App\DTO\Output\Application\ApplicationListOutputDTO;
use App\DTO\Output\Application\ApplicationOutputDTO;
use App\Factory\Application\ApplicationInputDTOFactory;
use App\Factory\Application\ListApplicationsQueryDTOFactory;
use App\Service\ApplicationService;
use App\Service\CacheService;
use App\Validator\ApplicationValidator;
use App\Validator\DTOValidator;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use App\Presentation\Http\ApiResponseTrait;

#[OA\Tag(name: 'Applications')]
#[Route('/api/applications')]
class ApplicationController extends AbstractApiController
{
    use ApiResponseTrait;
    private const TAG = 'Applications';
    public function __construct(
        private readonly ApplicationService $applicationService,
        private readonly ApplicationInputDTOFactory $inputDTOFactory,
        private readonly ApplicationValidator $appValidator,
        private readonly DTOValidator $dtoValidator,
        private readonly CacheService $cacheService,
        private readonly ListApplicationsQueryDTOFactory $listApplicationsQueryDTOFactory,
        private readonly \App\Service\CacheKeyBuilder $cacheKeyBuilder,
        SerializerInterface $serializer
    ) {
        parent::__construct($serializer);
    }

    #[OA\Post(
        summary: 'Create new application',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            description: 'Application data',
            content: new OA\JsonContent(ref: new Model(type: CreateApplicationInputDTO::class))
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Application created',
        content: new OA\JsonContent(ref: new Model(type: ApplicationOutputDTO::class))
    )]
    #[IsGranted('ROLE_ADMIN')]
    #[Route('', name: 'applications_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        try {
            $inputDTO = $this->inputDTOFactory->createFromRequest($request);

            // Валидация DTO
            $dtoValidation = $this->dtoValidator->validate($inputDTO);
            if (!$dtoValidation['success']) {
                return $this->respondWithError($dtoValidation['errors'], Response::HTTP_BAD_REQUEST);
            }

            // Бизнес-валидация
            $appValidation = $this->appValidator->validateApplicationCreation($inputDTO);
            if (!$appValidation['success']) {
                return $this->respondWithError($appValidation['errors'], Response::HTTP_BAD_REQUEST);
            }

            $outputDTO = $this->applicationService->createApplication(
                $inputDTO->applicantId,
                $inputDTO->programId,
                $inputDTO->documents,
                $inputDTO->notes
            );
            $this->cacheService->invalidateByTags([self::TAG]);

            return $this->respond($outputDTO, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->respondWithError(
                'Internal server error: ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[OA\Get(
        summary: 'Get application by ID',
        security: [['Bearer' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Application ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ]
    )]
    #[OA\Response(
        response: 200,
        description: 'Application details',
        content: new OA\JsonContent(ref: new Model(type: ApplicationOutputDTO::class))
    )]
    #[OA\Response(
        response: 404,
        description: 'Application not found'
    )]
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}', name: 'applications_get', methods: ['GET'])]
    public function get(int $id): Response
    {
        try {
            $cacheKey = $this->cacheKeyBuilder->build(
                className: get_class($this),
                methodName: __FUNCTION__,
                params: ['id' => $id],
            );

            $application = $this->cacheService->fetchFromCache(
                key: $cacheKey,
                tag: self::TAG,
                callback: fn () => $this->applicationService->getApplicationById($id)
            );

            return $this->ok($application);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), Response::HTTP_NOT_FOUND);
        }
    }

    #[OA\Get(
        summary: 'Get applications list with filtering',
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
                name: 'status',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    enum: ['draft', 'submitted', 'under_review', 'accepted', 'rejected']
                )
            ),
            new OA\Parameter(
                name: 'applicantId',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'programId',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'sortBy',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    default: 'createdAt',
                    enum: ['id', 'status', 'createdAt', 'updatedAt']
                )
            ),
            new OA\Parameter(
                name: 'sortDirection',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    default: 'desc',
                    enum: ['asc', 'desc']
                )
            )
        ]
    )]
    #[OA\Response(
        response: 200,
        description: 'List of applications',
        content: new OA\JsonContent(ref: new Model(type: ApplicationListOutputDTO::class))
    )]
    #[IsGranted('ROLE_ADMIN')]
    #[Route('', name: 'applications_list', methods: ['GET'])]
    public function list(Request $request): Response
    {
        try {
            $listQuery = $this->listApplicationsQueryDTOFactory->create($request);

            $validation = $this->dtoValidator->validate($listQuery);
            if (!$validation['success']) {
                return $this->respondWithError($validation['errors'], Response::HTTP_BAD_REQUEST);
            }

            $cacheKey = $this->cacheKeyBuilder->build(
                className: get_class($this),
                methodName: __FUNCTION__,
                params: get_object_vars($listQuery)
            );

            $result = $this->cacheService->fetchFromCache(
                key: $cacheKey,
                tag: self::TAG,
                callback: fn () => $this->applicationService->listApplications($listQuery),
            );

            return $this->ok($result);
        } catch (\Exception $e) {
            return $this->error('Internal server error: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
