<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\Input\Applicant\CreateApplicantInputDTO;
use App\DTO\Output\Applicant\ApplicantListOutputDTO;
use App\DTO\Output\Applicant\ApplicantOutputDTO;
use App\Factory\Applicant\ApplicantInputDTOFactory;
use App\Factory\Applicant\ListApplicantsQueryDTOFactory;
use App\Service\ApplicantService;
use App\Validator\ApplicantValidator;
use App\Validator\DTOValidator;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[OA\Tag(name: 'Applicants')]
#[Route('/api/applicants')]
class ApplicantController extends AbstractApiController
{
    public function __construct(
        private readonly ApplicantService $applicantService,
        private readonly ApplicantInputDTOFactory $inputDTOFactory,
        private readonly ApplicantValidator $applicantValidator,
        private readonly DTOValidator $dtoValidator,
        SerializerInterface $serializer
    ) {
        parent::__construct($serializer);
    }

    #[OA\Post(
        summary: 'Create new applicant',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            description: 'Applicant data',
            content: new OA\JsonContent(ref: new Model(type: CreateApplicantInputDTO::class))
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Applicant created',
        content: new OA\JsonContent(ref: new Model(type: ApplicantOutputDTO::class))
    )]
    #[IsGranted('ROLE_ADMIN')]
    #[Route('', name: 'applicant_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        try {
            $inputDTO = $this->inputDTOFactory->createFromRequest($request);

            $dtoValidation = $this->dtoValidator->validate($inputDTO);
            if (!$dtoValidation['success']) {
                return $this->respondWithError($dtoValidation['errors'], Response::HTTP_BAD_REQUEST);
            }

            $appValidation = $this->applicantValidator->validateApplicantCreation($inputDTO);
            if (!$appValidation['success']) {
                return $this->respondWithError($appValidation['errors'], Response::HTTP_BAD_REQUEST);
            }

            $outputDTO = $this->applicantService->createApplicant(
                $inputDTO->firstName,
                $inputDTO->lastName,
                $inputDTO->email,
                $inputDTO->phone,
                $inputDTO->birthDate
            );

            return $this->respond($outputDTO, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->respondWithError(
                'Internal server error: ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[OA\Get(
        summary: 'Get applicants list with filtering',
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
                name: 'search',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'sortBy',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    default: 'createdAt',
                    enum: ['id', 'firstName', 'lastName', 'email', 'createdAt']
                )
            ),
            new OA\Parameter(
                name: 'sortDirection',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    enum: ['asc', 'desc'],
                    default: 'desc'
                )
            )
        ]
    )]
    #[OA\Response(
        response: 200,
        description: 'List of applicants',
        content: new OA\JsonContent(ref: new Model(type: ApplicantListOutputDTO::class))
    )]
    #[IsGranted('ROLE_ADMIN')]
    #[Route('', name: 'applicants_list', methods: ['GET'])]
    public function list(Request $request): Response
    {
        try {
            $query = (new ListApplicantsQueryDTOFactory())->create($request);

            $validation = $this->dtoValidator->validate($query);
            if (!$validation['success']) {
                return $this->respondWithError($validation['errors'], Response::HTTP_BAD_REQUEST);
            }

            $result = $this->applicantService->listApplicants(
                $query->page,
                $query->limit,
                $query->search,
                $query->sortBy,
                $query->sortDirection
            );

            return $this->respond($result);
        } catch (\Exception $e) {
            return $this->respondWithError(
                'Internal server error: ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
