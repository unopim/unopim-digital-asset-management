<?php

namespace Webkul\DAM\Http\Controllers\API\Asset;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\DAM\Repositories\AssetCommentsRepository;
use Webkul\DAM\Repositories\AssetRepository;

class CommentController extends Controller
{
    /**
     *  Create instance
     */
    public function __construct(
        protected AssetRepository $assetRepository,
        protected AssetCommentsRepository $assetCommentRepository,
    ) {}

    /**
     * To fetch the comments
     */
    public function comments(int $id): JsonResponse
    {

        $comment = $this->assetCommentRepository->find($id);
        // $comment = $this->assetCommentRepository->where('dam_asset_id', $id)->get();
        if (! $comment) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.asset.comments.not-found'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $comment,
        ], 200);
    }

    /**
     * Update the specified comment.
     *
     * @return JsonResponse
     */
    public function update(int $id)
    {
        try {
            $comment = $this->assetCommentRepository->find($id);

            if (! $comment) {
                return response()->json([
                    'success' => false,
                    'message' => trans('dam::app.admin.dam.asset.comments.not-found'),
                ], 404);
            }

            $comment = $this->assetCommentRepository->update(request()->only([
                'comments',
            ]), $id);

            return response()->json([
                'success' => true,
                'message' => trans('dam::app.admin.dam.asset.comments.updated-success'),
                'data'    => $comment,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.asset.comments.update-failed'),
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete the specified comment.
     *
     * @return JsonResponse
     */
    public function delete(int $id)
    {
        try {
            $comment = $this->assetCommentRepository->find($id);

            if (! $comment) {
                return response()->json([
                    'success' => false,
                    'message' => trans('dam::app.admin.dam.asset.comments.not-found'),
                ], 404);
            }

            $comment = $this->assetCommentRepository->delete($id);

            return response()->json([
                'success' => true,
                'message' => trans('dam::app.admin.dam.asset.comments.delete-success'),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => trans('dam::app.admin.dam.asset.comments.delete-failed'),
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create the new comment.
     *
     * @return JsonResponse
     */
    public function createComment(Request $request)
    {
        $messages = [
            'comments.required'     => trans('dam::app.admin.validation.comment.required'),
            'dam_asset_id.required' => trans('dam::app.admin.validation.asset.required'),
        ];

        $this->validate($request, [
            'comments'     => 'required|min:2|max:1000',
            'dam_asset_id' => 'required|integer|exists:dam_assets,id',
            'parent_id'    => 'nullable|exists:comments,id',
        ], $messages);

        try {
            $comment = $this->assetCommentRepository->create([
                'comments'     => $request->input('comments'),
                'parent_id'    => $request->input('parent_id') === 'null' ? null : $request->input('parent_id'),
                'admin_id'     => Auth::id(),
                'dam_asset_id' => $request->input('dam_asset_id'),
            ]);

            return response()->json([
                'message' => trans('dam::app.admin.dam.asset.comments.create.create-success'),
                'comment' => $comment,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => trans('dam::app.admin.dam.asset.comments.create.create-failure'),
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
