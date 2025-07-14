<template>
  <svg
    :class="['star-rating-star', {'star-rating-star-rotate' : shouldAnimate}]"
    :height="starSize"
    :width="starSize"
    :viewBox="viewBox"
    @mousemove="mouseMoving"
    @click="selected"
    @touchstart="touchStart"
    @touchend="touchEnd"
  >
    <linearGradient
      :id="grad"
      x1="0"
      x2="100%"
      y1="0"
      y2="0"
    >
      <stop
        :offset="starFill"
        :stop-color="(rtl) ? getColor(inactiveColor) : getColor(activeColor)"
        :stop-opacity="(rtl) ? getOpacity(inactiveColor) : getOpacity(activeColor)"
      />
      <stop
        :offset="starFill"
        :stop-color="(rtl) ? getColor(activeColor) : getColor(inactiveColor)"
        :stop-opacity="(rtl) ? getOpacity(activeColor) : getOpacity(inactiveColor)"
      />
    </linearGradient>

    <filter
      :id="glowId"
      height="130%"
      width="130%"
      filterUnits="userSpaceOnUse"
    >
      <feGaussianBlur
        :stdDeviation="glow"
        result="coloredBlur"
      />
      <feMerge>
        <feMergeNode in="coloredBlur" />
        <feMergeNode in="SourceGraphic" />
      </feMerge>
    </filter>

    <polygon
      v-if="glowColor && glow > 0"
      v-show="fill > 1"
      :points="starPointsToString"
      :fill="gradId"
      :stroke="glowColor"
      :filter="'url(#'+glowId+')'"
      :stroke-width="border"
    />

    <polygon
      :points="starPointsToString"
      :fill="gradId"
      :stroke="getBorderColor"
      :stroke-width="border"
      :stroke-linejoin="strokeLinejoin"
    />
    <polygon
      :points="starPointsToString"
      :fill="gradId"
    />
  </svg>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import AlphaColor from '../alpha_color.js'

const props = defineProps({
  fill: {
    type: Number,
    default: 0
  },
  points: {
    type: Array,
    default: () => []
  },
  size: {
    type: Number,
    default: 50
  },
  starId: {
    type: Number,
    required: true
  },
  activeColor: {
    type: String,
    required: true
  },
  inactiveColor: {
    type: String,
    required: true
  },
  borderColor: {
    type: String,
    default: '#000'
  },
  activeBorderColor: {
    type: String,
    default: '#000'
  },
  borderWidth: {
    type: Number,
    default: 0
  },
  roundedCorners: {
    type: Boolean,
    default: false
  },
  rtl: {
    type: Boolean,
    default: false
  },
  glow: {
    type: Number,
    default: 0
  },
  glowColor: {
    type: String,
    default: null
  },
  animate: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['star-selected', 'star-mouse-move'])

const starPoints = ref([19.8, 2.2, 6.6, 43.56, 39.6, 17.16, 0, 17.16, 33, 43.56])
const grad = ref('')
const glowId = ref('')
const isStarActive = ref(true)

const starPointsToString = computed(() => {
  return starPoints.value.join(',')
})

const gradId = computed(() => {
  return 'url(#' + grad.value + ')'
})

const starSize = computed(() => {
  // Adjust star size when rounded corners are set with no border, to account for the 'hidden' border
  const size = (props.roundedCorners && props.borderWidth <= 0) ? parseInt(props.size) - parseInt(border.value) : props.size
  return parseInt(size) + parseInt(border.value)
})

const starFill = computed(() => {
  return (props.rtl) ? 100 - props.fill + '%' : props.fill + '%'
})

const border = computed(() => {
  return (props.roundedCorners && props.borderWidth <= 0) ? 6 : props.borderWidth
})

const getBorderColor = computed(() => {
  if (props.roundedCorners && props.borderWidth <= 0) {
    // create a hidden border
    return (props.fill <= 0) ? props.inactiveColor : props.activeColor
  }
  return (props.fill <= 0) ? props.borderColor : props.activeBorderColor
})

const maxSize = computed(() => {
  return starPoints.value.reduce((a, b) => Math.max(a, b))
})

const viewBox = computed(() => {
  return '0 0 ' + maxSize.value + ' ' + maxSize.value
})

const shouldAnimate = computed(() => {
  return props.animate && isStarActive.value
})

const strokeLinejoin = computed(() => {
  return props.roundedCorners ? 'round' : 'miter'
})

const mouseMoving = ($event) => {
  if ($event.touchAction !== 'undefined') {
    emit('star-mouse-move', {
      event: $event,
      position: getPosition($event),
      id: props.starId
    })
  }
}

const touchStart = () => {
  isStarActive.value = true
}

const touchEnd = () => {
  isStarActive.value = false
}

const getPosition = ($event) => {
  // calculate position in percentage.
  const starWidth = (92 / 100) * props.size
  const offset = (props.rtl) ? Math.min($event.offsetX, 45) : Math.max($event.offsetX, 1)
  const position = Math.round((100 / starWidth) * offset)
  return Math.min(position, 100)
}

const selected = ($event) => {
  emit('star-selected', {
    id: props.starId,
    position: getPosition($event)
  })
}

const getRandomId = () => {
  return Math.random().toString(36).substring(7)
}

const calculatePoints = () => {
  starPoints.value = starPoints.value.map((point, i) => {
    const offset = i % 2 === 0 ? border.value * 1.5 : 0
    return ((props.size / maxSize.value) * point) + offset
  })
}

const getColor = (color) => {
  return new AlphaColor(color).parseAlphaColor().color
}

const getOpacity = (color) => {
  return new AlphaColor(color).parseAlphaColor().opacity
}

onMounted(() => {
  starPoints.value = (props.points.length) ? props.points : starPoints.value
  calculatePoints()
  grad.value = getRandomId()
  glowId.value = getRandomId()
})
</script>

<style scoped>
.star-rating-star {
  overflow: visible !important;
}

.star-rating-star-rotate {
  transition: all .25s;
}

.star-rating-star-rotate:hover {
  transition: transform 0.25s;
  transform: rotate(-15deg) scale(1.3)
}
</style>
