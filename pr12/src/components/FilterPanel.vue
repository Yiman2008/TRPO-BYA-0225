<script>
export default {
  props: { filters: Array, status: String, query: String },
  emits: ['status', 'query']
}
</script>

<template>
  <div class="panel controls">
    <label>Поиск по названию
      <input type="search" :value="query" @input="$emit('query', $event.target.value)">
    </label>
    <label>Статус
      <select :value="status" @change="$emit('status', $event.target.value)">
        <option v-for="filter in filters" :key="filter.id" :value="filter.id">{{ filter.title }}</option>
      </select>
    </label>
  </div>
</template>
