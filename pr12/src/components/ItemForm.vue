<script>
export default {
  emits: ['add'],
  data: () => ({ title: '', error: '' }),
  methods: {
    submit() {
      const title = this.title.trim()
      this.error = title ? '' : 'Введите название задачи.'
      if (!title) return
      this.$emit('add', title)
      this.title = ''
    }
  }
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
