<script setup>
import { ref, watch, nextTick, onMounted, onUnmounted } from "vue";

const props = defineProps({
  modelValue: String,

  rowData: {
    type: Object,
    default: () => ({}), // full row data
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

const editing = ref(false);
const innerValue = ref(props.modelValue);
const textareaRef = ref(null);

watch(() => props.modelValue, val => {
  innerValue.value = val;
});

function startEdit() {
  editing.value = true;
  nextTick(() => textareaRef.value?.focus());
}

async function stopEdit() {
  if (!editing.value) return;
  editing.value = false;

  if (innerValue.value !== props.modelValue) {
    emit("update:modelValue", innerValue.value);
    // Pass rowData as 4th argument
    await props.saveFunc(props.field, innerValue.value, props.rowData);
  }
}

function handleClickOutside(e) {
  if (!e.target.closest(".editable-textarea-cell")) stopEdit();
}

onMounted(() => {
  document.addEventListener("click", handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener("click", handleClickOutside);
});
</script>

<template>
  <div class="editable-textarea-cell w-full" @click.stop="startEdit">
    <textarea
      v-if="editing"
      ref="textareaRef"
      v-model="innerValue"
      class="w-full bg-gray-800 text-white p-2 rounded border border-gray-600"
      rows="3"
      @keyup.enter.prevent="stopEdit"
      @blur="stopEdit"
    ></textarea>

    <span v-else v-html="!props.modelValue ? 'No Data Yet' : props.modelValue.replace(/\n/g,'<br>')"></span>
  </div>
</template>

<style scoped>
.editable-textarea-cell {
  cursor: pointer;
}
</style>
