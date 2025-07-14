<template>
  <div class="flex items-center space-x-2 relative">
    <!-- 이모지 추가 버튼 (로그인 사용자만) -->
    <button type="button" v-if="canReact" @click="togglePicker" class="emoji-add-btn text-gray-500 rounded-full bg-gray-100 hover:bg-gray-200 text-lg font-bold py-1.5 px-2.5">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
            <path d="M17.5,12 C20.5375661,12 23,14.4624339 23,17.5 C23,20.5375661 20.5375661,23 17.5,23 C14.4624339,23 12,20.5375661 12,17.5 C12,14.4624339 14.4624339,12 17.5,12 Z M12.0000002,1.99896738 C17.523704,1.99896738 22.0015507,6.47681407 22.0015507,12.0005179 C22.0015507,12.2637452 21.9913819,12.5245975 21.9714157,12.7827034 C21.5335438,12.3671164 21.0376367,12.012094 20.4972374,11.7307716 C20.3551544,7.16057357 16.6051843,3.49896738 12.0000002,3.49896738 C7.30472352,3.49896738 3.49844971,7.30524119 3.49844971,12.0005179 C3.49844971,16.6060394 7.16059249,20.3562216 11.7317296,20.4979161 C12.0124658,21.0381559 12.3673338,21.5337732 12.7825138,21.9716342 C12.5247521,21.9918733 12.2635668,22.0020684 12.0000002,22.0020684 C6.47629639,22.0020684 1.99844971,17.5242217 1.99844971,12.0005179 C1.99844971,6.47681407 6.47629639,1.99896738 12.0000002,1.99896738 Z M17.5,13.9992349 L17.4101244,14.0072906 C17.2060313,14.0443345 17.0450996,14.2052662 17.0080557,14.4093593 L17,14.4992349 L16.9996498,16.9992349 L14.4976498,17 L14.4077742,17.0080557 C14.2036811,17.0450996 14.0427494,17.2060313 14.0057055,17.4101244 L13.9976498,17.5 L14.0057055,17.5898756 C14.0427494,17.7939687 14.2036811,17.9549004 14.4077742,17.9919443 L14.4976498,18 L17.0006498,17.9992349 L17.0011076,20.5034847 L17.0091633,20.5933603 C17.0462073,20.7974534 17.207139,20.9583851 17.411232,20.995429 L17.5011076,21.0034847 L17.5909833,20.995429 C17.7950763,20.9583851 17.956008,20.7974534 17.993052,20.5933603 L18.0011076,20.5034847 L18.0006498,17.9992349 L20.5045655,18 L20.5944411,17.9919443 C20.7985342,17.9549004 20.9594659,17.7939687 20.9965098,17.5898756 L21.0045655,17.5 L20.9965098,17.4101244 C20.9594659,17.2060313 20.7985342,17.0450996 20.5944411,17.0080557 L20.5045655,17 L17.9996498,16.9992349 L18,14.4992349 L17.9919443,14.4093593 C17.9549004,14.2052662 17.7939687,14.0443345 17.5898756,14.0072906 L17.5,13.9992349 Z M8.46174078,14.7838355 C9.12309331,15.6232213 10.0524954,16.1974014 11.0917655,16.4103066 C11.0312056,16.7638158 11,17.1282637 11,17.5 C11,17.6408778 11.0044818,17.7807089 11.0133105,17.9193584 C9.53812034,17.6766509 8.21128537,16.8896809 7.28351576,15.7121597 C7.02716611,15.3868018 7.08310832,14.9152347 7.40846617,14.6588851 C7.73382403,14.4025354 8.20539113,14.4584777 8.46174078,14.7838355 Z M9.00044779,8.75115873 C9.69041108,8.75115873 10.2497368,9.3104845 10.2497368,10.0004478 C10.2497368,10.6904111 9.69041108,11.2497368 9.00044779,11.2497368 C8.3104845,11.2497368 7.75115873,10.6904111 7.75115873,10.0004478 C7.75115873,9.3104845 8.3104845,8.75115873 9.00044779,8.75115873 Z M15.0004478,8.75115873 C15.6904111,8.75115873 16.2497368,9.3104845 16.2497368,10.0004478 C16.2497368,10.6904111 15.6904111,11.2497368 15.0004478,11.2497368 C14.3104845,11.2497368 13.7511587,10.6904111 13.7511587,10.0004478 C13.7511587,9.3104845 14.3104845,8.75115873 15.0004478,8.75115873 Z" />
        </svg>
    </button>
    <!-- 이모지 선택 레이어 -->
    <div v-if="showPicker" class="absolute z-10 top-full left-0 bg-white border rounded shadow p-2 flex flex-wrap w-48">
      <button v-for="(emoji,key) in reactionTypes" :key="emoji" @click="addReaction(key)" class="text-xl p-1 hover:bg-gray-100 rounded">
        {{ emoji }}
      </button>
    </div>
    <!-- 리액션 이모지 버튼들 (1개 이상만) -->
    <button
      v-for="(data, emojiKey) in filteredReactions"
      :key="emojiKey"
      @click="canReact ? toggleReaction(emojiKey) : null"
      :disabled="!canReact"
      :class="['reaction-btn flex items-center px-2 py-1 rounded-full text-xs transition-colors', data.isMine ? 'border-blue-500 border-2 bg-blue-50' : 'bg-gray-100 hover:bg-gray-200', !canReact ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-200']"
      @mouseenter="showTooltip(emojiKey)"
      @mouseleave="hideTooltip"
    >
      <span class="text-base">{{ reactionTypes[emojiKey] }}</span>
      <span class="ml-1">{{ data.count }}</span>
      <!-- 유저목록 툴팁 -->
      <div v-if="tooltipEmoji === emojiKey" class="absolute bg-white border rounded shadow px-2 py-1 top-full left-0 text-xs z-20">
        <div v-for="user in data.users" :key="user.id">{{ user.name }}</div>
      </div>
    </button>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import {usePage} from "@inertiajs/vue3";

