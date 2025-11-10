<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security\Voter;

use App\Entity\Tag;
use App\Entity\User;
use App\Security\Voter\TagVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class TagVoterTest extends TestCase
{
    private TagVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new TagVoter();
    }

    public function testOwnerCanViewTag(): void
    {
        // Arrange
        $user = new User();
        $user->setEmail('owner@test.com');

        $tag = new Tag();
        $tag->setUser($user);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        // Act
        $result = $this->voter->vote($token, $tag, [TagVoter::VIEW]);

        // Assert
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testOwnerCanEditTag(): void
    {
        // Arrange
        $user = new User();
        $user->setEmail('owner@test.com');

        $tag = new Tag();
        $tag->setUser($user);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        // Act
        $result = $this->voter->vote($token, $tag, [TagVoter::EDIT]);

        // Assert
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testOwnerCanDeleteTag(): void
    {
        // Arrange
        $user = new User();
        $user->setEmail('owner@test.com');

        $tag = new Tag();
        $tag->setUser($user);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        // Act
        $result = $this->voter->vote($token, $tag, [TagVoter::DELETE]);

        // Assert
        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testNonOwnerCannotDeleteTag(): void
    {
        // Arrange
        $owner = new User();
        $owner->setEmail('owner@test.com');

        $otherUser = new User();
        $otherUser->setEmail('other@test.com');

        $tag = new Tag();
        $tag->setUser($owner);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($otherUser);

        // Act
        $result = $this->voter->vote($token, $tag, [TagVoter::DELETE]);

        // Assert
        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testUnauthenticatedUserDenied(): void
    {
        // Arrange
        $owner = new User();
        $owner->setEmail('owner@test.com');

        $tag = new Tag();
        $tag->setUser($owner);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null); // No authenticated user

        // Act
        $result = $this->voter->vote($token, $tag, [TagVoter::VIEW]);

        // Assert
        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testVoterSupportsOnlyTagEntity(): void
    {
        // Arrange
        $tag = new Tag();
        $token = $this->createMock(TokenInterface::class);

        // Act - supports Tag entity
        $resultTag = $this->voter->vote($token, $tag, [TagVoter::VIEW]);

        // Act - does not support other objects
        $resultOther = $this->voter->vote($token, new \stdClass(), [TagVoter::VIEW]);

        // Assert
        $this->assertNotSame(VoterInterface::ACCESS_ABSTAIN, $resultTag);
        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $resultOther);
    }

    public function testVoterSupportsCorrectAttributes(): void
    {
        // Arrange
        $tag = new Tag();
        $token = $this->createMock(TokenInterface::class);

        // Act - supports correct attributes
        $resultView = $this->voter->vote($token, $tag, [TagVoter::VIEW]);
        $resultEdit = $this->voter->vote($token, $tag, [TagVoter::EDIT]);
        $resultDelete = $this->voter->vote($token, $tag, [TagVoter::DELETE]);

        // Act - does not support unknown attributes
        $resultUnknown = $this->voter->vote($token, $tag, ['unknown_attribute']);

        // Assert
        $this->assertNotSame(VoterInterface::ACCESS_ABSTAIN, $resultView);
        $this->assertNotSame(VoterInterface::ACCESS_ABSTAIN, $resultEdit);
        $this->assertNotSame(VoterInterface::ACCESS_ABSTAIN, $resultDelete);
        $this->assertSame(VoterInterface::ACCESS_ABSTAIN, $resultUnknown);
    }
}
