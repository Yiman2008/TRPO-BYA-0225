import { ref } from 'vue'
import fields from '../dataset/api_fields.json'
import mock from '../dataset/mock_forecast.json'
import { normalizeForecast, requestWeather } from './weather.js'

export function useWeather() {
  const data = ref(null)
  const loading = ref(false)
  const error = ref('')
  const updatedAt = ref(null)

  async function load(city, offline = false) {
    if (loading.value) return
    error.value = ''
    data.value = null
    updatedAt.value = null
    if (!city) {
      error.value = 'Сначала выберите город.'
      return
    }
    loading.value = true
    try {
      data.value = offline ? normalizeForecast(mock, fields) : await requestWeather(city, fields)
      updatedAt.value = Date.now()
    } catch (cause) {
      error.value = cause.message
    } finally {
      loading.value = false
    }
  }

  return { data, loading, error, updatedAt, load }
}
