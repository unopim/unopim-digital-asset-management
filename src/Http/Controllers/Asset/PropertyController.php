<?php

namespace Webkul\DAM\Http\Controllers\Asset;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\Rule;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\MassDestroyRequest;
use Webkul\Core\Filesystem\FileStorer;
use Webkul\DAM\DataGrids\Asset\AssetPropertyDataGrid;
use Webkul\DAM\Repositories\AssetPropertyRepository;
use Webkul\DAM\Repositories\AssetRepository;
use Webkul\DAM\Traits\AssetAccessControl;

class PropertyController extends Controller
{
    use AssetAccessControl;

    /**
     *  Create instance
     */
    public function __construct(
        protected AssetRepository $assetRepository,
        protected AssetPropertyRepository $assetPropertyRepository,
        protected FileStorer $fileStorer
    ) {}

    /**
     * For the asset properties route
     *
     * @return void
     */
    public function properties(int $id)
    {
        $this->damAuthorizeAsset($id);

        if (request()->ajax()) {
            return app(AssetPropertyDataGrid::class)->toJson();
        }

        return view('dam::asset.properties.index', compact('id'));
    }

    /**
     * Property create $id
     *
     * @return void
     */
    public function propertiesCreate(int $id)
    {
        $this->damAuthorizeAsset($id);

        $messages = [
            'name.required' => trans('dam::app.admin.validation.property.name.required'),
            'name.unique'   => trans('dam::app.admin.validation.property.name.unique'),
        ];

        $this->validate(request(), [
            'type'          => 'required',
            'language'      => 'required',
            'value'         => 'required|max:1000',
            'is_filterable' => 'sometimes|boolean',
            'sort_order'    => 'sometimes|integer|min:0|max:9999',
            'name'          => [
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

        $payload = array_merge(request()->only([
            'name',
            'type',
            'language',
            'value',
        ]), [
            'dam_asset_id'  => $id,
            'is_filterable' => request()->boolean('is_filterable'),
            'sort_order'    => (int) request()->input('sort_order', 0),
        ]);

        $this->assetPropertyRepository->create($payload);

        return new JsonResponse([
            'message' => trans('dam::app.admin.dam.asset.properties.index.create-success'),
        ]);
    }

    /**
     * Property edit section
     *
     * @return void
     */
    public function propertiesEdit(int $id)
    {
        $property = $this->assetPropertyRepository->findOrFail($id);

        $this->damAuthorizeAsset((int) $property->dam_asset_id);

        return new JsonResponse($property);
    }

    /**
     * properties update
     *
     * @param  int  $id
     * @return void
     */
    public function propertiesUpdate()
    {
        $id = request('id');
        $property = $this->assetPropertyRepository->findOrFail($id);
        $this->damAuthorizeAsset((int) $property->dam_asset_id);

        $messages = [
            'name.unique' => trans('dam::app.admin.validation.property.name.unique'),
        ];

        $this->validate(request(), [
            'name' => [
                'required',
                'min:3',
                'max:100',
                Rule::unique('dam_asset_properties')
                    ->ignore($id)
                    ->where(function ($query) use ($property) {
                        return $query->where('dam_asset_id', $property->dam_asset_id)
                            ->where('language', $property->language);
                    }),
            ],
            'value'         => 'required',
            'is_filterable' => 'sometimes|boolean',
            'sort_order'    => 'sometimes|integer|min:0|max:9999',
        ], $messages);

        $payload = array_merge(
            request()->only(['name', 'value']),
            [
                'is_filterable' => request()->boolean('is_filterable'),
                'sort_order'    => (int) request()->input('sort_order', 0),
            ]
        );

        $this->assetPropertyRepository->update($payload, $id);

        return new JsonResponse([
            'message' => trans('dam::app.admin.dam.asset.properties.index.update-success'),
        ]);
    }

    /**
     * properties destroy
     *
     * @param  int  $id
     * @return void
     */
    public function propertiesDestroy()
    {
        $id = request('id');
        $property = $this->assetPropertyRepository->find($id);
        if ($property) {
            $this->damAuthorizeAsset((int) $property->dam_asset_id);
        }
        try {
            $this->assetPropertyRepository->delete($id);

            return new JsonResponse([
                'message' => trans('dam::app.admin.dam.asset.properties.index.delete-success'),
            ], 200);
        } catch (\Exception $e) {
            report($e);
        }

        return new JsonResponse([
            'message' => trans('dam::app.admin.dam.asset.properties.index.delete-failed'),
        ], 500);
    }

    /**
     * Mass delete assets
     */
    public function massDestroy(MassDestroyRequest $massDestroyRequest): JsonResponse
    {
        $assetPropertyIds = $massDestroyRequest->input('indices');

        try {
            foreach ($assetPropertyIds as $assetPropertyId) {
                $asset = $this->assetPropertyRepository->find($assetPropertyId);

                if (isset($asset)) {
                    Event::dispatch('dam.asset.property.delete.before', $assetPropertyId);

                    $this->assetPropertyRepository->delete($assetPropertyId);

                    Event::dispatch('dam.asset.property.delete.after', $assetPropertyId);
                }
            }

            return new JsonResponse([
                'message' => trans('dam::app.admin.dam.asset.datagrid.mass-delete-success'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
