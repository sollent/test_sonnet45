<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Voter;

use App\Entity\Task;
use App\Entity\User;
use App\Security\Voter\TaskVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class TaskVoterTest extends TestCase
{
    private TaskVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new TaskVoter();
    }

    public function testOwnerCanViewTask(): void
    {
        // Arrange
        $user = new User();
        $user->setEmail('owner@test.com');

        $task = new Task();
        $task->setUser($user);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        // Act
        $result = $this->voter->vote($token, $task, [TaskVoter::VIEW]);

        // Assert
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testOwnerCanEditTask(): void
    {
        // Arrange
        $user = new User();
        $user->setEmail('owner@test.com');

        $task = new Task();
        $task->setUser($user);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        // Act
        $result = $this->voter->vote($token, $task, [TaskVoter::EDIT]);

        // Assert
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testOwnerCanDeleteTask(): void
    {
        // Arrange
        $user = new User();
        $user->setEmail('owner@test.com');

        $task = new Task();
        $task->setUser($user);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        // Act
        $result = $this->voter->vote($token, $task, [TaskVoter::DELETE]);

        // Assert
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testNonOwnerCannotViewTask(): void
    {
        // Arrange
        $owner = new User();
        $owner->setEmail('owner@test.com');

        $otherUser = new User();
        $otherUser->setEmail('other@test.com');

        $task = new Task();
        $task->setUser($owner);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($otherUser);

        // Act
        $result = $this->voter->vote($token, $task, [TaskVoter::VIEW]);

        // Assert
        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testNonOwnerCannotEditTask(): void
    {
        // Arrange
        $owner = new User();
        $owner->setEmail('owner@test.com');

        $otherUser = new User();
        $otherUser->setEmail('other@test.com');

        $task = new Task();
        $task->setUser($owner);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($otherUser);

        // Act
        $result = $this->voter->vote($token, $task, [TaskVoter::EDIT]);

        // Assert
        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testNonOwnerCannotDeleteTask(): void
    {
        // Arrange
        $owner = new User();
        $owner->setEmail('owner@test.com');

        $otherUser = new User();
        $otherUser->setEmail('other@test.com');

        $task = new Task();
        $task->setUser($owner);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($otherUser);

        // Act
        $result = $this->voter->vote($token, $task, [TaskVoter::DELETE]);

        // Assert
        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testUnauthenticatedUserDenied(): void
    {
        // Arrange
        $owner = new User();
        $owner->setEmail('owner@test.com');

        $task = new Task();
        $task->setUser($owner);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null); // No authenticated user

        // Act
        $result = $this->voter->vote($token, $task, [TaskVoter::VIEW]);

        // Assert
        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testVoterSupportsOnlyTaskEntity(): void
    {
        // Arrange
        $task = new Task();
        $token = $this->createMock(TokenInterface::class);

        // Act - supports Task entity
        $resultTask = $this->voter->vote($token, $task, [TaskVoter::VIEW]);

        // Act - does not support other objects
        $resultOther = $this->voter->vote($token, new \stdClass(), [TaskVoter::VIEW]);

        // Assert
        $this->assertNotSame(VoterInterface::ACCESS_ABSTAIN, $resultTask);
        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $resultOther);
    }

    public function testVoterSupportsCorrectAttributes(): void
    {
        // Arrange
        $task = new Task();
        $token = $this->createMock(TokenInterface::class);

        // Act - supports correct attributes
        $resultView = $this->voter->vote($token, $task, [TaskVoter::VIEW]);
        $resultEdit = $this->voter->vote($token, $task, [TaskVoter::EDIT]);
        $resultDelete = $this->voter->vote($token, $task, [TaskVoter::DELETE]);

        // Act - does not support unknown attributes
        $resultUnknown = $this->voter->vote($token, $task, ['unknown_attribute']);

        // Assert
        $this->assertNotSame(VoterInterface::ACCESS_ABSTAIN, $resultView);
        $this->assertNotSame(VoterInterface::ACCESS_ABSTAIN, $resultEdit);
        $this->assertNotSame(VoterInterface::ACCESS_ABSTAIN, $resultDelete);
        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $resultUnknown);
    }
}
