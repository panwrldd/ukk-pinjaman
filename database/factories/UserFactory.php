<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        $username = strtolower(Str::slug(fake()->unique()->userName(), '.'));

        return [
            'name' => fake()->name(),
            'username' => substr($username, 0, 45),
            'email' => fake()->unique()->safeEmail(),
            'identity_number' => (string) fake()->unique()->numerify('2026######'),
            'password' => static::$password ??= Hash::make('password123'),
            'role' => 'peminjam',
            'phone' => fake()->numerify('0812########'),
            'address' => fake()->address(),
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => 'admin']);
    }

    public function petugas(): static
    {
        return $this->state(fn () => ['role' => 'petugas']);
    }

    public function peminjam(): static
    {
        return $this->state(fn () => ['role' => 'peminjam']);
    }
}
