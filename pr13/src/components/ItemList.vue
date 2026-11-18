<script setup>
import ItemCard from './ItemCard.vue'

defineProps({ items: Array, labels: Object })
defineEmits(['toggle'])
</script>

<template>
  <ul class="grid">
    <li v-for="item in items" :key="item.id">
      <ItemCard :item="item" :labels="labels" @toggle="$emit('toggle', $event)" />
    </li>
  </ul>
</template>
