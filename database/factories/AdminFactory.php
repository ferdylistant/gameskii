<?php

namespace Database\Factories;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdminFactory extends Factory
{
    protected $model = Admin::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'password' => app('hash')->make('admin'),
            'role_id' => '1',
            'ip_address' => $this->faker->ipv4(),
            'last_login' => $this->faker->dateTime()
        ];
    }
}