const props = defineProps({
  reactionsSummary: { type: Object, required: true },
  reactionTypes: { type: Object, default: {} }
})
const emit = defineEmits(['reaction-toggle', 'reaction-add'])

const showPicker = ref(false)
const tooltipEmoji = ref(null)

const canReact = computed(() => !!usePage().props.auth.user)

const filteredReactions = computed(() => {
  // count > 0 인 것만, isMine은 users에 내 id가 있는지로 판단
  const result = {}
  const currentUser = usePage().props.auth.user
  
  for (const [emoji, data] of Object.entries(props.reactionsSummary || {})) {
    if (data.count > 0) {
      result[emoji] = {
        ...data,
        isMine: currentUser ? data.users?.some(u => String(u.id) === String(currentUser.id)) : false
      }
    }
  }
  return result
})

function togglePicker() {
  showPicker.value = !showPicker.value
}
function closePicker(e) {
  if (!e.target.closest('.emoji-add-btn') && !e.target.closest('.emoji-picker-tooltip')) {
    showPicker.value = false
  }
}
onMounted(() => {
  document.addEventListener('click', closePicker)
})
onBeforeUnmount(() => {
  document.removeEventListener('click', closePicker)
})

function addReaction(emoji) {
  try {
    console.log('ReactionBar addReaction called with emoji:', emoji)
    showPicker.value = false
    emit('reaction-add', emoji)
  } catch (error) {
    console.error('Error in addReaction:', error)
  }
}

function toggleReaction(emoji) {
  try {
    console.log('ReactionBar toggleReaction called with emoji:', emoji)
    emit('reaction-toggle', emoji)
  } catch (error) {
    console.error('Error in toggleReaction:', error)
  }
}
function showTooltip(emoji) {
  tooltipEmoji.value = emoji
}
function hideTooltip() {
  tooltipEmoji.value = null
}
</script>

<style scoped>
.emoji-add-btn {
  border: 1px solid #e5e7eb;
}
.emoji-picker-tooltip {
  min-width: 160px;
  max-width: 200px;
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}
.reaction-btn {
  position: relative;
}
.reaction-tooltip {
  min-width: 80px;
  left: 0;
  top: 100%;
  margin-top: 2px;
  pointer-events: none;
}
</style>
