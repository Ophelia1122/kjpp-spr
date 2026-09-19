<?php

namespace Database\Factories;

use App\Models\Role;
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
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            // Default role paling minim izin (Surveyor) — supaya test
            // yang tidak secara eksplisit butuh role tertentu tetap
            // "aman" (tidak sengaja punya akses penuh Administrator).
            'role_id' => Role::where('slug', Role::SURVEYOR)->first()?->id,
            'is_active' => true,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => ['email_verified_at' => null]);
    }

    /**
     * Helper baru: ->administrator() untuk test yang butuh akses penuh.
     * Contoh: User::factory()->administrator()->create();
     */
    public function administrator(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::where('slug', Role::ADMINISTRATOR)->first()?->id,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
