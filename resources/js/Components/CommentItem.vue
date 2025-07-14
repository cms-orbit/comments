<template>
  <div class="transition-all duration-200 hover:shadow-sm" :class="{ 'ml-8': comment.depth > 0 }">
    <div class="bg-white rounded-lg border border-gray-200 p-4">
      <!-- 댓글 헤더 -->
      <div class="flex items-start justify-between mb-3">
        <div class="flex items-center space-x-3">
          <!-- 아바타 -->
          <div v-if="showAvatars && comment.author_avatar" class="flex-shrink-0">
            <img
              :src="comment.author_avatar"
              :alt="comment.author_name"
              class="h-8 w-8 rounded-full"
            />
          </div>
          <div v-else-if="showAvatars" class="flex-shrink-0">
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
            <div v-if="showTimestamps" class="text-xs text-gray-500">
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
            :title="__('Edit')"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
            </svg>
          </button>

          <button
            v-if="canDelete(comment)"
            @click="deleteComment"
            class="text-gray-400 hover:text-red-600"
            :title="__('Delete')"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
          </button>

          <!-- 스팸 처리 버튼 (권한이 있는 경우) -->
          <button
            v-if="canModerateSpam()"
            @click="toggleSpamStatus"
            :class="comment.is_spam ? 'text-green-400 hover:text-green-600' : 'text-red-400 hover:text-red-600'"
            :title="comment.is_spam ? __('Unmark as spam') : __('Mark as spam')"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
          </button>
        </div>
      </div>

        <!-- 평점 표시 -->
        <div v-if="comment.rating_summary && Object.keys(comment.rating_summary).length > 0 && comment.depth === 0" class="mb-3">
            <div class="space-y-1">
                <template v-for="(ratingCategory, ratingId) in ratingCategories" :key="ratingId" >
                    <div v-if="comment.rating_summary[ratingId]" class="flex items-center justify-start">
                        <h5 class="text-gray-500 text-sm font-medium mr-2">{{ __(ratingCategory.title) }}</h5>
                        <StarRating
                            v-model="comment.rating_summary[ratingId].average"
                            :read-only="true"
                            :rating-category="ratingCategory"
                        />
                    </div>
                </template>
            </div>
        </div>

      <!-- 댓글 내용 -->
      <div class="mb-4">
        <!-- 스팸 댓글 처리 -->
        <div v-if="comment.is_spam && !canViewSpamContent(comment)" class="bg-red-50 border border-red-200 rounded-md p-3">
          <p class="text-red-600 text-sm font-medium">{{ __('This comment has been marked as spam.') }}</p>
        </div>

        <!-- 비밀 댓글 처리 -->
        <div v-else-if="comment.is_secret && !canViewSecretContent(comment)" class="bg-yellow-50 border border-yellow-200 rounded-md p-3">
          <p class="text-yellow-600 text-sm font-medium">{{ __('This is a secret comment.') }}</p>
        </div>

        <!-- 일반 댓글 내용 -->
        <div v-else>
          <p class="text-gray-900 whitespace-pre-wrap">{{ comment.content }}</p>
        </div>
      </div>
      <!-- 반응 버튼들 -->
      <ReactionBar
        v-if="reactionsEnabled && comment.reactions_summary"
        :reactions-summary="comment.reactions_summary"
        :reaction-types="reactionTypes"
        @reaction-add="onReactionAdd"
        @reaction-toggle="onReactionToggle"
        class="mb-3"
      />

      <!-- 액션 버튼들 -->
      <div class="flex items-center space-x-4 text-sm">
        <!-- 답글 버튼 -->
        <button
          v-if="canReply && comment.depth < maxDepth"
          @click="showReplyForm = !showReplyForm"
          class="text-gray-500 hover:text-gray-700 flex items-center space-x-1"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
          </svg>
          <span>{{ __('Reply') }}</span>
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

      <!-- 답글들 -->
      <div v-if="comment.replies && comment.replies.length > 0" class="mt-4 space-y-3">
        <CommentItem
          v-for="reply in comment.replies"
          :key="reply.id"
          :comment="reply"
          :max-depth="maxDepth"
          :show-avatars="showAvatars"
          :show-timestamps="showTimestamps"
          :reactions-enabled="reactionsEnabled"
          :reaction-types="reactionTypes"
          :rating-categories="ratingCategories"
          @reply-added="handleReplyAdded"
          @reaction-toggled="handleReactionToggled"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { formatDistanceToNow } from 'date-fns'
