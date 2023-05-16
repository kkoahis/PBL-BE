<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\ReplyResource;
use App\Models\Reply;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController as BaseController;

class ReplyController extends BaseController
{
    //
    public function index()
    {
        // return hotels with category and in category get all rooms
        $replies = Reply::get();
        return $this->sendResponse(ReplyResource::collection($replies), 'Replies retrieved successfully.');
    }

    public function show($id)
    {
        $reply = Reply::find($id);

        if (is_null($reply)) {
            return $this->sendError('Reply not found.');
        }

        return $this->sendResponse(new ReplyResource($reply), 'Reply retrieved successfully.');
    }

    public function store(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'user_id' => 'required|exists:users,id,deleted_at,NULL',
            'review_id' => 'required|exists:review,id,deleted_at,NULL',
            'content' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        $reply = Reply::create($input);

        return $this->sendResponse(new ReplyResource($reply), 'Reply created successfully.');
    }

    public function update(Request $request, $id)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            // 'user_id' => 'required',
            'review_id' => 'required|exists:review,id,deleted_at,NULL',
            'content' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', $validator->errors());
        }
        $reply = Reply::find($id);

        if (is_null($reply)) {
            return $this->sendError('Reply not found.');
        }

        // $reply->user_id = $input['user_id'];
        $reply->review_id = $input['review_id'];
        $reply->content = $input['content'];
        $reply->save();

        return $this->sendResponse(new ReplyResource($reply), 'Reply updated successfully.');
    }

    public function destroy($id)
    {
        $reply = Reply::find($id);

        if (is_null($reply)) {
            return $this->sendError('Reply not found.');
        }
        if ($reply->delete()) {
            return $this->sendResponse([], 'Reply deleted successfully.');
        } else {
            return $this->sendError('Reply delete failed.');
        }
    }

    public function restore($id)
    {
        $reply = Reply::onlyTrashed()->find($id);

        if (is_null($reply)) {
            return $this->sendError('Reply not found.');
        }

        if ($reply->restore()) {
            return $this->sendResponse([], 'Reply restored successfully.');
        } else {
            return $this->sendError('Reply restore failed.');
        }
    }

    public function getByReviewId($id)
    {
        $reply = Reply::get()->where('review_id', $id);
        // echo $reply;

        if (is_null($reply)) {
            return $this->sendError('Reply not found.');
        }

        if($reply->count() > 0)
            return $this->sendResponse(ReplyResource::collection($reply), 'Reply retrieved successfully.');
        else {
            return $this->sendError('Reply not found.');
        }
    }

    public function deleteByReviewId($id)
    {
        $reply = Reply::get()->where('review_id', $id);
        // echo $reply;

        if (is_null($reply)) {
            return $this->sendError('Reply not found.');
        }

        if($reply->count() > 0) {
            foreach ($reply as $r) {
                $r->delete();
            }
            return $this->sendResponse([], 'Reply deleted successfully.');
        }
        else {
            return $this->sendError('Reply not found.');
        }
    }

    public function restoreByReviewId($id)
    {
        $reply = Reply::onlyTrashed()->get()->where('review_id', $id);
        // echo $reply;

        if (is_null($reply)) {
            return $this->sendError('Reply not found.');
        }

        if($reply->count() > 0) {
            foreach ($reply as $r) {
                $r->restore();
            }
            return $this->sendResponse([], 'Reply restored successfully.');
        }
        else {
            return $this->sendError('Reply not found.');
        }
    }
}
