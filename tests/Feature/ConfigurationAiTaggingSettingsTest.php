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

function makeTaggingPlatform(string $models, bool $default = true): MagicAIPlatform
{
    return MagicAIPlatform::create([
        'label'      => 'Vision Platform',
        'provider'   => 'openai',
        'api_url'    => 'https://api.openai.com/v1',
        'api_key'    => 'test-key',
        'models'     => $models,
        'extras'     => [],
        'is_default' => $default,
        'status'     => true,
    ]);
}

it('renders the AI tagging model field on the configuration page', function () {
    $this->loginAsAdmin();

    $this->get(route('admin.dam.configuration.index'))
        ->assertOk()
        ->assertSee('DAM_AI_TAGGING_MODEL', false)
        ->assertSee(route('admin.dam.configuration.ai-tagging.models'), false);
});

it('serves the selected platform models that can tag an image', function () {
    $this->loginAsAdmin();

    $platform = makeTaggingPlatform('gpt-4o,gpt-image-1,text-embedding-3-small,gpt-5-mini', default: false);

    $response = $this->getJson(route('admin.dam.configuration.ai-tagging.models', ['platform_id' => $platform->id]))
        ->assertOk();

    expect(collect($response->json('options'))->pluck('id')->all())->toBe(['gpt-4o', 'gpt-5-mini']);
});

it('serves the default platform models when no platform is selected', function () {
    $this->loginAsAdmin();

    MagicAIPlatform::query()->update(['is_default' => false]);
    makeTaggingPlatform('gpt-4.1-mini,dall-e-3');

    $response = $this->getJson(route('admin.dam.configuration.ai-tagging.models'))
        ->assertOk();

    expect(collect($response->json('options'))->pluck('id')->all())->toBe(['gpt-4.1-mini']);
});

it('denies the async model options endpoint to an admin without the permission', function () {
    $this->loginWithPermissions('custom', ['dashboard']);

    $this->getJson(route('admin.dam.configuration.ai-tagging.models'))
        ->assertForbidden();
});

it('persists the selected model on update', function () {
    $this->loginAsAdmin();

    $platform = makeTaggingPlatform('gpt-4o,gpt-5-mini');

    $this->post(route('admin.dam.configuration.update'), [
        'DAM_AI_TAGGING_ENABLED'     => '1',
        'DAM_AI_TAGGING_PLATFORM_ID' => $platform->id,
        'DAM_AI_TAGGING_MODEL'       => 'gpt-5-mini',
    ])->assertRedirect(route('admin.dam.configuration.index'));

    expect(DamConfiguration::find('DAM_AI_TAGGING_MODEL')->value)->toBe('gpt-5-mini');
});

it('clears the saved model when none is selected', function () {
    $this->loginAsAdmin();

    $platform = makeTaggingPlatform('gpt-4o');
    DamConfiguration::updateOrCreate(['key' => 'DAM_AI_TAGGING_MODEL'], ['value' => 'gpt-4o']);

    $this->post(route('admin.dam.configuration.update'), [
        'DAM_AI_TAGGING_ENABLED'     => '1',
        'DAM_AI_TAGGING_PLATFORM_ID' => $platform->id,
    ])->assertRedirect(route('admin.dam.configuration.index'));

    expect(DamConfiguration::find('DAM_AI_TAGGING_MODEL')->value)->toBe('');
});

it('rejects a model that the selected platform cannot use for tagging', function (string $model) {
    $this->loginAsAdmin();

    $platform = makeTaggingPlatform('gpt-4o,gpt-image-1');

    $this->post(route('admin.dam.configuration.update'), [
        'DAM_AI_TAGGING_ENABLED'     => '1',
        'DAM_AI_TAGGING_PLATFORM_ID' => $platform->id,
        'DAM_AI_TAGGING_MODEL'       => $model,
    ])->assertSessionHasErrors('DAM_AI_TAGGING_MODEL');
})->with(['gpt-image-1', 'not-on-this-platform']);
