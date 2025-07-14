<template>
    <div class="comment-system">
        <!-- 댓글 작성 폼 -->
        <div v-if="configs" class="mb-8">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Write Comment') }}</h3>

                <!-- 게스트 댓글이 비활성화된 경우 로그인 안내 -->
                <div v-if="!user && !configs.guest.enabled" class="text-center py-8">
                    <p class="text-gray-600 mb-4">{{ __('Please login to write a comment.') }}</p>
                    <button
                        @click="redirectToLogin"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        {{ __('Login') }}
                    </button>
                </div>

                <!-- 댓글 작성 폼 -->
                <CommentForm
                    v-else
                    :commentable-type="commentableType"
                    :commentable-id="commentableId"
                    :show-ratings="configs.ratings.enabled"
                    :rating-categories="configs.ratings.categories"
                    @comment-added="handleCommentAdded"
                />
            </div>
        </div>

        <!-- 댓글 목록 -->
        <div v-if="comments.length > 0" class="comments-list">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                {{ __('Comments') }} ({{ totalComments }})
            </h3>

            <div class="space-y-4">
                <CommentItem
                    v-for="comment in comments"
                    :key="comment.id"
                    :comment="comment"
                    :max-depth="configs?.display?.max_depth || 5"
                    :show-avatars="configs?.display?.show_avatars"
                    :show-timestamps="configs?.display?.show_timestamps"
                    :reactions-enabled="configs?.reactions?.enabled"
                    :reaction-types="configs?.reactions?.types"
                    :rating-categories="configs?.ratings?.categories"
                    @reply-added="handleReplyAdded"
                    @reaction-toggled="handleReactionToggled"
                />
            </div>

            <!-- 페이지네이션 -->
            <div v-if="pagination && pagination.last_page > 1" class="mt-6">
                <Pagination :pagination="pagination" @page-changed="handlePageChanged" />
            </div>
        </div>

        <!-- 댓글이 없을 때 -->
        <div v-else-if="configs" class="text-center py-8 text-gray-500">
            <p>{{ __('No comments yet.') }}</p>
            <p class="mt-2">{{ __('Be the first to comment!') }}</p>
        </div>

        <!-- 설정을 불러오는 중 -->
        <div v-else-if="configs != null" class="text-center py-8 text-gray-500">
            <p>{{ __('Loading comment system...') }}</p>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import {useForm, usePage} from '@inertiajs/vue3'
import CommentForm from '@orbit/comments/Components/CommentForm.vue'
import CommentItem from '@orbit/comments/Components/CommentItem.vue'
import Pagination from '@orbit/comments/Components/Pagination.vue'
import { __ } from '@/lib/translate.js';

const props = defineProps({
    commentableType: {
        type: String,
        required: true
    },
    commentableId: {
        type: [String, Number],
        required: true
    },
    initialComments: {
        type: Array,
        default: () => []
    },
    pagination: {
        type: Object,
        default: null
    },
    maxDepth: {
        type: Number,
        default: 5
    },
    showRatings: {
        type: Boolean,
        default: false
    },
    ratingCategories: {
        type: Array,
        default: () => [
            { key: 'overall', label: __('Overall') }
        ]
    }
})

const emit = defineEmits(['comment-added', 'reply-added', 'reaction-toggled', 'rating-added'])

// 상태
const comments = ref(props.initialComments)
const totalComments = ref(props.pagination?.total || 0)
const isSubmitting = ref(false)
const errors = ref({})
const configs = ref(null)
const isLoading = ref(true)
const pagination = ref(props.pagination)
const user = computed(() => usePage().props.auth.user);

// 댓글 설정 로드
const loadCommentConfigs = async () => {
    try {
        const response = await axios.get(route('orbit-comments.comment.configs', {
            commentable_type: props.commentableType
        }))

        if (response.status === 200) {
            configs.value = response.data.configs
        } else if(response.status === 204) {
            configs.value = null
        }
    } catch (error) {
        console.error('댓글 설정 로드 중 오류:', error)
        configs.value = null
    } finally {
        isLoading.value = false
    }
}

