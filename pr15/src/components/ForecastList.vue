<script setup>
import { computed } from 'vue'
import codes from '../../dataset/weather_codes.json'
import { describeCode } from '../weather.js'

const props = defineProps({ days: Array })
const formatter = new Intl.DateTimeFormat('ru-RU', { day: 'numeric', month: 'long', timeZone: 'UTC' })
const forecast = computed(() => props.days.map(day => ({
  ...day,
  label: formatter.format(new Date(day.date + 'T00:00:00Z')),
  weather: describeCode(day.weather_code, codes)
})))
</script>

<template>
  <section aria-label="Прогноз на три дня">
    <h2>Ближайшие дни</h2>
    <ul class="grid">
      <li v-for="day in forecast" :key="day.date" class="card">
        <h3><time :datetime="day.date">{{ day.label }}</time></h3>
        <p><span aria-hidden="true">{{ day.weather.icon }}</span> {{ day.weather.label }}</p>
        <p>Мин. {{ day.temperature_2m_min }} °C · Макс. {{ day.temperature_2m_max }} °C</p>
      </li>
    </ul>
  </section>
</template>
