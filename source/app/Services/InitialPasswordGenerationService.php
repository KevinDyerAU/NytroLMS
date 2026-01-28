<?php

namespace App\Services;

use Faker\Factory as FakerFactory;

class InitialPasswordGenerationService
{
    /**
     * Generate a random password using a random latin word and padded number.
     *
     * @return string
     */
    public function generateInitialPassword(): string
    {
        $faker = FakerFactory::create();

        $word = ucfirst($faker->word());

        return $word . str_pad(random_int(0, 99), 2, '0', STR_PAD_LEFT);
    }
}
