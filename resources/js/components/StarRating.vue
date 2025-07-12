<template>
  <div class="star-rating" :class="sizeClass">
    <div class="flex items-center">
      <button
        v-for="star in maxRating"
        :key="star"
        @click="handleStarClick(star)"
        @mouseenter="hoveredStar = star"
        @mouseleave="hoveredStar = 0"
        :disabled="readonly"
        class="star-button"
        :class="[
          'transition-colors duration-150',
          readonly ? 'cursor-default' : 'cursor-pointer hover:scale-110',
          getStarClass(star)
        ]"
      >
        <svg
          class="w-full h-full"
          fill="currentColor"
          viewBox="0 0 20 20"
        >
          <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
        </svg>
      </button>

      <!-- 반별점 (활성화된 경우) -->
      <button
        v-if="allowHalf && !readonly"
        @click="handleHalfStarClick"
        @mouseenter="hoveredHalfStar = true"
        @mouseleave="hoveredHalfStar = false"
        class="star-button half-star"
        :class="[
          'transition-colors duration-150 cursor-pointer hover:scale-110',
          getHalfStarClass()
        ]"
      >
        <div class="relative w-full h-full">
          <svg
            class="w-full h-full absolute inset-0"
            fill="currentColor"
            viewBox="0 0 20 20"
          >
            <defs>
              <linearGradient id="half-star" x1="0%" y1="0%" x2="100%" y2="0%">
                <stop offset="50%" style="stop-color:currentColor;stop-opacity:1" />
                <stop offset="50%" style="stop-color:transparent;stop-opacity:1" />
              </linearGradient>
            </defs>
            <path
              d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
              fill="url(#half-star)"
            />
          </svg>
        </div>
      </button>
    </div>

    <!-- 평점 텍스트 표시 -->
    <div v-if="showText" class="ml-2 text-sm text-gray-600">
      {{ displayRating }}
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  modelValue: {
    type: Number,
    default: 0
  },
  maxRating: {
    type: Number,
    default: 5
  },
  allowHalf: {
    type: Boolean,
    default: false
  },
  readonly: {
    type: Boolean,
    default: false
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value)
  },
  showText: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue', 'rating-changed'])

// 상태
const hoveredStar = ref(0)
const hoveredHalfStar = ref(false)

// 계산된 속성
const currentRating = computed(() => props.modelValue)
const displayRating = computed(() => {
  if (props.allowHalf) {
    return currentRating.value.toFixed(1)
  }
  return Math.round(currentRating.value)
})

const sizeClass = computed(() => {
  const sizes = {
    sm: 'text-sm',
    md: 'text-base',
    lg: 'text-lg'
  }
  return sizes[props.size] || sizes.md
})

// 별점 클래스 결정
const getStarClass = (star) => {
  const rating = hoveredStar.value || currentRating.value
  
  if (star <= rating) {
    return 'text-yellow-400'
  }
  
  return 'text-gray-300'
}

const getHalfStarClass = () => {
  const rating = currentRating.value
  const isHovered = hoveredHalfStar.value
  
  if (isHovered || (rating > 0 && rating % 1 !== 0)) {
    return 'text-yellow-400'
  }
  
  return 'text-gray-300'
}

// 이벤트 핸들러
const handleStarClick = (star) => {
  if (props.readonly) return
  
  const newRating = star
  emit('update:modelValue', newRating)
  emit('rating-changed', newRating)
}

const handleHalfStarClick = () => {
  if (props.readonly || !props.allowHalf) return
  
  const current = currentRating.value
  const newRating = current === 0.5 ? 0 : 0.5
  emit('update:modelValue', newRating)
  emit('rating-changed', newRating)
}

// 외부에서 평점 변경 감지
watch(() => props.modelValue, (newValue) => {
  // 필요한 경우 추가 로직
})
</script>

<style scoped>
.star-rating {
  @apply inline-flex items-center;
}

.star-button {
  @apply flex-shrink-0;
}

.half-star {
  @apply relative;
}

/* 반별점 그라데이션 효과 */
.half-star svg {
  @apply transition-all duration-150;
}
</style> 