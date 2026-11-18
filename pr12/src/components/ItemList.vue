<script>
import ItemCard from './ItemCard.vue'

export default {
  components: { ItemCard },
  props: { items: Array, labels: Object },
  emits: ['toggle']
}
</script>

<template>
  <ul class="grid">
    <li v-for="item in items" :key="item.id">
      <ItemCard :item="item" :labels="labels" @toggle="$emit('toggle', $event)" />
    </li>
  </ul>
</template>
