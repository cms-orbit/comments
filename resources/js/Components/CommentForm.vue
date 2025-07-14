<template>
  <form @submit.prevent="submitComment" class="space-y-4">
    <!-- 게스트 정보 (로그인하지 않은 경우) -->
    <div v-if="!page.props.auth.user" class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div>
        <label for="guest_name" class="block text-xs font-medium text-gray-700">
          {{ __('Name') }} *
        </label>
        <input
          id="guest_name"
          v-model="form.guest_name"
          type="text"
          required
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm px-2 py-1 focus:border-indigo-500 focus:ring-indigo-500"
          :class="{ 'border-red-500': errors.guest_name }"
        />
        <p v-if="errors.guest_name" class="mt-1 text-xs text-red-600">
          {{ errors.guest_name }}
        </p>
      </div>

      <div>
        <label for="guest_email" class="block text-xs font-medium text-gray-700">
          {{ __('Email') }} *
        </label>
        <input
          id="guest_email"
          v-model="form.guest_email"
          type="email"
          required
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm px-2 py-1 focus:border-indigo-500 focus:ring-indigo-500"
          :class="{ 'border-red-500': errors.guest_email }"
        />
        <p v-if="errors.guest_email" class="mt-1 text-xs text-red-600">
          {{ errors.guest_email }}
        </p>
      </div>

      <div>
        <label for="guest_password" class="block text-xs font-medium text-gray-700">
          {{ __('Password') }} *
        </label>
        <input
          id="guest_password"
          v-model="form.guest_password"
          type="password"
          required
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm px-2 py-1 focus:border-indigo-500 focus:ring-indigo-500"
          :class="{ 'border-red-500': errors.guest_password }"
        />
        <p v-if="errors.guest_password" class="mt-1 text-xs text-red-600">
          {{ errors.guest_password }}
        </p>
      </div>
    </div>

    <!-- 평점 시스템 (활성화된 경우이고 대댓글이 아닌 경우) -->
    <div v-if="showRatings && !isReply" class="border-t border-gray-200 pt-4">
      <h4 class="text-sm font-medium text-gray-700 mb-2">{{ __('Rating') }}</h4>
      <div class="space-y-4">
        <div v-for="(ratingCategory, ratingId) in ratingCategories" :key="ratingId" class="flex items-center justify-start">
          <h5 class="text-gray-500 text-sm font-medium mr-2">{{ __(ratingCategory.title) }}</h5>
          <StarRating
            v-model="form.ratings[ratingId]"
            :rating-category="ratingCategory"
          />
        </div>
      </div>
    </div>

    <!-- 댓글 내용 -->
    <div>
      <textarea
        id="content"
        v-model="form.content"
        rows="4"
        required
        :placeholder="__('Enter your comment...')"
        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
        :class="{ 'border-red-500': errors.content }"
      ></textarea>
      <p v-if="errors.content" class="mt-1 text-sm text-red-600">
        {{ errors.content }}
      </p>
    </div>

    <!-- Honeypot 필드 (숨김) -->
    <div class="hidden">
      <input type="text" v-model="form.website" name="website" autocomplete="off" />
    </div>

    <!-- 제출 버튼 -->
    <div class="flex justify-end">
      <!-- 비밀글/알림 체크박스 (모든 댓글에서 사용 가능) -->
      <div class="flex flex-col md:flex-row md:items-center gap-4 mr-4">
        <label class="inline-flex items-center text-xs">
          <input type="checkbox" v-model="form.is_secret" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
          <span class="ml-2">{{ __('Secret comment') }}</span>
        </label>
        <label class="inline-flex items-center text-xs">
          <input type="checkbox" v-model="form.notify_reply" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
          <span class="ml-2">{{ __('Notify me by email when a reply is posted') }}</span>
        </label>
      </div>

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
        {{ isSubmitting ? __('Submitting...') : __('Submit Comment') }}
      </button>
    </div>
  </form>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import {useForm, usePage} from '@inertiajs/vue3'
import StarRating from '@orbit/comments/Components/StarRating.vue'
import { __ } from '@/lib/translate.js';
const page = usePage()
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
  showRatings: {
    type: Boolean,
    default: false
  },
  ratingCategories: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['comment-added'])

// 대댓글 여부 확인
const isReply = computed(() => !!props.parentId)

// 폼 데이터
const form = useForm({
  commentable_type: props.commentableType,
  commentable_id: props.commentableId,
  parent_id: props.parentId,
  content: '',
  guest_name: '',
  guest_email: '',
  guest_password: '',
  ratings: {},
  is_secret: false,
  notify_reply: false,
  website: '' // honeypot
})

// 상태
const isSubmitting = ref(false)
const errors = ref({})

// 댓글 제출
const submitComment = async () => {
  isSubmitting.value = true
  errors.value = {}

  try {
    // 로그인된 사용자는 인증된 라우트 사용
    const routeName = page.props.auth.user ? 'orbit-comments.comment.store' : 'orbit-comments.guest-comment.store'
      const response = await axios.post(route(routeName), form.data())

      if (response.status === 201) {
          // 성공 시 폼 초기화
          form.reset()
          form.clearErrors()
          
          // 부모에게 새 댓글 전달
          emit('comment-added', response.data.comment)
      } else if (response.status === 422) {
          if (response.data.errors) {
              errors.value = response.data.errors
      }
    }
  } catch (error) {
    console.error(__('An error occurred while submitting the comment.'), error)
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      errors.value = { general: __('An error occurred while submitting the comment.') }
    }
  } finally {
    isSubmitting.value = false
  }
}

// 컴포넌트 마운트 시 초기화
onMounted(() => {
  // 평점 초기화 (대댓글이 아닌 경우에만)
  if (props.showRatings && !isReply.value) {
    Object.keys(props.ratingCategories).forEach(category => {
      form.ratings[category] = 0
    })
  }
})
</script>
