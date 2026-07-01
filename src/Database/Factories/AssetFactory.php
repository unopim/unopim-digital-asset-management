<?php

namespace Webkul\DAM\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\DAM\Models\Asset;

class AssetFactory extends Factory
{
    protected $model = Asset::class;

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
