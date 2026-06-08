<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre'    => $this->faker->firstName(),
            'apellido'  => $this->faker->lastName(),
            'email'     => $this->faker->unique()->safeEmail(),
            'cedula'    => null,
            'telefono'  => $this->faker->numerify('09#######'),
            'password'  => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'activo'    => true,
            'modulo_id' => null,
        ];
    }

    /**
     * Usuario inactivo.
     */
    public function inactivo(): static
    {
        return $this->state(fn (array $attributes) => [
            'activo' => false,
        ]);
    }

    /**
     * Usuario con cédula.
     */
    public function conCedula(): static
    {
        return $this->state(fn (array $attributes) => [
            'cedula' => $this->faker->numerify('########'),
        ]);
    }
}
