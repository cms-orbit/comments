<?php

namespace CmsOrbit\Comments\Http\Controllers;

use CmsOrbit\Comments\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CommentController extends Controller
{
    /**
     * 댓글 목록 조회
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'commentable_type' => 'required|string',
            'commentable_id' => 'required|integer',
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $query = Comment::query()
            ->where('commentable_type', $request->commentable_type)
            ->where('commentable_id', $request->commentable_id)
            ->where('is_approved', true)
            ->whereNull('parent_id')
            ->with(['author', 'replies.author', 'reactions', 'ratings'])
            ->orderBy('created_at', 'desc');

        $perPage = $request->get('per_page', config('comments.display.per_page', 10));
        $comments = $query->paginate($perPage);

        return response()->json([
            'comments' => $comments->items(),
            'pagination' => [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
                'from' => $comments->firstItem(),
                'to' => $comments->lastItem(),
                'prev_page_url' => $comments->previousPageUrl(),
                'next_page_url' => $comments->nextPageUrl(),
            ]
        ]);
    }

    /**
     * 댓글 생성
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'commentable_type' => 'required|string',
            'commentable_id' => 'required|integer',
            'parent_id' => 'nullable|integer|exists:comments,id',
            'content' => 'required|string|max:10000',
            'guest_name' => 'nullable|string|max:255',
            'guest_email' => 'nullable|email|max:255',
            'ratings' => 'nullable|array',
            'ratings.*' => 'numeric|min:0|max:5',
            'website' => 'nullable|string', // honeypot
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 스팸 방지 (honeypot)
        if (config('comments.security.spam_protection.enabled', true)) {
            $honeypotField = config('comments.security.spam_protection.honeypot_field', 'website');
            if (!empty($request->input($honeypotField))) {
                return response()->json(['message' => '스팸으로 감지되었습니다.'], 403);
            }
        }

        // 게스트 댓글 검증
        if (!Auth::check()) {
            if (config('comments.guest.require_name', true) && empty($request->guest_name)) {
                return response()->json(['errors' => ['guest_name' => ['이름은 필수입니다.']]], 422);
            }
            if (config('comments.guest.require_email', true) && empty($request->guest_email)) {
                return response()->json(['errors' => ['guest_email' => ['이메일은 필수입니다.']]], 422);
            }
        }

        // 댓글 깊이 제한
        if ($request->parent_id) {
            $parentComment = Comment::find($request->parent_id);
            if (!$parentComment) {
                return response()->json(['errors' => ['parent_id' => ['부모 댓글을 찾을 수 없습니다.']]], 422);
            }

            $maxDepth = config('comments.display.max_depth', 5);
            if ($parentComment->depth >= $maxDepth) {
                return response()->json(['errors' => ['parent_id' => ['답글 깊이 제한에 도달했습니다.']]], 422);
            }
        }

        // 댓글 생성
        $commentData = [
            'commentable_type' => $request->commentable_type,
            'commentable_id' => $request->commentable_id,
            'parent_id' => $request->parent_id,
            'content' => $request->content,
            'guest_name' => $request->guest_name,
            'guest_email' => $request->guest_email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        // 로그인한 사용자인 경우
        if (Auth::check()) {
            $commentData['author_type'] = Auth::user()->getMorphClass();
            $commentData['author_id'] = Auth::id();
        }

        // 자동 승인 설정
        if (config('comments.moderation.auto_approve', true)) {
            $commentData['is_approved'] = true;
        }

        $comment = Comment::create($commentData);

        // 평점 저장
        if ($request->has('ratings') && is_array($request->ratings)) {
            foreach ($request->ratings as $category => $rating) {
                if ($rating > 0) {
                    $comment->ratings()->create([
                        'category' => $category,
                        'rating' => $rating,
                        'rater_type' => Auth::check() ? Auth::user()->getMorphClass() : null,
                        'rater_id' => Auth::id(),
                        'ip_address' => $request->ip(),
                    ]);
                }
            }
        }

        // 관계 로드
        $comment->load(['author', 'reactions', 'ratings']);

        return response()->json([
            'message' => '댓글이 성공적으로 작성되었습니다.',
            'comment' => $comment
        ], 201);
    }

    /**
     * 댓글 조회
     */
    public function show(Comment $comment): JsonResponse
    {
        if (!$comment->is_approved) {
            return response()->json(['message' => '승인되지 않은 댓글입니다.'], 403);
        }

        $comment->load(['author', 'replies.author', 'reactions', 'ratings']);

        return response()->json(['comment' => $comment]);
    }

    /**
     * 댓글 수정
     */
    public function update(Request $request, Comment $comment): JsonResponse
    {
        // 권한 체크
        if (!Auth::check() || (Auth::id() !== $comment->author_id && !Auth::user()->is_admin)) {
            return response()->json(['message' => '권한이 없습니다.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:10000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $comment->update([
            'content' => $request->content,
        ]);

        $comment->load(['author', 'reactions', 'ratings']);

        return response()->json([
            'message' => '댓글이 성공적으로 수정되었습니다.',
            'comment' => $comment
        ]);
    }

    /**
     * 댓글 삭제
     */
    public function destroy(Comment $comment): JsonResponse
    {
        // 권한 체크
        if (!Auth::check() || (Auth::id() !== $comment->author_id && !Auth::user()->is_admin)) {
            return response()->json(['message' => '권한이 없습니다.'], 403);
        }

        $comment->delete();

        return response()->json([
            'message' => '댓글이 성공적으로 삭제되었습니다.'
        ]);
    }
} 