<template>
  <div class="comment-item" :class="{ 'ml-8': comment.depth > 0 }">
    <div class="bg-white rounded-lg border border-gray-200 p-4">
      <!-- 댓글 헤더 -->
      <div class="flex items-start justify-between mb-3">
        <div class="flex items-center space-x-3">
          <!-- 아바타 -->
          <div v-if="comment.author_avatar" class="flex-shrink-0">
            <img
              :src="comment.author_avatar"
              :alt="comment.author_name"
              class="h-8 w-8 rounded-full"
            />
          </div>
          <div v-else class="flex-shrink-0">
            <div class="h-8 w-8 rounded-full bg-gray-300 flex items-center justify-center">
              <span class="text-sm font-medium text-gray-700">
                {{ comment.author_name.charAt(0).toUpperCase() }}
              </span>
            </div>
          </div>

          <!-- 작성자 정보 -->
          <div>
            <div class="text-sm font-medium text-gray-900">
              {{ comment.author_name }}
            </div>
            <div class="text-xs text-gray-500">
              {{ formatDate(comment.created_at) }}
            </div>
          </div>
        </div>

        <!-- 메뉴 버튼 -->
        <div class="flex items-center space-x-2">
          <button
            v-if="canEdit(comment)"
            @click="editComment"
            class="text-gray-400 hover:text-gray-600"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
            </svg>
          </button>
          
          <button
            v-if="canDelete(comment)"
            @click="deleteComment"
            class="text-gray-400 hover:text-red-600"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
          </button>
        </div>
      </div>

      <!-- 댓글 내용 -->
      <div class="mb-4">
        <p class="text-gray-900 whitespace-pre-wrap">{{ comment.content }}</p>
      </div>

      <!-- 평점 표시 -->
      <div v-if="comment.rating_summary && Object.keys(comment.rating_summary).length > 0" class="mb-3">
        <div class="space-y-1">
          <div v-for="(rating, category) in comment.rating_summary" :key="category" class="flex items-center space-x-2">
            <span class="text-xs text-gray-600">{{ getCategoryLabel(category) }}:</span>
            <StarRating
              :rating="rating.average"
              :max-rating="5"
              :readonly="true"
              :size="'sm'"
            />
            <span class="text-xs text-gray-500">({{ rating.count }})</span>
          </div>
        </div>
      </div>

      <!-- 반응 버튼들 -->
      <div v-if="showReactions" class="mb-3">
        <div class="flex items-center space-x-2">
          <button
            v-for="(reaction, type) in availableReactions"
            :key="type"
            @click="toggleReaction(type)"
            :class="[
              'flex items-center space-x-1 px-2 py-1 rounded-full text-xs transition-colors',
              isReactionActive(type)
                ? 'bg-blue-100 text-blue-700'
                : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
            ]"
          >
            <span class="text-sm">{{ reaction.emoji }}</span>
            <span v-if="getReactionCount(type) > 0">{{ getReactionCount(type) }}</span>
          </button>
        </div>
      </div>

      <!-- 액션 버튼들 -->
      <div class="flex items-center space-x-4 text-sm">
        <!-- 답글 버튼 -->
        <button
          v-if="canReply"
          @click="showReplyForm = !showReplyForm"
          class="text-gray-500 hover:text-gray-700 flex items-center space-x-1"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
          </svg>
          <span>답글</span>
        </button>

        <!-- 평점 버튼 -->
        <button
          v-if="showRatings"
          @click="showRatingForm = !showRatingForm"
          class="text-gray-500 hover:text-gray-700 flex items-center space-x-1"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
          </svg>
          <span>평점</span>
        </button>
      </div>

      <!-- 답글 폼 -->
      <div v-if="showReplyForm" class="mt-4 pt-4 border-t border-gray-200">
        <CommentForm
          :commentable-type="comment.commentable_type"
          :commentable-id="comment.commentable_id"
          :parent-id="comment.id"
          @comment-added="handleReplyAdded"
        />
      </div>

      <!-- 평점 폼 -->
      <div v-if="showRatingForm" class="mt-4 pt-4 border-t border-gray-200">
        <div class="space-y-3">
          <h4 class="text-sm font-medium text-gray-700">평점 남기기</h4>
          <div v-for="category in ratingCategories" :key="category.key" class="flex items-center justify-between">
            <span class="text-sm text-gray-600">{{ category.label }}</span>
            <StarRating
              v-model="newRatings[category.key]"
              :max-rating="5"
              :allow-half="true"
              @rating-changed="handleRatingChanged"
            />
          </div>
          <div class="flex justify-end space-x-2">
            <button
              @click="submitRating"
              :disabled="!hasRating"
              class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 disabled:opacity-50"
            >
              평점 제출
            </button>
            <button
              @click="showRatingForm = false"
              class="px-3 py-1 bg-gray-300 text-gray-700 text-sm rounded hover:bg-gray-400"
            >
              취소
            </button>
          </div>
        </div>
      </div>

      <!-- 답글들 -->
      <div v-if="comment.replies && comment.replies.length > 0" class="mt-4 space-y-3">
        <CommentItem
          v-for="reply in comment.replies"
          :key="reply.id"
          :comment="reply"
          :max-depth="maxDepth"
          @reply-added="handleReplyAdded"
          @reaction-toggled="handleReactionToggled"
          @rating-added="handleRatingAdded"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { formatDistanceToNow } from 'date-fns'
