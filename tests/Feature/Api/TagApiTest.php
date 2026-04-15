<?php

use Webkul\DAM\Models\Tag;

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

it('fetches a tag by id via api', function () {
    $tag = Tag::create(['name' => 'api-tag']);

    $response = $this->withHeaders($this->headers)
        ->getJson(route('admin.api.dam.tags.get', $tag->id));

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $tag->id);
});

it('returns 404 when tag not found via api', function () {
    $this->withHeaders($this->headers)
        ->getJson(route('admin.api.dam.tags.get', 999999))
        ->assertStatus(404);
});
