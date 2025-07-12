# CMS-Orbit Comments

CMS-Orbit을 위한 포괄적인 댓글 시스템입니다. 반응(이모지), 평점, 알림 기능을 포함합니다.

## 주요 기능

- ✅ **Morph 관계 지원**: 모든 모델에 댓글 기능 추가 가능
- ✅ **계층형 댓글**: 답글 기능 지원 (최대 깊이 설정 가능)
- ✅ **반응 시스템**: 이모지 기반 반응 (좋아요, 사랑해요, 웃겨요 등)
- ✅ **평점 시스템**: 별점 평점 (반별점 지원, 카테고리별 평점)
- ✅ **알림 시스템**: 이메일 및 데이터베이스 알림
- ✅ **게스트 댓글**: 로그인하지 않은 사용자도 댓글 작성 가능
- ✅ **스팸 방지**: Honeypot 방식 스팸 방지
- ✅ **권한 관리**: 댓글 수정/삭제 권한 관리
- ✅ **Vue.js 컴포넌트**: 완전한 프론트엔드 컴포넌트 제공

## 설치

### 1. Composer 패키지 설치

```bash
composer require cms-orbit/comments
```

### 2. 설정 파일 발행

```bash
php artisan vendor:publish --tag=comments-config
```

### 3. 마이그레이션 실행

```bash
php artisan vendor:publish --tag=comments-migrations
php artisan migrate
```

### 4. Vue 컴포넌트 등록 (선택사항)

```bash
php artisan vendor:publish --tag=comments-views
```

## 사용법

### 1. 모델에 HasComments Trait 추가

```php
<?php

namespace App\Models;

use CmsOrbit\Comments\Traits\HasComments;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasComments;
    
    // ... 기존 코드
}
```

### 2. Vue 컴포넌트 사용

```vue
<template>
  <CommentList
    :commentable-type="'App\\Models\\Post'"
    :commentable-id="post.id"
    :initial-comments="comments"
    :pagination="pagination"
    :can-comment="true"
    :show-ratings="true"
    :show-reactions="true"
  />
</template>

<script setup>
import CommentList from '@/components/CommentList.vue'

const props = defineProps({
  post: Object,
  comments: Array,
  pagination: Object
})
</script>
```

### 3. 댓글 데이터 로드

```php
<?php

namespace App\Http\Controllers;

use App\Models\Post;

class PostController extends Controller
{
    public function show(Post $post)
    {
        $comments = $post->getPaginatedComments(10);
        
        return Inertia::render('Post/Show', [
            'post' => $post,
            'comments' => $comments->items(),
            'pagination' => [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
                'from' => $comments->firstItem(),
                'to' => $comments->lastItem(),
            ]
        ]);
    }
}
```

## 설정

### 환경 변수

```env
# 댓글 시스템 설정
COMMENTS_MODERATION_ENABLED=false
COMMENTS_AUTO_APPROVE=true
COMMENTS_REQUIRE_APPROVAL=false

# 알림 설정
COMMENTS_EMAIL_NOTIFICATIONS=true
COMMENTS_FROM_ADDRESS=noreply@example.com
COMMENTS_FROM_NAME="CMS-Orbit Comments"
COMMENTS_DATABASE_NOTIFICATIONS=true

# 평점 시스템
COMMENTS_RATINGS_ENABLED=true
COMMENTS_MAX_RATING=5
COMMENTS_ALLOW_HALF_RATINGS=true

# 반응 시스템
COMMENTS_REACTIONS_ENABLED=true

# 보안 설정
COMMENTS_RATE_LIMIT_ENABLED=true
COMMENTS_RATE_LIMIT_MAX_ATTEMPTS=5
COMMENTS_RATE_LIMIT_DECAY_MINUTES=1
COMMENTS_SPAM_PROTECTION_ENABLED=true

# 게스트 댓글
COMMENTS_GUEST_ENABLED=true
COMMENTS_GUEST_REQUIRE_CAPTCHA=true
COMMENTS_GUEST_REQUIRE_EMAIL=true
COMMENTS_GUEST_REQUIRE_NAME=true
```

### 설정 파일 커스터마이징

`config/comments.php` 파일에서 다음을 설정할 수 있습니다:

- **평점 카테고리**: 청결도, 서비스, 시설 등
- **반응 타입**: 좋아요, 사랑해요, 웃겨요 등
- **표시 설정**: 페이지당 댓글 수, 최대 깊이 등
- **보안 설정**: 스팸 방지, 속도 제한 등

## API 엔드포인트

### 댓글 CRUD

```
GET    /api/comments          # 댓글 목록
POST   /api/comments          # 댓글 작성
GET    /api/comments/{id}     # 댓글 조회
PUT    /api/comments/{id}     # 댓글 수정
DELETE /api/comments/{id}     # 댓글 삭제
```

### 반응

```
POST   /api/comments/{id}/reactions  # 반응 토글
```

### 평점

```
POST   /api/comments/{id}/ratings    # 평점 저장
PUT    /api/comments/{id}/ratings    # 평점 수정
DELETE /api/comments/{id}/ratings    # 평점 삭제
```

## Vue 컴포넌트

### CommentList
메인 댓글 목록 컴포넌트

### CommentForm
댓글 작성 폼 컴포넌트

### CommentItem
개별 댓글 표시 컴포넌트

### StarRating
별점 평점 컴포넌트

### Pagination
페이지네이션 컴포넌트

## 알림 시스템

댓글이 작성되거나 답글이 달리면 자동으로 알림이 발송됩니다:

- **새 댓글 알림**: 게시물 작성자에게
- **답글 알림**: 원 댓글 작성자에게
- **채널**: 이메일, 데이터베이스

## 이벤트

다음 이벤트들이 발생합니다:

- `CmsOrbit\Comments\Events\CommentCreated`
- `CmsOrbit\Comments\Events\CommentReplied`

## 라이센스

MIT License

## 기여하기

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 지원

문제가 있거나 기능 요청이 있으시면 [GitHub Issues](https://github.com/cms-orbit/comments/issues)에 등록해 주세요. 