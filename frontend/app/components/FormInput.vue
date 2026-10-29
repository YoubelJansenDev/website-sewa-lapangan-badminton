<script setup lang="ts">
// Komponen input form standar: label, input, dan pesan error Bahasa Indonesia.
const props = withDefaults(
  defineProps<{
    modelValue: string
    label: string
    type?: string
    placeholder?: string
    error?: string
    hint?: string
    required?: boolean
  }>(),
  { type: 'text', placeholder: '', error: '', hint: '', required: false },
)

const emit = defineEmits<{ (e: 'update:modelValue', value: string): void }>()

const inner = computed({
  get: () => props.modelValue,
  set: (value: string) => emit('update:modelValue', value),
})
</script>

<template>
  <div>
    <label class="mb-1.5 block text-sm font-semibold text-black">
      {{ label }}<span v-if="required" class="text-green-600"> *</span>
    </label>
    <input
      v-model="inner"
      :type="type"
      :placeholder="placeholder"
      class="w-full rounded-xl border bg-white px-4 py-2.5 text-sm text-black outline-none transition focus:border-green-600 focus:ring-2 focus:ring-green-100"
      :class="error ? 'border-red-500' : 'border-gray-300'"
    >
    <p v-if="error" class="mt-1.5 text-sm text-red-600">{{ error }}</p>
    <p v-else-if="hint" class="mt-1.5 text-sm text-gray-500">{{ hint }}</p>
  </div>
</template>
