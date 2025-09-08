<?php

declare(strict_types=1);

namespace App\Http\Resolver;

use App\Http\Attribute\MapQueryPayload;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class MapQueryPayloadResolver implements ValueResolverInterface
{
    public function __construct(private readonly DenormalizerInterface $denormalizer)
    {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): array
    {
        $attributes = $argument->getAttributesOfType(MapQueryPayload::class);
        if (empty($attributes)) {
            return [];
        }

        $type = $argument->getType();
        if ($type === null) {
            return [];
        }

        $data = $request->query->all();
        $dto = $this->denormalizer->denormalize($data, $type);

        return [$dto];
    }
} 