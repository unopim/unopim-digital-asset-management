<?php

namespace Webkul\DAM\Http\Controllers\API\Asset;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Core\Filesystem\FileStorer;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\DAM\Repositories\AssetPropertyRepository;
use Webkul\DAM\Repositories\AssetRepository;

class PropertyController extends Controller
{
    /**
     *  Create instance
     */
    public function __construct(
        protected AssetRepository $assetRepository,
        protected AssetPropertyRepository $assetPropertyRepository,
        protected FileStorer $fileStorer,
        protected LocaleRepository $localeRepository
    ) {}

    /**
     * Get the property
     */
    public function properties(int $id)
    {
        $property = $this->assetPropertyRepository->find($id);

        if (! $property) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.asset.properties.index.not-found'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => trans('dam::app.admin.dam.asset.properties.index.found-success'),
            'data'    => $property,
        ], 200);
    }

    /**
     * Create new Property
     */
    public function addProperty(int $id)
    {
        $messages = [
            'name.required' => trans('dam::app.admin.validation.property.name.required'),
            'name.unique'   => trans('dam::app.admin.validation.property.name.unique'),
        ];

        $this->validate(request(), [
            'type'     => 'required',
            'language' => 'required',
            'value'    => 'required|max:1000',
            'name'     => [
                'required',
                'min:3',
                'max:100',
                Rule::unique('dam_asset_properties')
                    ->where(function ($query) use ($id) {
                        return $query->where('dam_asset_id', $id)
                            ->where('language', request()->get('language'));
                    }),
            ],
        ], $messages);

        $language = request()->get('language');
        $locale = $this->localeRepository->where('code', $language)->where('status', 1)->first();

        if (! $locale) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.validation.property.language.not-found'),
            ], 400);
        }

        $property = $this->assetPropertyRepository->create([
            'name'         => request('name'),
            'type'         => request('type'),
            'language'     => $locale->id,
            'value'        => request('value'),
            'dam_asset_id' => $id,
        ]);

        return response()->json([
            'success'  => true,
            'message'  => trans('dam::app.admin.dam.asset.properties.index.create-success'),
            'property' => $property,
        ], 200);
    }

    /**
     * Properties update
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $property = $this->assetPropertyRepository->find($id);

        if (! $property) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.asset.properties.index.not-found'),
            ], 404);
        }

        $this->validate($request, [
            'name'  => 'required|min:3|max:100|unique:dam_asset_properties,name,'.$id.',id,dam_asset_id,'.$property->dam_asset_id,
            'value' => 'required',
        ]);

        try {
            $updatedProperty = $this->assetPropertyRepository->update($request->only(['name', 'value']), $id);

            return response()->json([
                'success'  => true,
                'message'  => trans('dam::app.admin.dam.asset.properties.index.update-success'),
                'property' => $updatedProperty,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.asset.properties.index.update-failure'),
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete the property
     */
    public function delete(Request $request, int $id): JsonResponse
    {
        $property = $this->assetPropertyRepository->find($id);

        if (! $property) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.asset.properties.index.not-found'),
            ], 404);
        }

        try {
            $this->assetPropertyRepository->delete($id);

            return response()->json([
                'success' => true,
                'message' => trans('dam::app.admin.dam.asset.properties.index.delete-success'),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.asset.properties.index.delete-failed'),
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
