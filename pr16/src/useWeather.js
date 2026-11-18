import { computed, shallowRef } from 'vue'
import fields from '../../pr15/dataset/api_fields.json'
import mock from '../dataset/mock_forecast.json'
import { normalizeForecast, requestWeather } from './weather.js'
import { createWeatherLoader } from './cache.js'

export function useWeather() {
  const state = shallowRef({ record: null, loading: false, error: '', stale: false, fromCache: false })
  const load = createWeatherLoader({
    request: (city, offline, signal) => offline
      ? normalizeForecast(mock, fields)
      : requestWeather(city, fields, { signal }),
    update: next => { state.value = next }
  })

  return {
    data: computed(() => state.value.record?.data),
    updatedAt: computed(() => state.value.record?.savedAt),
    loading: computed(() => state.value.loading),
    error: computed(() => state.value.error),
    stale: computed(() => state.value.stale),
    fromCache: computed(() => state.value.fromCache),
    load
  }
}
