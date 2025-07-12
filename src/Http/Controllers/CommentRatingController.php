<?php

namespace CmsOrbit\Comments\Http\Controllers;

use CmsOrbit\Comments\Models\Comment;
use CmsOrbit\Comments\Models\CommentRating;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CommentRatingController extends Controller
{
    /**
     * 댓글 평점 저장
     */
    public function store(Request $request, Comment $comment): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ratings' => 'required|array',
            'ratings.*' => 'numeric|min:0|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $raterType = Auth::check() ? Auth::user()->getMorphClass() : null;
        $raterId = Auth::id();

        $savedRatings = [];

        foreach ($request->ratings as $category => $rating) {
            if ($rating > 0) {
                // 기존 평점 확인
                $existingRating = $comment->ratings()
                    ->where('category', $category)
                    ->where('rater_type', $raterType)
                    ->where('rater_id', $raterId)
                    ->first();

                if ($existingRating) {
                    // 기존 평점 업데이트
                    $existingRating->update(['rating' => $rating]);
                    $savedRatings[$category] = $rating;
                } else {
                    // 새 평점 생성
                    $newRating = $comment->ratings()->create([
                        'category' => $category,
                        'rating' => $rating,
                        'rater_type' => $raterType,
                        'rater_id' => $raterId,
                        'ip_address' => $request->ip(),
                    ]);
                    $savedRatings[$category] = $rating;
                }
            }
        }

        // 평점 요약 업데이트
        $ratingSummary = $comment->rating_summary;

        return response()->json([
            'message' => '평점이 성공적으로 저장되었습니다.',
            'ratings' => $savedRatings,
            'rating_summary' => $ratingSummary,
        ]);
    }

    /**
     * 댓글 평점 수정
     */
    public function update(Request $request, Comment $comment): JsonResponse
    {
        return $this->store($request, $comment);
    }

    /**
     * 댓글 평점 삭제
     */
    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'categories' => 'required|array',
            'categories.*' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $raterType = Auth::check() ? Auth::user()->getMorphClass() : null;
        $raterId = Auth::id();

        $deletedCategories = [];

        foreach ($request->categories as $category) {
            $existingRating = $comment->ratings()
                ->where('category', $category)
                ->where('rater_type', $raterType)
                ->where('rater_id', $raterId)
                ->first();

            if ($existingRating) {
                $existingRating->delete();
                $deletedCategories[] = $category;
            }
        }

        // 평점 요약 업데이트
        $ratingSummary = $comment->rating_summary;

        return response()->json([
            'message' => '평점이 성공적으로 삭제되었습니다.',
            'deleted_categories' => $deletedCategories,
            'rating_summary' => $ratingSummary,
        ]);
    }
} 