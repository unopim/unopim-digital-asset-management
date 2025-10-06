<?php

namespace Webkul\DAM\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\DAM\Models\Asset;

class AssetFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Asset::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $fileName = fake()->name().'.jpg';

        return [
            'file_name' => $fileName,
            'file_type' => 'image',
            'file_size' => fake()->numberBetween(10000, 5000000),
            'mime_type' => 'image/jpg',
            'extension' => 'jpg',
            'path'      => 'assets/Root/'.$fileName,
        ];
    }
}
