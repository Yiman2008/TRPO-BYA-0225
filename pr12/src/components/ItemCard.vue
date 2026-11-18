<script>
export default {
  props: { item: { type: Object, required: true }, labels: Object },
  emits: ['toggle']
}
</script>

<template>
  <article class="card">
    <h3>{{ item.title }}</h3>
    <p>{{ labels[item.status] }} · Приоритет {{ item.priority }}</p>
    <button type="button" @click="$emit('toggle', item.id)">
      {{ item.status === 'done' ? 'Вернуть в новые' : 'Отметить готовой' }}
    </button>
  </article>
</template>
