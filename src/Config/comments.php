<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Comment System Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the comment system.
    |
    */

    // Comment moderation settings
    'moderation' => [
        'enabled' => env('COMMENTS_MODERATION_ENABLED', false),
        'auto_approve' => env('COMMENTS_AUTO_APPROVE', true),
        'require_approval' => env('COMMENTS_REQUIRE_APPROVAL', false),
    ],

    // Notification settings
    'notifications' => [
        'email' => [
            'enabled' => env('COMMENTS_EMAIL_NOTIFICATIONS', true),
            'from_address' => env('COMMENTS_FROM_ADDRESS', 'noreply@example.com'),
            'from_name' => env('COMMENTS_FROM_NAME', 'CMS-Orbit Comments'),
        ],
        'database' => [
            'enabled' => env('COMMENTS_DATABASE_NOTIFICATIONS', true),
        ],
    ],

    // Rating system settings
    'ratings' => [
        'enabled' => env('COMMENTS_RATINGS_ENABLED', true),
        'max_rating' => env('COMMENTS_MAX_RATING', 5),
        'allow_half_ratings' => env('COMMENTS_ALLOW_HALF_RATINGS', true),
        'categories' => [
            'default' => ['overall'],
            'custom' => [
                'cleanliness' => '청결도',
                'service' => '서비스',
                'facility' => '시설',
                'value' => '가성비',
            ],
        ],
    ],

    // Reaction system settings
    'reactions' => [
        'enabled' => env('COMMENTS_REACTIONS_ENABLED', true),
        'types' => [
            'like' => '👍',
            'love' => '❤️',
            'laugh' => '😂',
            'wow' => '😮',
            'sad' => '😢',
            'angry' => '😠',
        ],
    ],

    // Comment display settings
    'display' => [
        'per_page' => env('COMMENTS_PER_PAGE', 10),
        'max_depth' => env('COMMENTS_MAX_DEPTH', 5),
        'show_avatars' => env('COMMENTS_SHOW_AVATARS', true),
        'show_timestamps' => env('COMMENTS_SHOW_TIMESTAMPS', true),
    ],

    // Security settings
    'security' => [
        'rate_limit' => [
            'enabled' => env('COMMENTS_RATE_LIMIT_ENABLED', true),
            'max_attempts' => env('COMMENTS_RATE_LIMIT_MAX_ATTEMPTS', 5),
            'decay_minutes' => env('COMMENTS_RATE_LIMIT_DECAY_MINUTES', 1),
        ],
        'spam_protection' => [
            'enabled' => env('COMMENTS_SPAM_PROTECTION_ENABLED', true),
            'honeypot_field' => env('COMMENTS_HONEYPOT_FIELD', 'website'),
        ],
    ],

    // Guest comment settings
    'guest' => [
        'enabled' => env('COMMENTS_GUEST_ENABLED', true),
        'require_captcha' => env('COMMENTS_GUEST_REQUIRE_CAPTCHA', true),
        'require_email' => env('COMMENTS_GUEST_REQUIRE_EMAIL', true),
        'require_name' => env('COMMENTS_GUEST_REQUIRE_NAME', true),
    ],

    // File upload settings
    'uploads' => [
        'enabled' => env('COMMENTS_UPLOADS_ENABLED', false),
        'max_size' => env('COMMENTS_MAX_FILE_SIZE', 2048), // KB
        'allowed_types' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
        ],
        'disk' => env('COMMENTS_UPLOAD_DISK', 'public'),
        'path' => env('COMMENTS_UPLOAD_PATH', 'comments'),
    ],
]; 