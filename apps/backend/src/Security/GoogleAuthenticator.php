<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Security\User\OAuthUserProvider;
use RuntimeException;
use stdClass;

class GoogleAuthenticator extends OAuthUserProvider
{
    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    public function loadUserFromDecodedJwt(stdClass $jwt): User
    {
        $email = $jwt->email ?? null;

        if (!$email) {
            throw new RuntimeException('Email not found in Google token');
        }

        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            $user = new User();
            $user->setEmail($email);
            $user->setGoogleId($jwt->sub ?? null);
            $user->setGoogleUserName($jwt->name ?? 'Google User');
            $this->em->persist($user);
            $this->em->flush();
        }

        return $user;
    }
}
