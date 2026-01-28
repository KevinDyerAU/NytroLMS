<?php

namespace Database\Factories;

use App\Models\Timezone;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserDetailFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UserDetail::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => User::factory(), // Ensures a User is created if user_id is not passed in
            'last_logged_in' => $this->faker->dateTimeThisYear,
            'registered_by' => 0,
            'phone' => $this->faker->e164PhoneNumber,
            'address' => $this->faker->streetAddress,
            'country_id' => $this->faker->numberBetween(1, 255),
            'language' => 'en',
            'timezone' => \Arr::random(Timezone::where('region', '=', 'Australia')->get()->pluck('name')->toArray()),
        ];
    }
}