import {ko} from 'date-fns/locale'
import { __ } from '@/lib/translate.js';
import CommentForm from '@orbit/comments/Components/CommentForm.vue'
import StarRating from '@orbit/comments/Components/StarRating.vue'
import ReactionBar from '@orbit/comments/Components/ReactionBar.vue'
import axios from 'axios'
import {usePage} from "@inertiajs/vue3";

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
  showAvatars: {
    type: Boolean,
    default: true
  },
  showTimestamps: {
    type: Boolean,
    default: true
  },
  reactionsEnabled: {
    type: Boolean,
    default: true
  },
  reactionTypes: {
    type: Object,
    default: () => ({
    })
  },
  ratingCategories: {
    type: Object,
    default: () => ({
    })
  }
})

const emit = defineEmits(['reply-added', 'reaction-toggled'])

// 상태
const showReplyForm = ref(false)

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
  return usePage().props.auth.user?.id === comment.author_id
}

const canDelete = (comment) => {
  // 로그인한 사용자이고 댓글 작성자인 경우 또는 관리자
  return usePage().props.auth.user?.id === comment.author_id || usePage().props.auth.user?.permissions?.includes("orbit_comments.delete_any")
}

// 스팸 처리 권한 체크
const canModerateSpam = () => {
  const user = usePage().props.auth?.user
  return user && (user.permissions?.includes('orbit_comments.spam_any'))
}

// 스팸 댓글 내용 보기 권한 체크
const canViewSpamContent = (comment) => {
  const user = usePage().props.auth?.user
  if (!user) return false

  // 댓글 작성자는 스팸 댓글도 볼 수 있음
  return user.id === comment.author_id
}

// 비밀 댓글 내용 보기 권한 체크
const canViewSecretContent = (comment) => {
  const user = usePage().props.auth?.user

  if (!user) return false

  // 댓글 작성자
  if (user.id === comment.author_id) return true

  // 권한이 있는 사용자
  if (user.permissions?.includes('orbit_comments.read_secret')) return true

  // 문서 작성자 (commentable의 author)
  const commentable = comment.commentable
  if (commentable && commentable.author_id === user.id) return true

  // 대댓글인 경우 부모 댓글 작성자
  if (comment.parent_id && comment.parent && comment.parent.author_id === user.id) return true

  return false
}

// 반응 관련
const onReactionAdd = (emoji) => {
  console.log('onReactionAdd called with emoji:', emoji)
  // 서버에 리액션 추가 요청
  toggleReaction(emoji)
}

const onReactionToggle = (emoji) => {
  console.log('onReactionToggle called with emoji:', emoji)
  // 서버에 리액션 토글 요청
  toggleReaction(emoji)
}

// 리액션 토글 함수
const toggleReaction = async (emoji) => {
  try {
    console.log('Toggling reaction:', emoji, 'for comment:', props.comment.id)

    const response = await axios.post(`/api/orbit-comments/reactions/${props.comment.id}`, {
      type: emoji
    })

    console.log('Reaction response:', response.data)

    // 서버에서 받은 최신 리액션 상태로 업데이트
    if (response.data.reactions_summary) {
      // 부모 컴포넌트에 리액션 상태 업데이트 이벤트 발생
      emit('reaction-toggled', props.comment.id, emoji, response.data.is_active, response.data.reactions_summary)
    }
  } catch (error) {
    console.error('Failed to toggle reaction:', error)

    // 401 에러 (비로그인) 처리
    if (error.response?.status === 401) {
      alert('리액션을 사용하려면 로그인해주세요.')
    } else {
      // 기타 에러
      alert('리액션 처리 중 오류가 발생했습니다.')
    }
  }
}

// 스팸 상태 토글
const toggleSpamStatus = async () => {
  try {
    let response
    if (props.comment.is_spam) {
      // 스팸 해제 - DELETE 메서드 사용
      response = await axios.delete(route('orbit-comments.comment.unmark-as-spam', props.comment.id))
    } else {
      // 스팸 처리 - POST 메서드 사용
      response = await axios.post(route('orbit-comments.comment.mark-as-spam', props.comment.id))
    }

    if (response.status === 200) {
      // 댓글 상태 업데이트
      props.comment.is_spam = !props.comment.is_spam
    }
  } catch (error) {
    console.error('Failed to toggle spam status:', error)
    if (error.response?.status === 403) {
      alert('권한이 없습니다.')
    } else {
      alert('스팸 상태 변경 중 오류가 발생했습니다.')
    }
  }
}

// 답글 추가 처리
const handleReplyAdded = (parentId, newReply) => {
  // 답글 폼 숨기기
  showReplyForm.value = false

  // 부모에게 답글 추가 이벤트 전달
  emit('reply-added', parentId, newReply)
}

// 반응 토글 처리
const handleReactionToggled = (commentId, reactionType, isActive, reactionsSummary) => {
  // 대댓글의 반응 토글 이벤트를 부모에게 전달
  emit('reaction-toggled', commentId, reactionType, isActive, reactionsSummary)
}

</script>
