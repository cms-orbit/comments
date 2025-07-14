<template>
  <div class="star-rating-wrapper">
    <div :class="['star-rating', {'star-rating-rtl': rtl}, {'star-rating-inline': inline}]">
      <div class="sr-only">
        <slot
          name="screen-reader"
          :rating="selectedRating"
          :stars="maxRating"
        >
          <span>Rated {{ selectedRating }} stars out of {{ maxRating }}</span>
        </slot>
      </div>

      <div
        class="star-rating"
        @mouseleave="readOnly ? null : resetRating"
      >
        <span
          v-for="n in maxRating"
          :key="n"
          :class="[{'star-rating-pointer': !readOnly }, 'star-rating-star']"
          :style="{'margin-right': margin + 'px'}"
        >
          <Star
            :fill="fillLevel[n-1]"
            :size="starSize"
            :points="starPoints"
            :star-id="n"
            :step="step"
            :active-color="currentActiveColor"
            :inactive-color="inactiveColor"
            :border-color="borderColor"
            :active-border-color="currentActiveBorderColor"
            :border-width="borderWidth"
            :rounded-corners="roundedCorners"
            :rtl="rtl"
            :glow="glow"
            :glow-color="glowColor"
            :animate="animate"
            @star-selected="setRating($event, true)"
            @star-mouse-move="setRating"
          />
        </span>
        <span
          v-if="showRating"
          :class="['star-rating-rating-text', textClass]"
        > {{ formattedRating }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import Star from './Star.vue'

const props = defineProps({
  modelValue: {
    type: Number,
    default: 0
  },
  ratingCategory: {
    type: Object,
    required: false
  },
  readOnly: {
    type: Boolean,
    default: undefined
  }
})

const emit = defineEmits(['update:modelValue', 'rating-selected', 'rating-changed', 'current-rating'])
const ratingCategoryConfig = computed(() => {
  const defaultConfig = {
    title: 'Rating',
    max_rating: 5,
    increment: 0.5,
    border_width: 3,
    border_color: '#ffd700',
    fill_color: '#ffd700',
    star_size: 20,
    star_spacing: 2,
    show_rating: false,
    read_only: false,
    disable_click: false,
    rtl: false,
    fixed_points: null,
    glow: 0,
    glow_color: '#ffd700',
    text_class: 'text-sm text-gray-600',
    required: false,
  }

  return { ...defaultConfig, ...props.ratingCategory }
})

// vue-star-rating props mapping
const increment = computed(() => ratingCategoryConfig.value.increment)
const rating = computed(() => props.modelValue)
const roundStartRating = computed(() => true)
const activeColor = computed(() => ratingCategoryConfig.value.fill_color)
const inactiveColor = computed(() => '#d8d8d8')
const maxRating = computed(() => ratingCategoryConfig.value.max_rating)
const starPoints = computed(() => [])
const starSize = computed(() => ratingCategoryConfig.value.star_size)
const showRating = computed(() => ratingCategoryConfig.value.show_rating)
const readOnly = computed(() => {
  // props에서 read-only가 직접 전달되면 그것을 우선 사용
  if (props.readOnly !== undefined) {
    return props.readOnly
  }
  // 그렇지 않으면 config에서 가져옴
  return ratingCategoryConfig.value.read_only
})
const textClass = computed(() => ratingCategoryConfig.value.text_class)
const inline = computed(() => false)
const borderColor = computed(() => ratingCategoryConfig.value.border_color)
const activeBorderColor = computed(() => ratingCategoryConfig.value.border_color)
const borderWidth = computed(() => ratingCategoryConfig.value.border_width)
const roundedCorners = computed(() => false)
const padding = computed(() => ratingCategoryConfig.value.star_spacing)
const rtl = computed(() => ratingCategoryConfig.value.rtl)
const fixedPoints = computed(() => ratingCategoryConfig.value.fixed_points)
const glow = computed(() => ratingCategoryConfig.value.glow)
const glowColor = computed(() => ratingCategoryConfig.value.glow_color)
const clearable = computed(() => false)
const activeOnClick = computed(() => false)
const animate = computed(() => false)

// reactive data
const step = ref(0)
const fillLevel = ref([])
const currentRating = ref(0)
const selectedRating = ref(0)
const ratingSelected = ref(false)

// computed
const formattedRating = computed(() => {
  return (fixedPoints.value === null) ? currentRating.value : currentRating.value.toFixed(fixedPoints.value)
})

const shouldRound = computed(() => {
  return ratingSelected.value || roundStartRating.value
})

const margin = computed(() => {
  return padding.value + borderWidth.value
})

const activeColors = computed(() => {
  if (Array.isArray(activeColor.value)) {
    return padColors(activeColor.value, maxRating.value, activeColor.value.slice(-1)[0])
  }
  return new Array(maxRating.value).fill(activeColor.value)
})

const currentActiveColor = computed(() => {
  if (!activeOnClick.value) {
    return (currentRating.value > 0) ? activeColors.value[Math.ceil(currentRating.value) - 1] : inactiveColor.value
  }
  return (selectedRating.value > 0) ? activeColors.value[Math.ceil(selectedRating.value) - 1] : inactiveColor.value
})

const activeBorderColors = computed(() => {
  if (Array.isArray(activeBorderColor.value)) {
    return padColors(activeBorderColor.value, maxRating.value, activeBorderColor.value.slice(-1)[0])
  }
  let borderColorValue = (activeBorderColor.value) ? activeBorderColor.value : borderColor.value
  return new Array(maxRating.value).fill(borderColorValue)
})

const currentActiveBorderColor = computed(() => {
  if (!activeOnClick.value) {
    return (currentRating.value > 0) ? activeBorderColors.value[Math.ceil(currentRating.value) - 1] : borderColor.value
  }
  return (selectedRating.value > 0) ? activeBorderColors.value[Math.ceil(selectedRating.value) - 1] : borderColor.value
})

// methods
const setRating = ($event, persist) => {
  if (readOnly.value) {
    return
  }

  // 별점 계산: star-id는 1부터 시작하므로 -1을 해서 0부터 시작하게 함
  const starIndex = $event.id - 1
  const position = (rtl.value) ? (100 - $event.position) / 100 : $event.position / 100

  // increment에 맞춰서 별점 계산
  const baseRating = starIndex + position
  const newRating = Math.min(maxRating.value, baseRating)

  // increment에 맞춰 반올림
  const inv = 1.0 / increment.value
  const roundedRating = Math.round(newRating * inv) / inv

  if (persist) {
    // 클릭으로 확정된 경우
    currentRating.value = roundedRating
    createStars(true, true)
    if (clearable.value && roundedRating === selectedRating.value) {
      selectedRating.value = 0
    } else {
      selectedRating.value = roundedRating
    }

    emit('update:modelValue', selectedRating.value)
    emit('rating-selected', {
      category: props.ratingCategory.title || 'rating',
      rating: selectedRating.value
    })
    emit('rating-changed', {
      category: props.ratingCategory.title || 'rating',
      rating: selectedRating.value
    })
    ratingSelected.value = true
  } else {
    // 호버로 임시 변경된 경우 - 확정된 별점이 있으면 호버 효과를 무시
    if (ratingSelected.value && selectedRating.value > 0) {
      return
    }
    currentRating.value = roundedRating
    createStars(true, !activeOnClick.value)
    emit('current-rating', currentRating.value)
  }
}

const resetRating = () => {
  if (readOnly.value) {
    return
  }

  currentRating.value = selectedRating.value
  createStars(shouldRound.value)
}

const createStars = (shouldRound = true, applyFill = true) => {
  // 확정된 별점이 있으면 selectedRating을 사용, 없으면 currentRating 사용
  const displayRating = (ratingSelected.value && selectedRating.value > 0) ? selectedRating.value : currentRating.value

  if (shouldRound) {
    roundRating(displayRating)
  }
  for (let i = 0; i < maxRating.value; i++) {
    let level = 0
    if (i < displayRating) {
      level = (displayRating - i > 1) ? 100 : (displayRating - i) * 100
    }
    if (applyFill) {
      fillLevel.value[i] = Math.round(level)
    }
  }
}

const roundRating = (ratingToRound = currentRating.value) => {
  const inv = 1.0 / increment.value
  const roundedRating = Math.min(maxRating.value, Math.ceil(ratingToRound * inv) / inv)

  // 확정된 별점이 있으면 selectedRating을 업데이트, 없으면 currentRating을 업데이트
  if (ratingSelected.value && selectedRating.value > 0) {
    selectedRating.value = roundedRating
  } else {
    currentRating.value = roundedRating
  }
}

const padColors = (array, minLength, fillValue) => {
  return Object.assign(new Array(minLength).fill(fillValue), array)
}

// watchers
watch(() => props.modelValue, (val) => {
  currentRating.value = val
  selectedRating.value = val
  createStars(shouldRound.value)
})

// lifecycle
onMounted(() => {
  step.value = increment.value * 100
  currentRating.value = rating.value
  selectedRating.value = currentRating.value
  createStars(roundStartRating.value)
})
</script>

<style scoped>
.star-rating-wrapper {
  display: inline-block;
}

.star-rating-star {
  display: inline-block;
}

.star-rating-pointer {
  cursor: pointer;
}

.star-rating {
  display: flex;
  align-items: center;
}

.star-rating-inline {
  display: inline-flex;
}

.star-rating-rating-text {
  margin-left: 7px;
}

.star-rating-rtl {
  direction: rtl;
}

.star-rating-rtl .star-rating-rating-text {
  margin-right: 10px;
  direction: rtl;
}

.sr-only {
  position: absolute;
  left: -10000px;
  top: auto;
  width: 1px;
  height: 1px;
  overflow: hidden;
}
</style>
