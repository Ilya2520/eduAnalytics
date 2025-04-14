<?php

declare(strict_types=1);

namespace App\DTO\Output;

use App\Entity\Program;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: 'ProgramOutput',
    description: 'Program output data'
)]
class ProgramOutput
{
    #[OA\Property(description: 'Program ID', example: 1)]
    public ?int $id;

    #[OA\Property(description: 'Program name', example: 'Computer Science')]
    public string $name;

    #[OA\Property(description: 'Program code', example: 'CS101')]
    public string $code;

    #[OA\Property(description: 'Program description', nullable: true, example: 'Bachelor program in Computer Science')]
    public ?string $description;

    #[OA\Property(
        description: 'Degree level',
        enum: ['bachelor', 'master', 'phd'],
        example: 'bachelor'
    )]
    public string $degree;

    #[OA\Property(description: 'Duration in months', example: 48)]
    public int $duration;

    #[OA\Property(description: 'Student capacity', example: 100)]
    public int $capacity;

    #[OA\Property(description: 'Is program active', example: true)]
    public bool $isActive;

    public function __construct(
        ?int $id,
        string $name,
        string $code,
        ?string $description,
        string $degree,
        int $duration,
        int $capacity,
        bool $isActive
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->code = $code;
        $this->description = $description;
        $this->degree = $degree;
        $this->duration = $duration;
        $this->capacity = $capacity;
        $this->isActive = $isActive;
    }

    public static function fromEntity(Program $program): self
    {
        return new self(
            $program->getId(),
            $program->getName(),
            $program->getCode(),
            $program->getDescription(),
            $program->getDegree(),
            $program->getDuration(),
            $program->getCapacity(),
            $program->isActive()
        );
    }
}
