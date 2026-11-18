import { computed, ref } from 'vue'
import optionsItems from '../../../pr12/dataset/items.json'
import filters from '../../../pr12/dataset/filters.json'
import uiStates from '../../../pr12/dataset/ui_states.json'
import edgeCases from '../../../pr13/dataset/edge_cases.json'
import { adaptItems, createItem, filterItems, toggleItem } from '../domain.js'

import brokenItems from '../../dataset/broken_items.json'

const labels = Object.fromEntries(filters.map(({ id, title }) => [id, title]))
export { filters, labels }

export function useItems() {
  const items = ref([])
  const query = ref('')
  const status = ref('all')
  const loading = ref(true)
  const error = ref('')
  const visibleItems = computed(() => filterItems(items.value, status.value, query.value))
  const count = computed(() => visibleItems.value.length)

  async function load(scenario) {
    if (scenario === 'loading') return
    try {
      const uiState = uiStates.find(item => item.state === scenario)
      if (uiState?.message) throw new Error(uiState.message)
      const edge = edgeCases.find(item => item.case === scenario)
      if (scenario === 'broken') items.value = adaptItems(brokenItems)
      else if (scenario === 'options') items.value = adaptItems(optionsItems)
      else if (scenario === 'empty') items.value = []
      else if (edge) items.value = adaptItems(edge.items)
      else {
        const { default: source } = await import('../../../pr13/dataset/items.json')
        items.value = adaptItems(source)
      }
    } catch (cause) {
      error.value = cause.message
    } finally {
      loading.value = false
    }
  }

  function add(title) {
    items.value = [...items.value, createItem(items.value, title)]
  }

  function toggle(id) {
    items.value = toggleItem(items.value, id)
  }

  return { status, query, loading, error, visibleItems, count, load, add, toggle }
}
