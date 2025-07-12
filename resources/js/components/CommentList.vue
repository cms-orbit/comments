<template>
  <div class="comments-section">
    <!-- 댓글 작성 폼 -->
    <CommentForm 
      v-if="canComment"
      :commentable-type="commentableType"
      :commentable-id="commentableId"
      @comment-added="handleCommentAdded"
    />

    <!-- 댓글 목록 -->
    <div v-if="comments.length > 0" class="comments-list">
      <h3 class="text-lg font-semibold mb-4">
        댓글 ({{ totalComments }})
      </h3>
      
      <div class="space-y-4">
        <CommentItem
          v-for="comment in comments"
          :key="comment.id"
          :comment="comment"
          :max-depth="maxDepth"
          @reply-added="handleReplyAdded"
          @reaction-toggled="handleReactionToggled"
          @rating-added="handleRatingAdded"
        />
      </div>

      <!-- 페이지네이션 -->
      <div v-if="pagination && pagination.last_page > 1" class="mt-6">
        <Pagination :pagination="pagination" @page-changed="handlePageChanged" />
      </div>
    </div>

    <!-- 댓글이 없을 때 -->
    <div v-else class="text-center py-8 text-gray-500">
      <p>아직 댓글이 없습니다.</p>
      <p v-if="canComment" class="mt-2">첫 번째 댓글을 작성해보세요!</p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import CommentForm from './CommentForm.vue'
import CommentItem from './CommentItem.vue'
import Pagination from './Pagination.vue'

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
  canComment: {
    type: Boolean,
    default: true
  },
  maxDepth: {
    type: Number,
    default: 5
  }
})

const emit = defineEmits(['comment-added', 'reply-added', 'reaction-toggled', 'rating-added'])

const comments = ref(props.initialComments)
const totalComments = ref(props.pagination?.total || 0)

// 댓글 추가 처리
const handleCommentAdded = (newComment) => {
  comments.value.unshift(newComment)
  totalComments.value++
  emit('comment-added', newComment)
}

// 답글 추가 처리
const handleReplyAdded = (parentId, newReply) => {
  const parentComment = findCommentById(comments.value, parentId)
  if (parentComment) {
    if (!parentComment.replies) {
      parentComment.replies = []
    }
    parentComment.replies.unshift(newReply)
    totalComments.value++
    emit('reply-added', parentId, newReply)
  }
}

// 반응 토글 처리
const handleReactionToggled = (commentId, reactionType, isActive) => {
  const comment = findCommentById(comments.value, commentId)
  if (comment) {
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
    
    emit('reaction-toggled', commentId, reactionType, isActive)
  }
}

// 평점 추가 처리
const handleRatingAdded = (commentId, category, rating) => {
  const comment = findCommentById(comments.value, commentId)
  if (comment) {
    if (!comment.rating_summary) {
      comment.rating_summary = {}
    }
    
    comment.rating_summary[category] = {
      average: rating,
      count: 1
    }
    
    emit('rating-added', commentId, category, rating)
  }
}

// 페이지 변경 처리
const handlePageChanged = (page) => {
  router.get(
    route().current(),
    { 
      ...route().params,
      page: page,
      comment_page: page
    },
    {
      preserveState: true,
      preserveScroll: true,
      only: ['comments', 'pagination']
    }
  )
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
  const reactions = {
    like: '👍',
    love: '❤️',
    laugh: '😂',
    wow: '😮',
    sad: '😢',
    angry: '😠'
  }
  return reactions[type] || '👍'
}

// props 변경 감지
watch(() => props.initialComments, (newComments) => {
  comments.value = newComments
})

watch(() => props.pagination, (newPagination) => {
  totalComments.value = newPagination?.total || 0
})
</script>

<style scoped>
.comments-section {
  @apply max-w-4xl mx-auto;
}

.comments-list {
  @apply mt-8;
}
</style> 