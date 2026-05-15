<?php

namespace Webkul\DAM\Http\Controllers\Asset;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\DAM\Repositories\AssetCommentsRepository;
use Webkul\DAM\Repositories\AssetRepository;
use Webkul\DAM\Traits\AssetAccessControl;
use Webkul\User\Repositories\AdminRepository;

class CommentController extends Controller
{
    use AssetAccessControl;

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
        $this->damAuthorizeAsset((int) $id);

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
        $user = $this->adminRepository->find($id);

        if (! $user) {
            return new JsonResponse([
                'user' => [
                    'id'        => $id,
                    'name'      => 'Deleted user',
                    'image'     => null,
                    'image_url' => null,
                    'status'    => false,
                ],
                'timezone' => null,
            ]);
        }

        return new JsonResponse([
            'user' => [
                'id'        => $user->id,
                'name'      => $user->name,
                'image'     => $user->image,
                'image_url' => $user->image_url,
                'status'    => (bool) $user->status,
            ],
            'timezone' => ['id' => $user?->timezone, 'label' => $user?->timezone],
        ]);
    }

    /**
     * create new comment
     */
    public function commentCreate($id)
    {
        $this->damAuthorizeAsset((int) $id);

        $messages = [
            'comments.required' => trans('dam::app.admin.validation.comment.required'),
        ];

        $this->validate(request(), [
            'comments' => 'required|min:2|max:1000',
        ], $messages);

        $comment = $this->assetCommentRepository->create(array_merge(request()->only([
            'comments',
            'parent_id',
        ]), [
            'admin_id'     => Auth::id(),
            'dam_asset_id' => $id,
        ]));

        $comment->load('admin');

        return new JsonResponse([
            'message' => trans('dam::app.admin.dam.asset.comments.create.create-success'),
            'comment' => $comment,
        ]);
    }

    /**
     * update the comment message.
     */
    public function commentUpdate(): JsonResponse
    {
        $this->validate(request(), [
            'id'       => 'required|integer|exists:dam_asset_comments,id',
            'comments' => 'required|min:2|max:1000',
        ]);

        $id = request('id');
        $comment = $this->assetCommentRepository->findOrFail($id);

        $this->damAuthorizeAsset((int) $comment->dam_asset_id);

        if ($comment->admin_id !== Auth::id()) {
            return new JsonResponse([
                'message' => trans('dam::app.admin.dam.asset.comments.update-failed'),
            ], 403);
        }

        $this->assetCommentRepository->update(['comments' => request('comments')], $id);
        $comment->refresh()->load('admin');

        return new JsonResponse([
            'message' => trans('dam::app.admin.dam.asset.comments.updated-success'),
            'comment' => $comment,
        ]);
    }

    /**
     * Delete the comment thread
     */
    public function commentDelete(): JsonResponse
    {
        $id = request('id');
        $comment = $this->assetCommentRepository->find($id);

        if (! $comment) {
            return new JsonResponse([
                'message' => trans('dam::app.admin.dam.asset.comments.delete-failed'),
            ], 404);
        }

        $this->damAuthorizeAsset((int) $comment->dam_asset_id);

        if ($comment->admin_id !== Auth::id()) {
            return new JsonResponse([
                'message' => trans('dam::app.admin.dam.asset.comments.delete-failed'),
            ], 403);
        }

        try {
            $this->assetCommentRepository->delete($id);

            return new JsonResponse([
                'message' => trans('dam::app.admin.dam.asset.comments.delete-success'),
            ]);
        } catch (\Exception $e) {
            report($e);

            return new JsonResponse([
                'message' => trans('dam::app.admin.dam.asset.comments.delete-failed'),
            ], 500);
        }
    }
}
