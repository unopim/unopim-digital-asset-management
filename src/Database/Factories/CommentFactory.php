<?php

namespace Webkul\DAM\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\DAM\Models\Asset;
use Webkul\DAM\Models\AssetComments;

class CommentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = AssetComments::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'admin_id'     => 1,
            'parent_id'    => null,
            'comments'     => $this->faker->sentence(),
            'dam_asset_id' => Asset::factory(),
        ];
    }
}
