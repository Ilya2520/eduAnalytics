<?php

declare(strict_types=1);

namespace App\Tests\Security;

use App\Entity\Report;
use App\Entity\User;
use App\Security\Voter\ReportVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ReportVoterTest extends TestCase
{
    private function createToken(User $user): TokenInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        return $token;
    }

    public function testAdminCanDoAnything(): void
    {
        $user = (new User())->setEmail('a@a')->setRoles(['ROLE_ADMIN']);
        $token = $this->createToken($user);
        $voter = new ReportVoter();

        $this->assertTrue($voter->voteOnAttribute(ReportVoter::CREATE, null, $token));
        $report = new Report();
        $report->setRequestedBy($user);
        $this->assertTrue($voter->voteOnAttribute(ReportVoter::VIEW, $report, $token));
        $this->assertTrue($voter->voteOnAttribute(ReportVoter::EDIT, $report, $token));
        $this->assertTrue($voter->voteOnAttribute(ReportVoter::DELETE, $report, $token));
    }

    public function testOwnerCanViewOnly(): void
    {
        $owner = (new User())->setEmail('o@o')->setRoles(['ROLE_USER']);
        $token = $this->createToken($owner);
        $report = new Report();
        $report->setRequestedBy($owner);
        $voter = new ReportVoter();

        $this->assertTrue($voter->voteOnAttribute(ReportVoter::VIEW, $report, $token));
        $this->assertFalse($voter->voteOnAttribute(ReportVoter::EDIT, $report, $token));
        $this->assertFalse($voter->voteOnAttribute(ReportVoter::DELETE, $report, $token));
    }

    public function testOtherUserDenied(): void
    {
        $owner = (new User())->setEmail('o@o')->setRoles(['ROLE_USER']);
        $other = (new User())->setEmail('x@x')->setRoles(['ROLE_USER']);
        $report = new Report();
        $report->setRequestedBy($owner);
        $token = $this->createToken($other);
        $voter = new ReportVoter();

        $this->assertFalse($voter->voteOnAttribute(ReportVoter::VIEW, $report, $token));
    }
} 