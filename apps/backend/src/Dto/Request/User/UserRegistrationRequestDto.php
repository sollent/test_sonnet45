<?php

declare(strict_types=1);

namespace App\Dto\Request\User;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UserRegistrationRequestDto
{
    public const MIN_PASSWORD_LENGTH = 6;

    public const MAX_PASSWORD_LENGTH = 40;

    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email,
        #[Assert\NotBlank]
        #[Assert\Length(
            min: self::MIN_PASSWORD_LENGTH,
            max: self::MAX_PASSWORD_LENGTH,
            minMessage: 'user_registration_request_dto.password.min_length',
            maxMessage: 'user_registration_request_dto.password.max_length',
        )]
        public string $password,
    ) {
    }
}
