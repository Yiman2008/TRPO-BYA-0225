<script setup>
import { onMounted } from 'vue'
import FilterPanel from './components/FilterPanel.vue'
import ItemList from './components/ItemList.vue'
import ItemForm from './components/ItemForm.vue'
import { filters, labels, useItems } from './composables/useItems.js'

const { status, query, loading, error, visibleItems, count, load, add, toggle } = useItems()
onMounted(() => load(new URLSearchParams(location.search).get('state')))
</script>


<template>
  <main>
    <header><h1>Каталог задач</h1><p class="muted">Практическая работа №13 · Vue Composition API</p></header>
    <p v-if="loading" class="panel" role="status">Загружаем задачи…</p>
    <p v-else-if="error" class="panel error" role="alert">Ошибка загрузки. {{ error }}</p>
    <section v-else aria-label="Управление задачами">
      <ItemForm @add="add" />
      <FilterPanel :filters="filters" :status="status" :query="query" @status="status = $event" @query="query = $event" />
      <h2>Задачи <span class="muted" aria-live="polite">({{ count }})</span></h2>
      <p v-if="count === 0" class="panel">Задачи не найдены.</p>
      <ItemList v-else :items="visibleItems" :labels="labels" @toggle="toggle" />
    </section>
  </main>
</template>
