<?php

namespace Webkul\DAM\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\DAM\Models\Directory;

class DirectoryFactory extends Factory
{
    protected $model = Directory::class;

    public function definition(): array
    {
        return [
            'name'      => $this->faker->words(2, true),
            'parent_id' => null,
        ];
    }

    public function withParent(?Directory $parent = null): static
    {
        return $this->state(function (array $attributes) use ($parent) {
            return [
                'parent_id' => $parent?->id ?? Directory::factory(),
            ];
        });
    }
}
