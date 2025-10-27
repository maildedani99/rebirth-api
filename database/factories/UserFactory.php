<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'firstName' => $this->faker->firstName(),
            'lastName'  => $this->faker->lastName(),
            'email'     => $this->faker->unique()->safeEmail(),
            'password'  => Hash::make('password'),
            'dni'       => strtoupper($this->faker->bothify('########?')),
            'role'      => $this->faker->randomElement(['admin','teacher','client']),
            'isActive'  => true,
            'status'    => 'active',
            'coursePriceCents' => $this->faker->randomElement([0, 9900, 14900, 19900]),
            'tutor_id'  => null,
            'depositStatus' => 'pending',
            'finalPayment'  => 'pending',
            'contractSigned'=> false,
        ];
    }
}
