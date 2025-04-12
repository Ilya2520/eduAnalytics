<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Validator\ValidatorInterface;

class DTOValidator
{
    public function __construct(
        private readonly ValidatorInterface $validator
    ) {
    }

    public function validate(object $dto): array
    {
        $errors = $this->validator->validate($dto);

        if (count($errors) > 0) {
            return [
                'success' => false,
                'errors' => (string) $errors
            ];
        }

        return ['success' => true];
    }
}
