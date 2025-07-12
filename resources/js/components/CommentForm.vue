<template>
  <div class="comment-form">
    <form @submit.prevent="submitComment" class="space-y-4">
      <!-- 게스트 정보 (로그인하지 않은 경우) -->
      <div v-if="!isAuthenticated" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label for="guest_name" class="block text-sm font-medium text-gray-700">
            이름 *
          </label>
          <input
            id="guest_name"
            v-model="form.guest_name"
            type="text"
            required
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            :class="{ 'border-red-500': errors.guest_name }"
          />
          <p v-if="errors.guest_name" class="mt-1 text-sm text-red-600">
            {{ errors.guest_name }}
          </p>
        </div>

        <div>
          <label for="guest_email" class="block text-sm font-medium text-gray-700">
            이메일 *
          </label>
          <input
            id="guest_email"
            v-model="form.guest_email"
            type="email"
            required
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            :class="{ 'border-red-500': errors.guest_email }"
          />
          <p v-if="errors.guest_email" class="mt-1 text-sm text-red-600">
            {{ errors.guest_email }}
          </p>
        </div>
      </div>

      <!-- 평점 시스템 (활성화된 경우) -->
      <div v-if="showRatings" class="rating-section">
        <h4 class="text-sm font-medium text-gray-700 mb-2">평점</h4>
        <div class="space-y-2">
          <div v-for="category in ratingCategories" :key="category.key" class="flex items-center justify-between">
            <span class="text-sm text-gray-600">{{ category.label }}</span>
            <StarRating
              v-model="form.ratings[category.key]"
              :max-rating="5"
              :allow-half="true"
              @rating-changed="handleRatingChanged"
            />
          </div>
        </div>
      </div>

      <!-- 댓글 내용 -->
      <div>
        <label for="content" class="block text-sm font-medium text-gray-700">
          댓글 내용 *
        </label>
        <textarea
          id="content"
          v-model="form.content"
          rows="4"
          required
          placeholder="댓글을 입력하세요..."
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          :class="{ 'border-red-500': errors.content }"
        ></textarea>
        <p v-if="errors.content" class="mt-1 text-sm text-red-600">
          {{ errors.content }}
        </p>
      </div>

      <!-- 스팸 방지 (honeypot) -->
      <div v-if="showHoneypot" class="hidden">
        <input
          v-model="form.website"
          type="text"
          name="website"
          autocomplete="off"
        />
      </div>

      <!-- 제출 버튼 -->
      <div class="flex justify-end">
        <button
          type="submit"
          :disabled="isSubmitting"
          class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
        >
          <span v-if="isSubmitting" class="mr-2">
            <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
          </span>
          {{ isSubmitting ? '작성 중...' : '댓글 작성' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import StarRating from './StarRating.vue'

const props = defineProps({
  commentableType: {
    type: String,
    required: true
  },
  commentableId: {
    type: [String, Number],
    required: true
  },
  parentId: {
    type: [String, Number],
    default: null
  },
  isAuthenticated: {
    type: Boolean,
    default: false
  },
  showRatings: {
    type: Boolean,
    default: false
  },
  ratingCategories: {
    type: Array,
    default: () => [
      { key: 'overall', label: '전체' }
    ]
  }
})

const emit = defineEmits(['comment-added'])

// 폼 데이터
const form = useForm({
  commentable_type: props.commentableType,
  commentable_id: props.commentableId,
  parent_id: props.parentId,
  content: '',
  guest_name: '',
  guest_email: '',
  ratings: {},
  website: '' // honeypot
})

// 상태
const isSubmitting = ref(false)
const errors = ref({})

// 설정
const showHoneypot = computed(() => {
  return window.commentsConfig?.security?.spam_protection?.enabled || false
})

// 평점 변경 처리
const handleRatingChanged = (category, rating) => {
  form.ratings[category] = rating
}

// 댓글 제출
const submitComment = async () => {
  isSubmitting.value = true
  errors.value = {}

  try {
    const response = await fetch('/api/comments', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify(form.data())
    })

    const result = await response.json()

    if (response.ok) {
      // 성공
      emit('comment-added', result.comment)
      form.reset()
      form.clearErrors()
    } else {
      // 오류
      if (result.errors) {
        errors.value = result.errors
      }
    }
  } catch (error) {
    console.error('댓글 작성 중 오류가 발생했습니다:', error)
    errors.value = { general: '댓글 작성 중 오류가 발생했습니다.' }
  } finally {
    isSubmitting.value = false
  }
}

// 컴포넌트 마운트 시 초기화
onMounted(() => {
  // 평점 초기화
  if (props.showRatings) {
    props.ratingCategories.forEach(category => {
      form.ratings[category.key] = 0
    })
  }
})
</script>

<style scoped>
.comment-form {
  @apply bg-white rounded-lg shadow-sm border border-gray-200 p-6;
}

.rating-section {
  @apply border-t border-gray-200 pt-4;
}
</style> 