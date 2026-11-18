<script setup>
import { computed, ref } from 'vue'
import csv from '../dataset/cities.csv?raw'
import CitySelector from './components/CitySelector.vue'
import CurrentWeather from './components/CurrentWeather.vue'
import ForecastList from './components/ForecastList.vue'
import { useWeather } from './useWeather.js'
import { parseCities } from './weather.js'

const cities = parseCities(csv)
const cityId = ref('')
const offline = ref(false)
const city = computed(() => cities.find(option => option.id === cityId.value))
const { data, loading, error, updatedAt, load } = useWeather()
const time = new Intl.DateTimeFormat('ru-RU', { timeStyle: 'medium' })
const updatedLabel = computed(() => updatedAt.value === null ? '' : time.format(updatedAt.value))

function refresh() { return load(city.value, offline.value) }
function choose(id) {
  cityId.value = id
  return refresh()
}
</script>

<template>
  <main>
    <header><h1>Погодный менеджер</h1><p class="muted">Практическая работа №15 · Open-Meteo</p></header>
    <section class="panel" aria-label="Настройки прогноза">
      <div class="controls">
        <CitySelector :cities="cities" :city="cityId" :disabled="loading" @choose="choose" />
        <label class="check">
          <input v-model="offline" type="checkbox" :disabled="loading" @change="city && refresh()">
          Деморежим без API
        </label>
        <button type="button" class="primary" :disabled="loading" @click="refresh">
          {{ updatedAt ? 'Обновить' : 'Загрузить прогноз' }}
        </button>
      </div>
    </section>
    <p v-if="loading" class="panel" role="status">Загружаем прогноз…</p>
    <p v-else-if="error" class="panel error" role="alert">{{ error }}</p>
    <p v-else-if="!data" class="panel">Выберите город, чтобы увидеть прогноз.</p>
    <template v-if="data">
      <p class="muted" role="status">{{ offline ? 'Демонстрационные данные · ' : '' }}Обновлено в {{ updatedLabel }}</p>
      <CurrentWeather :current="data.current" :city="city.name" />
      <ForecastList :days="data.forecast" />
    </template>
  </main>
</template>
