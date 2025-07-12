<?php

namespace CmsOrbit\Comments\Http\Controllers;

use CmsOrbit\Comments\Models\Comment;
use CmsOrbit\Comments\Models\CommentReaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CommentReactionController extends Controller
{
    /**
     * 댓글 반응 토글
     */
    public function toggle(Request $request, Comment $comment): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:' . implode(',', array_keys(config('comments.reactions.types', []))),
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $type = $request->type;
        $reactorType = Auth::check() ? Auth::user()->getMorphClass() : null;
        $reactorId = Auth::id();

        // 기존 반응 확인
        $existingReaction = $comment->reactions()
            ->where('type', $type)
            ->where('reactor_type', $reactorType)
            ->where('reactor_id', $reactorId)
            ->first();

        if ($existingReaction) {
            // 기존 반응 삭제 (토글)
            $existingReaction->delete();
            $isActive = false;
        } else {
            // 새 반응 생성
            $comment->reactions()->create([
                'type' => $type,
                'reactor_type' => $reactorType,
                'reactor_id' => $reactorId,
                'ip_address' => $request->ip(),
            ]);
            $isActive = true;
        }

        // 반응 요약 업데이트
        $reactionsSummary = $comment->reactions_summary;

        return response()->json([
            'message' => $isActive ? '반응이 추가되었습니다.' : '반응이 제거되었습니다.',
            'is_active' => $isActive,
            'reactions_summary' => $reactionsSummary,
        ]);
    }
} 