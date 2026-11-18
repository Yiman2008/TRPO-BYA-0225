<script setup>
import { ref } from 'vue'

const emit = defineEmits(['add'])
const title = ref('')
const error = ref('')

function submit() {
  const cleanTitle = title.value.trim()
  error.value = cleanTitle ? '' : 'Введите название задачи.'
  if (!cleanTitle) return
  emit('add', cleanTitle)
  title.value = ''
}
</script>

<template>
  <form class="panel controls" @submit.prevent="submit">
    <label>Новая задача
      <input v-model="title" aria-required="true" :aria-invalid="Boolean(error)" :aria-describedby="error ? 'title-error' : undefined">
    </label>
    <button class="primary" type="submit">Добавить</button>
    <p v-if="error" id="title-error" class="error" role="alert">{{ error }}</p>
  </form>
</template>
