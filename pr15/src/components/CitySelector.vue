<script setup>
defineProps({ cities: Array, city: String, disabled: Boolean })
const emit = defineEmits(['choose'])
</script>

<template>
  <label>Город
    <select :value="city" :disabled="disabled" @change="emit('choose', $event.target.value)">
      <option value="">Выберите город</option>
      <option v-for="option in cities" :key="option.id" :value="option.id">{{ option.name }}</option>
    </select>
  </label>
</template>
