<?php

namespace Webkul\DAM\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\DAM\Models\Directory;

class DirectoryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Directory::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name'      => $this->faker->words(2, true),
            'parent_id' => null,
        ];
    }

    /**
     * Indicate that the directory has a parent.
     */
    public function withParent(?Directory $parent = null): static
    {
        return $this->state(function (array $attributes) use ($parent) {
            return [
                'parent_id' => $parent?->id ?? Directory::factory(),
            ];
        });
    }
}
