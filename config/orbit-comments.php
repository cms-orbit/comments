<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Comment System Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the comment system.
    | 각 설정은 프론트엔드 컴포넌트와 백엔드 컨트롤러에서 사용됩니다.
    |
    */

    // Comment moderation settings
    // 댓글 검토 설정 - 관리자가 댓글을 승인/거부할 수 있는 기능
    'moderation' => [
        'enabled' => env('COMMENTS_MODERATION_ENABLED', false), // 댓글 검토 기능 활성화 여부
        'auto_approve' => env('COMMENTS_AUTO_APPROVE', true), // 로그인한 사용자의 댓글 자동 승인 여부
        'require_approval' => env('COMMENTS_REQUIRE_APPROVAL', false), // 모든 댓글에 승인 필요 여부
    ],

    // Notification settings
    // 알림 설정 - 댓글이 작성되거나 반응이 있을 때 알림을 보내는 기능
    'notifications' => [
        'email' => env('COMMENTS_EMAIL_NOTIFICATIONS', true),
        'database' => env('COMMENTS_DATABASE_NOTIFICATIONS', true), // 알림 기능 활성화 여부
    ],

    // Rating system settings
    // 평점 시스템 설정 - 댓글과 함께 평점을 매길 수 있는 기능
    'ratings' => [
        'enabled' =>  true, // 평점 시스템 활성화 여부
        'max_rating' =>  5, // 최대 평점 (1-5점)
        'allow_half_ratings' =>  true, // 0.5점 단위 평점 허용 여부
        'categories' => [
            'overall' => [
                'title' => 'Overall Rating',
                'max_rating' => 5,
                'increment' => 0.5,
                'border_width' => 0,
                'border_color' => '#ffd700',
                'fill_color' => '#ffd700',
                'star_size' => 25,
                'star_spacing' => 2,
                'show_rating' => false,
                'read_only' => false,
                'disable_click' => false,
                'rtl' => false,
                'fixed_points' => null,
                'glow' => 0,
                'glow_color' => '#ffd700',
                'text_class' => 'text-sm text-gray-600',
                'required' => true,
            ],
            'cleanliness' => [
                'title' => 'Clean Rating',
                'max_rating' => 5,
                'increment' => 0.5,
                'border_width' => 2,
                'border_color' => '#10b981',
                'fill_color' => '#10b981',
                'star_size' => 18,
                'star_spacing' => 1,
                'show_rating' => true,
                'read_only' => false,
                'disable_click' => false,
                'rtl' => false,
                'fixed_points' => null,
                'glow' => 0,
                'glow_color' => '#10b981',
                'text_class' => 'text-sm text-gray-600',
                'required' => false,
            ],
        ],
    ],

    // Reaction system settings
    // 리액션 시스템 설정 - 댓글에 이모지 반응을 할 수 있는 기능
    'reactions' => [
        'enabled' => env('COMMENTS_REACTIONS_ENABLED', true), // 활성화
        'types' => [
            'like' => '👍', // CommentItem.vue에서 사용
            'love' => '❤️', // CommentItem.vue에서 사용
            'laugh' => '😂', // CommentItem.vue에서 사용
            'wow' => '😮', // CommentItem.vue에서 사용
            'sad' => '😢', // CommentItem.vue에서 사용
            'angry' => '😠', // CommentItem.vue에서 사용
        ],
    ],

    // Comment display settings
    // 댓글 표시 설정 - 프론트엔드에서 댓글을 어떻게 표시할지 결정
    'display' => [
        'per_page' => env('COMMENTS_PER_PAGE', 10), // 페이지당 댓글 수 (Pagination.vue에서 사용)
        'max_depth' => env('COMMENTS_MAX_DEPTH', 5), // 최대 답글 깊이 (CommentItem.vue에서 사용)
        'show_avatars' => env('COMMENTS_SHOW_AVATARS', true), // 사용자 아바타 표시 여부
        'show_timestamps' => env('COMMENTS_SHOW_TIMESTAMPS', true), // 시간 표시 여부
    ],

    // Security settings
    // 보안 설정 - 스팸 방지 및 요청 제한 기능
    'security' => [
        'rate_limit' => [
            'enabled' => env('COMMENTS_RATE_LIMIT_ENABLED', true), // 요청 제한 활성화 여부
            'max_attempts' => env('COMMENTS_RATE_LIMIT_MAX_ATTEMPTS', 5), // 최대 시도 횟수
            'decay_minutes' => env('COMMENTS_RATE_LIMIT_DECAY_MINUTES', 1), // 제한 시간 (분)
        ],
        'spam_protection' => [
            'enabled' => env('COMMENTS_SPAM_PROTECTION_ENABLED', true), // 스팸 방지 활성화 여부
            'honeypot_field' => env('COMMENTS_HONEYPOT_FIELD', 'website'), // 허니팟 필드명 (CommentForm.vue에서 사용)
        ],
    ],

    // Guest comment settings
    // 게스트 댓글 설정 - 로그인하지 않은 사용자의 댓글 작성 관련 설정
    'guest' => [
        'enabled' => false,
        'require_email' => env('COMMENTS_GUEST_REQUIRE_EMAIL', true), // 이메일 필수 여부 (CommentForm.vue에서 사용)
        'require_name' => env('COMMENTS_GUEST_REQUIRE_NAME', true), // 이름 필수 여부 (CommentForm.vue에서 사용)
        'require_password' => env('COMMENTS_GUEST_REQUIRE_PASSWORD', true), // 비밀번호 필수 여부 (수정 시 사용)
    ],

    // Secret comment settings
    // 비밀글 설정 - 특정 권한을 가진 사용자만 볼 수 있는 댓글
    'secret' => [
        'enabled' => env('COMMENTS_SECRET_ENABLED', true), // 비밀글 기능 활성화 여부
        'roles' => env('COMMENTS_SECRET_ROLES', 'admin,moderator'), // 비밀글을 볼 수 있는 역할 (쉼표로 구분)
    ],

    // File upload settings
    // 파일 업로드 설정 - 댓글에 이미지 첨부 기능
    'uploads' => [
        'enabled' => env('COMMENTS_UPLOADS_ENABLED', false), // 파일 업로드 활성화 여부
        'max_size' => env('COMMENTS_MAX_FILE_SIZE', 2048), // 최대 파일 크기 (KB)
        'allowed_types' => [
            'image/jpeg', // 허용된 파일 타입
            'image/png',
            'image/gif',
            'image/webp',
        ],
        'disk' => env('MEDIA_DISK', 'public'), // 업로드 디스크
        'path' => env('COMMENTS_UPLOAD_PATH', 'comments'), // 업로드 경로
    ],
];