import { ko } from 'date-fns/locale'
import CommentForm from './CommentForm.vue'
import StarRating from './StarRating.vue'

const props = defineProps({
  comment: {
    type: Object,
    required: true
  },
  maxDepth: {
    type: Number,
    default: 5
  },
  canReply: {
    type: Boolean,
    default: true
  },
  showReactions: {
    type: Boolean,
    default: true
  },
  showRatings: {
    type: Boolean,
    default: true
  },
  ratingCategories: {
    type: Array,
    default: () => [
      { key: 'overall', label: '전체' }
    ]
  }
})

const emit = defineEmits(['reply-added', 'reaction-toggled', 'rating-added'])

// 상태
const showReplyForm = ref(false)
const showRatingForm = ref(false)
const newRatings = ref({})

// 설정
const availableReactions = computed(() => {
  return window.commentsConfig?.reactions?.types || {
    like: { emoji: '👍' },
    love: { emoji: '❤️' },
    laugh: { emoji: '😂' },
    wow: { emoji: '😮' },
    sad: { emoji: '😢' },
    angry: { emoji: '😠' }
  }
})

// 날짜 포맷팅
const formatDate = (date) => {
  return formatDistanceToNow(new Date(date), { 
    addSuffix: true, 
    locale: ko 
  })
}

// 권한 체크
const canEdit = (comment) => {
  // 로그인한 사용자이고 댓글 작성자인 경우
  return window.auth?.user?.id === comment.author_id
}

const canDelete = (comment) => {
  // 로그인한 사용자이고 댓글 작성자인 경우 또는 관리자
  return window.auth?.user?.id === comment.author_id || window.auth?.user?.is_admin
}

// 반응 관련
const isReactionActive = (type) => {
  return props.comment.reactions_summary?.[type]?.count > 0
}

const getReactionCount = (type) => {
  return props.comment.reactions_summary?.[type]?.count || 0
}

const toggleReaction = async (type) => {
  try {
    const response = await fetch(`/api/comments/${props.comment.id}/reactions`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({ type })
    })

    if (response.ok) {
      const result = await response.json()
      emit('reaction-toggled', props.comment.id, type, result.is_active)
    }
  } catch (error) {
    console.error('반응 토글 중 오류가 발생했습니다:', error)
  }
}

// 평점 관련
const hasRating = computed(() => {
  return Object.values(newRatings.value).some(rating => rating > 0)
})

const handleRatingChanged = (category, rating) => {
  newRatings.value[category] = rating
}

const submitRating = async () => {
  try {
    const response = await fetch(`/api/comments/${props.comment.id}/ratings`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({ ratings: newRatings.value })
    })

    if (response.ok) {
      const result = await response.json()
      emit('rating-added', props.comment.id, result.ratings)
      showRatingForm.value = false
      newRatings.value = {}
    }
  } catch (error) {
    console.error('평점 제출 중 오류가 발생했습니다:', error)
  }
}

// 답글 추가 처리
const handleReplyAdded = (parentId, newReply) => {
  emit('reply-added', parentId, newReply)
  showReplyForm.value = false
}

// 반응 토글 처리
const handleReactionToggled = (commentId, reactionType, isActive) => {
  emit('reaction-toggled', commentId, reactionType, isActive)
}

// 평점 추가 처리
const handleRatingAdded = (commentId, ratings) => {
  emit('rating-added', commentId, ratings)
}

// 카테고리 라벨 가져오기
const getCategoryLabel = (category) => {
  const categories = window.commentsConfig?.ratings?.categories?.custom || {}
  return categories[category] || category
}
</script>

<style scoped>
.comment-item {
  @apply transition-all duration-200;
}

.comment-item:hover {
  @apply shadow-sm;
}
</style> 