<?php

namespace Webkul\DAM\Http\Controllers\Asset;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\DAM\Repositories\AssetCommentsRepository;
use Webkul\DAM\Repositories\AssetRepository;
use Webkul\User\Repositories\AdminRepository;

class CommentController extends Controller
{
    /**
     *  Create instance
     */
    public function __construct(
        protected AssetRepository $assetRepository,
        protected AssetCommentsRepository $assetCommentRepository,
        protected AdminRepository $adminRepository,
    ) {}

    /**
     * To fetch the comments
     */
    public function comments($id)
    {
        $property = $this->assetCommentRepository->findOrFail($id);

        return new JsonResponse($property);
    }

    /**
     * To fetch User Info
     *
     * @param  int  $id
     */
    public function getUserInfo($id): JsonResponse
    {
        $user = $this->adminRepository->findOrFail($id);


        $timezone = ['id' => $user?->timezone, 'label' => $user?->timezone];

        return new JsonResponse([
            'user'     => [
                'name' => $user->name,
                'status' => (bool) $user->status,
            ],
            'timezone' => $timezone,
        ]);
    }

    /**
     * create new comment
     */
    public function commentCreate($id)
    {
        $messages = [
            'comments.required' => trans('dam::app.admin.validation.comment.required'),
        ];

        $this->validate(request(), [
            'comments' => 'required|min:2|max:1000',
        ], $messages);

        $this->assetCommentRepository->create(array_merge(request()->only([
            'comments',
            'parent_id',
        ]), [
            'admin_id'     => Auth::id(),
            'dam_asset_id' => $id,
        ]));

        return new JsonResponse([
            'message' => trans('dam::app.admin.dam.asset.comments.create.create-success'),
        ]);
    }

    /**
     * update the comment message.
     *
     * @return void
     */
    public function commentUpdate()
    {
        $id = request('id');

        $this->validate(request(), [
            'name'  => 'required|min:3|max:13|unique:dam_asset_comments,name,'.$id,
            'value' => 'required',
        ]);

        $this->assetCommentRepository->update(request()->only([
            'value',
        ]), $id);

        return new JsonResponse([
            'message' => trans('dam::app.admin.dam.comments.index.update-success'),
        ]);
    }

    /**
     * Delete the comment thread
     *
     * @return void
     */
    public function commentDelete()
    {
        $id = request('id');

        try {
            $this->assetCommentRepository->delete($id);

            return new JsonResponse([
                'message' => trans('dam::app.admin.dam.comments.index.delete-success'),
            ], 200);
        } catch (\Exception $e) {
            report($e);
        }

        return new JsonResponse([
            'message' => trans('dam::app.admin.dam.comments.index.delete-failed'),
        ], 500);
    }
}
