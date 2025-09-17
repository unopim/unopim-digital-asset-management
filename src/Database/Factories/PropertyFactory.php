<?php

namespace Webkul\DAM\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\AssetProperty;

class PropertyFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AssetProperty::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name'         => $this->faker->words(2, true),
            'type'         => 'Text',
            'value'        => $this->faker->sentence(),
            'language'     => 'English',
            'dam_asset_id' => Asset::factory(),
        ];
    }
}
