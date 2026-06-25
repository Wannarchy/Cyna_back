<?php

namespace Tests\Concerns;

use App\Models\User;
use Laravel\Sanctum\Sanctum;

trait CreatesUsers
{
    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    protected function createAdmin(array $attributes = []): User
    {
        return User::factory()->admin()->create($attributes);
    }

    protected function actingAsUser(?User $user = null): User
    {
        $user ??= $this->createUser();
        Sanctum::actingAs($user);

        return $user;
    }

    protected function actingAsAdmin(?User $admin = null): User
    {
        $admin ??= $this->createAdmin();
        Sanctum::actingAs($admin);

        return $admin;
    }
}
