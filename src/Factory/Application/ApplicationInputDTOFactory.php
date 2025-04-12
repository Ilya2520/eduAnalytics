<?php

declare(strict_types=1);

namespace App\Factory\Application;

use App\DTO\Input\Application\CreateApplicationInputDTO;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

class ApplicationInputDTOFactory
{
    public function __construct(
        private readonly SerializerInterface $serializer
    ) {
    }

    public function createFromRequest(Request $request): CreateApplicationInputDTO
    {
        return $this->serializer->deserialize(
            $request->getContent(),
            CreateApplicationInputDTO::class,
            'json'
        );
    }
}
