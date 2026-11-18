export const WEATHER_TTL = 5 * 60 * 1000

export function createWeatherCache(now = Date.now) {
  const records = new Map()
  return {
    get(key) { return records.get(key) },
    isFresh(record) {
      const age = record ? now() - record.savedAt : Infinity
      return age >= 0 && age < WEATHER_TTL
    },
    set(key, data) {
      const record = { data, savedAt: now() }
      records.set(key, record)
      return record
    }
  }
}

export function createWeatherLoader({ request, update, cache = createWeatherCache() }) {
  let active

  return async function load(city, { offline = false, force = false } = {}) {
    const key = city && JSON.stringify([city.id, offline])
    if (active && active.key === key) return
    active?.controller.abort()
    active = undefined

    if (!city) {
      update({ error: 'Сначала выберите город.', record: null, loading: false, stale: false, fromCache: false })
      return
    }

    const cached = cache.get(key)
    if (!force && cache.isFresh(cached)) {
      update({ record: cached, error: '', loading: false, stale: false, fromCache: true })
      return
    }

    const current = { key, controller: new AbortController() }
    active = current
    update({ record: cached ?? null, error: '', loading: true, stale: Boolean(cached), fromCache: false })
    try {
      const data = await request(city, offline, current.controller.signal)
      if (active !== current) return
      update({ record: cache.set(key, data), error: '', loading: false, stale: false, fromCache: false })
    } catch (cause) {
      if (active !== current) return
      update({ record: cached ?? null, error: cause.message, loading: false, stale: Boolean(cached), fromCache: false })
    } finally {
      if (active === current) active = undefined
    }
  }
}
