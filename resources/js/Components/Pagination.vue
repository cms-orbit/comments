<template>
  <div class="pagination">
    <nav class="flex items-center justify-between">
      <!-- 이전 페이지 -->
      <div class="flex-1 flex justify-between sm:hidden">
        <button
          @click="goToPage(pagination.current_page - 1)"
          :disabled="!pagination.prev_page_url"
          class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ __('Previous') }}
        </button>
        <button
          @click="goToPage(pagination.current_page + 1)"
          :disabled="!pagination.next_page_url"
          class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ __('Next') }}
        </button>
      </div>

      <!-- 데스크톱 페이지네이션 -->
      <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
        <div>
          <p class="text-sm text-gray-700">
            {{ __('Showing :from to :to of :total results') }}
          </p>
        </div>
        <div>
          <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
            <!-- 이전 페이지 버튼 -->
            <button
              @click="goToPage(pagination.current_page - 1)"
              :disabled="!pagination.prev_page_url"
              class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span class="sr-only">{{ __('Previous') }}</span>
              <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
              </svg>
            </button>

            <!-- 페이지 번호들 -->
            <template v-for="(link, index) in visibleLinks" :key="index">
              <!-- 구분자 -->
              <span
                v-if="link.type === 'separator'"
                class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700"
              >
                ...
              </span>

              <!-- 페이지 번호 -->
              <button
                v-else
                @click="goToPage(link.page)"
                :class="[
                  'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                  link.page === pagination.current_page
                    ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                    : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
                ]"
              >
                {{ link.page }}
              </button>
            </template>

            <!-- 다음 페이지 버튼 -->
            <button
              @click="goToPage(pagination.current_page + 1)"
              :disabled="!pagination.next_page_url"
              class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span class="sr-only">{{ __('Next') }}</span>
              <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
              </svg>
            </button>
          </nav>
        </div>
      </div>
    </nav>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { __ } from '@/lib/translate.js';

const props = defineProps({
  pagination: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['page-changed'])

// 보여줄 페이지 링크들 계산
const visibleLinks = computed(() => {
  const current = props.pagination.current_page
  const last = props.pagination.last_page
  const links = []

  // 항상 첫 페이지 표시
  if (current > 1) {
    links.push({ type: 'page', page: 1 })
  }

  // 현재 페이지 주변 페이지들
  const start = Math.max(2, current - 2)
  const end = Math.min(last - 1, current + 2)

  // 첫 페이지와 시작 페이지 사이에 구분자
  if (start > 2) {
    links.push({ type: 'separator' })
  }

  // 중간 페이지들
  for (let i = start; i <= end; i++) {
    links.push({ type: 'page', page: i })
  }

  // 끝 페이지와 마지막 페이지 사이에 구분자
  if (end < last - 1) {
    links.push({ type: 'separator' })
  }

  // 항상 마지막 페이지 표시
  if (current < last) {
    links.push({ type: 'page', page: last })
  }

  return links
})

// 페이지 이동
const goToPage = (page) => {
  if (page >= 1 && page <= props.pagination.last_page) {
    emit('page-changed', page)
  }
}
</script>

<style scoped>
.pagination {
  @apply mt-6;
}
</style>
