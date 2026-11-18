<script setup>
import { computed, ref } from 'vue'
import csv from '../dataset/cities.csv?raw'
import CitySelector from '../../pr15/src/components/CitySelector.vue'
import CurrentWeather from '../../pr15/src/components/CurrentWeather.vue'
import ForecastList from '../../pr15/src/components/ForecastList.vue'
import { useWeather } from './useWeather.js'
import { parseCities } from './weather.js'
import { readFavorites, toggleFavorite, writeFavorites } from './favorites.js'

const cities = parseCities(csv)
const storage = {
  getItem: key => window.localStorage.getItem(key),
  setItem: (key, value) => window.localStorage.setItem(key, value)
}
const favorites = ref(readFavorites(storage, cities))
const storageError = ref('')
const favoriteCities = computed(() => cities.filter(option => favorites.value.includes(option.id)))
const isFavorite = computed(() => favorites.value.includes(cityId.value))

function saveFavorite() {
  if (!city.value) return
  favorites.value = toggleFavorite(favorites.value, cityId.value)
  storageError.value = writeFavorites(storage, favorites.value)
    ? ''
    : 'Хранилище браузера недоступно. Избранное сохранено до перезагрузки страницы.'
}
const cityId = ref('')
const offline = ref(false)
const city = computed(() => cities.find(option => option.id === cityId.value))
const { data, loading, error, updatedAt, stale, fromCache, load } = useWeather()
const time = new Intl.DateTimeFormat('ru-RU', { timeStyle: 'medium' })
const updatedLabel = computed(() => updatedAt.value === undefined ? '' : time.format(updatedAt.value))

function refresh(force = false) { return load(city.value, { offline: offline.value, force }) }
function choose(id) {
  cityId.value = id
  return refresh()
}
</script>

<template>
  <main>
    <header><h1>Погодный менеджер</h1><p class="muted">Практическая работа №16 · Избранное и кэш</p></header>
    <section class="panel" aria-label="Настройки прогноза">
      <div class="controls">
        <CitySelector :cities="cities" :city="cityId" @choose="choose" />
        <label class="check">
          <input v-model="offline" type="checkbox" @change="city && refresh()">
          Деморежим без API
        </label>
        <button type="button" class="primary" :disabled="loading" @click="refresh(true)">
          {{ updatedAt ? 'Обновить' : 'Загрузить прогноз' }}
        </button>
        <button type="button" :disabled="!city" :aria-pressed="isFavorite" @click="saveFavorite">
          {{ isFavorite ? 'В избранном' : 'В избранное' }}
        </button>
      </div>
      <div v-if="favoriteCities.length" class="favorite-list" aria-label="Избранные города">
        <button v-for="option in favoriteCities" :key="option.id" type="button" @click="choose(option.id)">
          {{ option.name }}
        </button>
      </div>
      <p v-if="storageError" class="error" role="status">{{ storageError }}</p>
    </section>
    <p v-if="loading" class="panel" role="status">Загружаем прогноз…</p>
    <p v-if="error" class="panel error" role="alert">{{ error }}</p>
    <p v-if="stale" class="panel alert">Показан последний полученный прогноз. Данные могут быть устаревшими.</p>
    <p v-if="!loading && !error && !data" class="panel">Выберите город, чтобы увидеть прогноз.</p>
    <template v-if="data">
      <p class="muted" role="status">{{ offline ? 'Демонстрационные данные · ' : '' }}{{ fromCache ? 'Из кэша · ' : '' }}Обновлено в {{ updatedLabel }}</p>
      <CurrentWeather :current="data.current" :city="city.name" />
      <ForecastList :days="data.forecast" />
    </template>
  </main>
</template>
