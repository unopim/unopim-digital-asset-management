<?php

use Webkul\DAM\Models\Directory;

it('redirects guests to login', function () {
    $response = $this->get(route('admin.dam.directory.search', ['q' => 'banners']));

    $response->assertRedirect(route('admin.session.create'));
});

describe('with admin auth', function () {
    beforeEach(fn () => $this->loginAsAdmin());

    it('rejects a query under two characters with 422', function () {
        $response = $this->getJson(route('admin.dam.directory.search', ['q' => 'a']));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('q');
    });

    it('rejects a query over one hundred characters with 422', function () {
        $response = $this->getJson(route('admin.dam.directory.search', ['q' => str_repeat('a', 101)]));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('q');
    });

    it('returns matching directories with breadcrumb path for a valid query', function () {
        Directory::factory()->create(['name' => 'banners']);

        $response = $this->getJson(route('admin.dam.directory.search', ['q' => 'ban']));

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [['id', 'name', 'parent_id', 'path_names']],
        ]);
        $response->assertJsonFragment(['name' => 'banners']);
    });

    it('caps the response at twenty rows', function () {
        for ($i = 0; $i < 25; $i++) {
            Directory::factory()->create(['name' => 'banner-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT)]);
        }

        $response = $this->getJson(route('admin.dam.directory.search', ['q' => 'banner']));

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(20);
    });
});
