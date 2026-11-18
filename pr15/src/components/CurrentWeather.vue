<script setup>
import { computed } from 'vue'
import codes from '../../dataset/weather_codes.json'
import { describeCode } from '../weather.js'

const props = defineProps({ current: Object, city: String })
const weather = computed(() => describeCode(props.current.weather_code, codes))
</script>

<template>
  <section class="panel weather-hero">
    <h2>{{ city }} · Сейчас</h2>
    <p class="temperature">{{ current.temperature_2m }} °C</p>
    <p><span aria-hidden="true">{{ weather.icon }}</span> {{ weather.label }}</p>
    <p class="muted">Ветер {{ current.wind_speed_10m }} км/ч</p>
  </section>
</template>
