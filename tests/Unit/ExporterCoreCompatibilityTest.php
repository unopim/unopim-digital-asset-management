<?php

use Webkul\DAM\Helpers\Exporters\Category\Exporter as DamCategoryExporter;
use Webkul\DAM\Helpers\Exporters\Product\Exporter as DamProductExporter;
use Webkul\DAM\Models\Asset;
use Webkul\DataTransfer\Helpers\Exporters\Category\Exporter as CoreCategoryExporter;
use Webkul\DataTransfer\Helpers\Exporters\Product\Exporter as CoreProductExporter;

$overrides = [
    [DamProductExporter::class, CoreProductExporter::class],
    [DamCategoryExporter::class, CoreCategoryExporter::class],
];

it('keeps every DAM exporter override signature-compatible with its core parent', function () use ($overrides) {
    $signature = function (ReflectionMethod $method): string {
        $params = array_map(function (ReflectionParameter $p) {
            return ($p->hasType() ? (string) $p->getType() : 'mixed')
                .' $'.$p->getName()
                .($p->isDefaultValueAvailable() ? ' = ...' : '');
        }, $method->getParameters());

        return $method->getName()
            .'('.implode(', ', $params).')'
            .': '.($method->hasReturnType() ? (string) $method->getReturnType() : 'mixed');
    };

    foreach ($overrides as [$damClass, $coreClass]) {
        $dam = new ReflectionClass($damClass);
        $core = new ReflectionClass($coreClass);

        foreach ($dam->getMethods() as $method) {
            if ($method->getDeclaringClass()->getName() !== $damClass) {
                continue;
            }

            if (! $core->hasMethod($method->getName())) {
                continue;
            }

            $parent = $core->getMethod($method->getName());

            $damReturn = $method->hasReturnType() ? (string) $method->getReturnType() : null;
            $coreReturn = $parent->hasReturnType() ? (string) $parent->getReturnType() : null;

            expect($damReturn)->toBe(
                $coreReturn,
                sprintf(
                    "%s::%s must declare the same return type as %s.\n  core: %s\n  dam:  %s",
                    class_basename($damClass),
                    $method->getName(),
                    class_basename($coreClass),
                    $signature($parent),
                    $signature($method),
                )
            );
        }
    }
});

it('resolves the core exporters to the DAM overrides through the container', function () use ($overrides) {
    foreach ($overrides as [$damClass, $coreClass]) {
        expect(app($coreClass))->toBeInstanceOf($damClass);
    }
});

it('exports asset attribute values as stored paths rather than raw asset ids', function () {
    $exporter = app(CoreProductExporter::class);

    $one = Asset::factory()->create();
    $two = Asset::factory()->create();

    $resolve = new ReflectionMethod($exporter, 'resolveAssetPaths');
    $resolve->setAccessible(true);

    expect($resolve->invoke($exporter, "{$one->id},{$two->id}"))
        ->toBe([$one->path, $two->path]);

    expect($resolve->invoke($exporter, (string) $one->id))
        ->toBe([$one->path]);

    expect($resolve->invoke($exporter, [$one->id, $two->id]))
        ->toBe([$one->path, $two->path]);
});

it('treats an empty asset attribute value as nothing to export', function () {
    $exporter = app(CoreProductExporter::class);

    $resolve = new ReflectionMethod($exporter, 'resolveAssetPaths');
    $resolve->setAccessible(true);

    expect($resolve->invoke($exporter, null))->toBe([]);
    expect($resolve->invoke($exporter, ''))->toBe([]);
    expect($resolve->invoke($exporter, []))->toBe([]);
});
