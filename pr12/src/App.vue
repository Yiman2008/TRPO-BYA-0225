<script>
import filters from '../dataset/filters.json'
import uiStates from '../dataset/ui_states.json'
import FilterPanel from './components/FilterPanel.vue'
import ItemList from './components/ItemList.vue'
import ItemForm from './components/ItemForm.vue'
import { createItem, filterItems, toggleItem } from './domain.js'

export default {
  components: { FilterPanel, ItemList, ItemForm },
  data: () => ({ items: [], status: 'all', query: '', loading: true, error: '', filters }),
  computed: {
    visibleItems() { return filterItems(this.items, this.status, this.query) },
    count() { return this.visibleItems.length },
    labels() { return Object.fromEntries(this.filters.map(({ id, title }) => [id, title])) }
  },
  async mounted() {
    const scenario = new URLSearchParams(location.search).get('state')
    const state = uiStates.find(item => item.state === scenario)
    if (state?.state === 'loading') return
    try {
      if (state?.state === 'error') throw new Error(state.message)
      const { default: source } = await import('../dataset/items.json')
      this.items = state?.state === 'empty' ? [] : source
    } catch (error) {
      this.error = error.message
    } finally {
      this.loading = false
    }
  },
  methods: {
    add(title) { this.items = [...this.items, createItem(this.items, title)] },
    toggle(id) { this.items = toggleItem(this.items, id) }
  }
}
</script>

<template>
  <main>
    <header><h1>Каталог задач</h1><p class="muted">Практическая работа №12 · Vue Options API</p></header>
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
