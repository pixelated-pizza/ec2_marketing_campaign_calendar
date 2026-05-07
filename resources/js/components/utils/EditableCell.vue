<script setup>
import { ref, onMounted, onUnmounted } from "vue";

const props = defineProps({
  modelValue: {
    type: [String, Number, Boolean],
    default: ""
  },
  rowData: {
    type: Object,
    default: () => ({}),
  },
  field: {
    type: String,
    required: true
  },
  saveFunc: {
    type: Function,
    required: true
  }
});

const emit = defineEmits(["update:modelValue"]);

const isEditing = ref(false);
const inputValue = ref(props.modelValue);
const inputRef = ref(null);

function startEdit() {
  isEditing.value = true;
  inputValue.value = props.modelValue;
  setTimeout(() => inputRef.value?.focus(), 10);
}

async function stopEdit() {
  if (!isEditing.value) return;
  isEditing.value = false;

  if (inputValue.value !== props.modelValue) {
    emit("update:modelValue", inputValue.value);
    // Pass rowData as 4th argument to saveFunc
    await props.saveFunc(props.field, inputValue.value, props.rowData);
  }
}

function handleClickOutside(e) {
  if (!e.target.closest(".editable-cell")) stopEdit();
}

onMounted(() => document.addEventListener("click", handleClickOutside));
onUnmounted(() => document.removeEventListener("click", handleClickOutside));
</script>

<template>
  <div class="editable-cell w-full" @click.stop="startEdit">
    <input
      v-if="isEditing"
      ref="inputRef"
      v-model="inputValue"
      @keyup.enter="stopEdit"
      @blur="stopEdit"
      class="w-full bg-gray-800 text-white p-1 border border-gray-600 rounded"
    />
    <span v-else>
      {{ modelValue === '' || modelValue == null ? 'No Data Yet' : modelValue }}
    </span>
  </div>
</template>

<style scoped>
.editable-cell {
  cursor: pointer;
}
</style>
