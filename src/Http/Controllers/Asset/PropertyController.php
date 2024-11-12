<?php

namespace Webkul\DAM\Http\Controllers\Asset;

use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Core\Filesystem\FileStorer;
use Webkul\DAM\DataGrids\Asset\AssetPropertyDataGrid;
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
        protected FileStorer $fileStorer
    ) {}

    /**
     * For the asset properties route
     *
     * @return void
     */
    public function properties(int $id)
    {
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
        $messages = [
            'name.required' => 'The name field is required.',
            'name.unique'   => 'This property already exists.',
        ];

        $this->validate(request(), [
            'name' => 'required|min:3|max:100|unique:dam_asset_properties,name,NULL,id,dam_asset_id,'.$id,
        ], $messages);

        $this->assetPropertyRepository->create(array_merge(request()->only([
            'name',
            'type',
            'language',
            'value',
        ]), ['dam_asset_id' => $id]));

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

        $this->validate(request(), [
            'name'  => 'required|min:3|max:100|unique:dam_asset_properties,name,NULL,id,dam_asset_id,'.$id,
            'value' => 'required',
        ]);

        $this->assetPropertyRepository->update(request()->only([
            'value',
        ]), $id);

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
}