// 댓글 목록 로드
const loadComments = async () => {
    try {
        const response = await axios.get(route('orbit-comments.comment.index'), {
            params: {
                commentable_type: props.commentableType,
                commentable_id: props.commentableId,
                page: 1,
                per_page: configs.value?.display?.per_page || 10
            }
        })

        if (response.status === 200) {
            comments.value = response.data.comments
            totalComments.value = response.data.pagination.total
            pagination.value = response.data.pagination
        }
    } catch (error) {
        console.error('댓글 목록 로드 중 오류:', error)
    }
}

// 댓글 추가 처리
const handleCommentAdded = async (newComment) => {
    // 새 댓글을 목록 맨 앞에 추가
    comments.value.unshift(newComment)
    totalComments.value++
    
    // 페이지네이션 정보 업데이트
    if (pagination.value) {
        pagination.value.total++
        pagination.value.from++
        pagination.value.to++
    }
    
    emit('comment-added', newComment)
}

// 답글 추가 처리
const handleReplyAdded = async (parentId, newReply) => {
    // 부모 댓글을 찾아서 답글 추가
    const parentComment = findCommentById(comments.value, parentId)
    if (parentComment) {
        if (!parentComment.replies) {
            parentComment.replies = []
        }
        parentComment.replies.unshift(newReply)
    }
    
    emit('reply-added', parentId, newReply)
}

// 반응 토글 처리
const handleReactionToggled = (commentId, reactionType, isActive, reactionsSummary) => {
    const comment = findCommentById(comments.value, commentId)
    if (comment) {
        // 서버에서 받은 최신 리액션 상태로 업데이트
        if (reactionsSummary) {
            comment.reactions_summary = reactionsSummary
        } else {
            // fallback: 기존 로직 사용
            if (!comment.reactions_summary) {
                comment.reactions_summary = {}
            }

            if (isActive) {
                comment.reactions_summary[reactionType] = {
                    emoji: getReactionEmoji(reactionType),
                    count: (comment.reactions_summary[reactionType]?.count || 0) + 1
                }
            } else {
                if (comment.reactions_summary[reactionType]) {
                    comment.reactions_summary[reactionType].count--
                    if (comment.reactions_summary[reactionType].count <= 0) {
                        delete comment.reactions_summary[reactionType]
                    }
                }
            }
        }

        emit('reaction-toggled', commentId, reactionType, isActive)
    }
}

// 페이지 변경 처리
const handlePageChanged = async (page) => {
    try {
        const response = await axios.get(route('orbit-comments.comment.index'), {
            params: {
                commentable_type: props.commentableType,
                commentable_id: props.commentableId,
                page: page,
                per_page: configs.value?.display?.per_page || 10
            }
        })

        if (response.status === 200) {
            comments.value = response.data.comments
            pagination.value = response.data.pagination
        }
    } catch (error) {
        console.error('페이지 변경 중 오류:', error)
    }
}

// 댓글 ID로 댓글 찾기 (재귀)
const findCommentById = (commentList, id) => {
    for (const comment of commentList) {
        if (comment.id === id) {
            return comment
        }
        if (comment.replies) {
            const found = findCommentById(comment.replies, id)
            if (found) return found
        }
    }
    return null
}

// 반응 이모지 가져오기
const getReactionEmoji = (type) => {
    if (configs.value?.reactions?.types) {
        return configs.value.reactions.types[type] || '👍'
    }
    return '👍'
}

// 로그인 리다이렉트
const redirectToLogin = () => {
    window.location.href = route('login')
}

// 컴포넌트 마운트 시 초기화
onMounted(async () => {
    await loadCommentConfigs()

    // 설정이 로드된 후에 댓글 목록 로드
    if (configs.value) {
        await loadComments()
    }
})
</script>
