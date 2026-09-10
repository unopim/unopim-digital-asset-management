<?php

use Webkul\DAM\Models\DamConfiguration;
use Webkul\MagicAI\Models\MagicAIPlatform;

it('renders the AI tagging settings on the configuration page', function () {
    $this->loginAsAdmin();

    $this->get(route('admin.dam.configuration.index'))
        ->assertOk()
        ->assertSee('dam_ai_tagging_enabled', false);
});

it('persists the enabled flag and platform id on update', function () {
    $this->loginAsAdmin();

    $platform = MagicAIPlatform::create([
        'label'      => 'Vision Platform',
        'provider'   => 'openai',
        'api_url'    => 'https://api.openai.com/v1',
        'api_key'    => 'test-key',
        'models'     => 'gpt-4o',
        'extras'     => [],
        'is_default' => true,
        'status'     => true,
    ]);

    $this->post(route('admin.dam.configuration.update'), [
        'DAM_AI_TAGGING_ENABLED'     => '1',
        'DAM_AI_TAGGING_PLATFORM_ID' => $platform->id,
        'DAM_AI_TAGGING_MAX_TAGS'    => 5,
    ])->assertRedirect(route('admin.dam.configuration.index'));

    expect(DamConfiguration::find('DAM_AI_TAGGING_ENABLED')->value)->toBe('1');
    expect(DamConfiguration::find('DAM_AI_TAGGING_PLATFORM_ID')->value)->toBe((string) $platform->id);
    expect(DamConfiguration::find('DAM_AI_TAGGING_MAX_TAGS')->value)->toBe('5');
});

it('rejects a max tags value outside 1-20', function () {
    $this->loginAsAdmin();

    $this->post(route('admin.dam.configuration.update'), [
        'DAM_AI_TAGGING_ENABLED'  => '1',
        'DAM_AI_TAGGING_MAX_TAGS' => 21,
    ])->assertSessionHasErrors('DAM_AI_TAGGING_MAX_TAGS');

    $this->post(route('admin.dam.configuration.update'), [
        'DAM_AI_TAGGING_ENABLED'  => '1',
        'DAM_AI_TAGGING_MAX_TAGS' => 0,
    ])->assertSessionHasErrors('DAM_AI_TAGGING_MAX_TAGS');
});

it('denies configuration update to an admin without the permission', function () {
    $this->loginWithPermissions('custom', ['dashboard']);

    $this->post(route('admin.dam.configuration.update'), [
        'DAM_AI_TAGGING_ENABLED' => '1',
    ])->assertForbidden();
});

it('rejects an invalid platform id', function () {
    $this->loginAsAdmin();

    $this->post(route('admin.dam.configuration.update'), [
        'DAM_AI_TAGGING_ENABLED'     => '1',
        'DAM_AI_TAGGING_PLATFORM_ID' => 999999,
    ])->assertSessionHasErrors('DAM_AI_TAGGING_PLATFORM_ID');
});

it('serves only vision-capable platforms from the async platform options endpoint', function () {
    $this->loginAsAdmin();

    $vision = MagicAIPlatform::create([
        'label'      => 'Vision Platform',
        'provider'   => 'openai',
        'api_url'    => 'https://api.openai.com/v1',
        'api_key'    => 'test-key',
        'models'     => 'gpt-4o',
        'extras'     => [],
        'is_default' => true,
        'status'     => true,
    ]);

    $textOnly = MagicAIPlatform::create([
        'label'      => 'Text-Only Platform',
        'provider'   => 'anthropic',
        'api_url'    => 'https://api.anthropic.com/v1',
        'api_key'    => 'test-key',
        'models'     => 'claude-3',
        'extras'     => [],
        'is_default' => false,
        'status'     => true,
    ]);

    $response = $this->getJson(route('admin.dam.configuration.ai-tagging.platforms'))
        ->assertOk();

    $ids = collect($response->json('options'))->pluck('id');

    expect($ids)->toContain((string) $vision->id);
    expect($ids)->not->toContain((string) $textOnly->id);
});

it('denies the async platform options endpoint to an admin without the permission', function () {
    $this->loginWithPermissions('custom', ['dashboard']);

    $this->getJson(route('admin.dam.configuration.ai-tagging.platforms'))
        ->assertForbidden();
});
