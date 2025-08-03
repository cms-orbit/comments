<?php

namespace CmsOrbit\Comments\Http\Controllers;

use CmsOrbit\Comments\Entities\Comment\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CommentController extends Controller
{
    /**
     * 댓글 설정 조회
     */
    public function configs(Request $request): JsonResponse|Response
    {
        $validator = Validator::make($request->all(), [
            'commentable_type' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $commentableType = $request->get('commentable_type');

        // 클래스가 존재하고 HasComments trait을 사용하는지 확인
        if (!class_exists($commentableType)) {
            return response()->json(['error' => '유효하지 않은 모델 타입입니다.'], 400);
        }

        // HasComments trait을 사용하는지 확인
        $traits = class_uses($commentableType);
        $hasCommentsTrait = 'CmsOrbit\Comments\Traits\HasComments';

        if (!in_array($hasCommentsTrait, $traits)) {
            return response(null,204);
        }

        // 모델의 댓글 설정 가져오기
        $configs = $commentableType::commentConfigs();

        return response()->json([
            'configs' => $configs,
            'commentable_type' => $commentableType,
        ]);
    }

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
            ->where('commentable_type', $request->get('commentable_type'))
            ->where('commentable_id', $request->get('commentable_id'))
            ->where('is_approved', true)
            ->whereNull('parent_id')
            ->with(['author', 'replies.author', 'replies.parent', 'reactions', 'ratings', 'commentable'])
            ->orderBy('created_at', 'desc');

        $perPage = $request->get('per_page', config('orbit-comments.display.per_page', 10));
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
            'guest_password' => 'nullable|string|min:6',
            'ratings' => 'nullable|array',
            'ratings.*' => 'numeric|min:0|max:5',
            'is_secret' => 'nullable|boolean',
            'notify_reply' => 'nullable|boolean',
            'website' => 'nullable|string', // honeypot
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // 스팸 방지 검사
        $spamCheck = $this->checkSpam($request);
        $isSpam = $spamCheck['is_spam'];

        // 게스트 댓글 검증
        if (!Auth::check()) {
            if (config('orbit-comments.guest.require_name', true) && empty($request->get('guest_name'))) {
                return response()->json(['errors' => ['guest_name' => ['이름은 필수입니다.']]], 422);
            }
            if (config('orbit-comments.guest.require_email', true) && empty($request->get('guest_email'))) {
                return response()->json(['errors' => ['guest_email' => ['이메일은 필수입니다.']]], 422);
            }
            if (config('orbit-comments.guest.require_password', true) && empty($request->get('guest_password'))) {
                return response()->json(['errors' => ['guest_password' => ['비밀번호는 필수입니다.']]], 422);
            }
        }

        // 댓글 깊이 제한
        if ($request->get('parent_id')) {
            $parentComment = Comment::find($request->get('parent_id'));
            if (!$parentComment) {
                return response()->json(['errors' => ['parent_id' => ['부모 댓글을 찾을 수 없습니다.']]], 422);
            }

            $maxDepth = config('orbit-comments.display.max_depth', 5);
            if ($parentComment->getAttribute('depth') >= $maxDepth) {
                return response()->json(['errors' => ['parent_id' => ['답글 깊이 제한에 도달했습니다.']]], 422);
            }
        }

        // 댓글 생성
        $commentData = [
            'commentable_type' => $request->get('commentable_type'),
            'commentable_id' => $request->get('commentable_id'),
            'parent_id' => $request->get('parent_id'),
            'content' => $request->get('content'),
            'guest_name' => $request->get('guest_name'),
            'guest_email' => $request->get('guest_email'),
            'guest_password' => $request->get('guest_password'),
            'is_secret' => $request->get('is_secret', false),
            'notify_reply' => $request->get('notify_reply', false),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];

        // 로그인한 사용자인 경우
        if (Auth::check()) {
            $user = Auth::user();
            $commentData['author_type'] = $user->getMorphClass();
            $commentData['author_id'] = $user->getAttribute('id');
        }

        // 자동 승인 설정
        if (config('orbit-comments.moderation.auto_approve', true)) {
            $commentData['is_approved'] = true;
        }

        // 스팸 설정
        $commentData['is_spam'] = $isSpam;

        $comment = Comment::create($commentData);

        // 평점 저장 (대댓글이 아닌 경우에만)
        if (!$request->get('parent_id') && $request->has('ratings') && is_array($request->get('ratings'))) {
            foreach ($request->get('ratings') as $category => $rating) {
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
        $comment->load(['author', 'reactions', 'ratings', 'commentable']);

        // 알림 전송 (대댓글인 경우에만)
        if ($request->get('parent_id')) {
            $this->sendReplyNotifications($comment);
        }

        $message = $isSpam ? '댓글이 작성되었지만 스팸으로 감지되어 검토 중입니다.' : '댓글이 성공적으로 작성되었습니다.';

        return response()->json([
            'message' => $message,
            'comment' => $comment,
            'is_spam' => $isSpam
        ], 201);
    }

    /**
     * 댓글 조회
     */
    public function show(Comment $comment): JsonResponse
    {
        if (!$comment->getAttribute('is_approved')) {
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
        if (!Auth::check() || (Auth::id() !== $comment->getAttribute('author_id') && !Auth::user()->hasAccess('orbit_comments.read_secret'))) {
            return response()->json(['message' => '권한이 없습니다.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:10000',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $comment->update([
            'content' => $request->get('content'),
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
        if (!Auth::check() || (Auth::id() !== $comment->getAttribute('author_id') && !Auth::user()->hasAccess('orbit_comments.delete_any'))) {
            return response()->json(['message' => '권한이 없습니다.'], 403);
        }

        $comment->delete();

        return response()->json([
            'message' => '댓글이 성공적으로 삭제되었습니다.'
        ]);
    }

    /**
     * 스팸 검사
     */
    private function checkSpam(Request $request): array
    {
        // 1. Honeypot 검사
        if (config('orbit-comments.security.spam_protection.enabled', true)) {
            $honeypotField = config('orbit-comments.security.spam_protection.honeypot_field', 'website');
            if (!empty($request->get($honeypotField))) {
                return ['is_spam' => true, 'message' => '스팸으로 감지되었습니다.'];
            }
        }

        // 2. IP 기반 스팸 검사
        $ipAddress = $request->ip();
        $recentComments = Comment::where('ip_address', $ipAddress)
            ->where('created_at', '>=', now()->subMinutes(config('orbit-comments.security.rate_limit.minutes', 5)))
            ->count();

        if ($recentComments >= config('orbit-comments.security.rate_limit.max_comments', 3)) {
            return ['is_spam' => true, 'message' => '너무 빠른 댓글 작성입니다. 잠시 후 다시 시도해주세요.'];
        }

        // 3. 키워드 기반 스팸 검사
        $content = strtolower($request->get('content'));
        $spamKeywords = config('orbit-comments.security.spam_keywords', []);

        foreach ($spamKeywords as $keyword) {
            if (str_contains($content, strtolower($keyword))) {
                return ['is_spam' => true, 'message' => '스팸 키워드가 포함되어 있습니다.'];
            }
        }

        // 4. 링크 수 제한
        $linkCount = substr_count($content, 'http://') + substr_count($content, 'https://');
        if ($linkCount > config('orbit-comments.security.max_links', 2)) {
            return ['is_spam' => true, 'message' => '너무 많은 링크가 포함되어 있습니다.'];
        }

        // 5. 중복 댓글 검사
        $recentDuplicate = Comment::where('ip_address', $ipAddress)
            ->where('content', $request->get('content'))
            ->where('commentable_type', $request->get('commentable_type'))
            ->where('commentable_id', $request->get('commentable_id'))
            ->where('created_at', '>=', now()->subHours(24))
            ->exists();

        if ($recentDuplicate) {
            return ['is_spam' => true, 'message' => '동일한 댓글이 이미 작성되었습니다.'];
        }

        return ['is_spam' => false, 'message' => ''];
    }

    /**
     * 스팸 처리
     */
    public function markAsSpam(Comment $comment): JsonResponse
    {
        // 권한 체크
        if (!Auth::check() || !Auth::user()->hasAccess('orbit_comments.spam_any')) {
            return response()->json(['message' => '권한이 없습니다.'], 403);
        }

        $comment->update(['is_spam' => true]);

        return response()->json([
            'message' => '댓글이 스팸으로 처리되었습니다.',
            'comment' => $comment
        ]);
    }

    /**
     * 스팸 해제
     */
    public function unmarkAsSpam(Comment $comment): JsonResponse
    {
        // 권한 체크
        if (!Auth::check() || !Auth::user()->hasAccess('orbit_comments.spam_any')) {
            return response()->json(['message' => '권한이 없습니다.'], 403);
        }

        $comment->update(['is_spam' => false]);

        return response()->json([
            'message' => '댓글이 스팸에서 해제되었습니다.'
        ]);
    }

    /**
     * 답글 알림 전송
     */
    private function sendReplyNotifications(Comment $reply): void
    {
        $parentComment = $reply->parent;

        if (!$parentComment) {
            return;
        }

        // 부모 댓글 작성자에게 알림 전송 (notify_reply가 true인 경우에만)
        if ($parentComment->notify_reply && $parentComment->author) {
            $parentComment->author->notify(new \CmsOrbit\Comments\Notifications\NewReplyNotification($reply));
        }

        // 부모 댓글의 상위 댓글들도 확인하여 알림 전송
        $this->sendNotificationsToParentChain($reply);
    }

    /**
     * 상위 댓글 체인에 알림 전송
     */
    private function sendNotificationsToParentChain(Comment $reply): void
    {
        $currentParent = $reply->parent;

        while ($currentParent) {
            // notify_reply가 true이고 작성자가 있는 경우에만 알림 전송
            if ($currentParent->notify_reply && $currentParent->author) {
                $currentParent->author->notify(new \CmsOrbit\Comments\Notifications\NewReplyNotification($reply));
            }

            $currentParent = $currentParent->parent;
        }
    }
}
