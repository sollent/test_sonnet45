<?php

namespace App\TestsUtilities\Story;

use App\TestsUtilities\Factory\UserFactory;
use Zenstruck\Foundry\Story;

final class DefaultUserStory extends Story
{
    public function build(): void
    {
        UserFactory::createMany(10);
    }
}
