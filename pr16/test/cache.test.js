import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import assert from 'node:assert/strict'
import { createWeatherCache, createWeatherLoader } from '../src/cache.js'
import { readFavorites, toggleFavorite, writeFavorites } from '../src/favorites.js'
import { describeCode, normalizeForecast, parseCities } from '../src/weather.js'

const read = (name, folder = '../dataset/') => readFileSync(new URL(folder + name, import.meta.url), 'utf8')
const json = (name, folder) => JSON.parse(read(name, folder))
const [moscow, petersburg] = parseCities(read('cities.csv'))
const mock = normalizeForecast(json('mock_forecast.json'), json('api_fields.json', '../../pr15/dataset/'))

function setup(request = async () => mock) {
  let now = 0
  let state
  let requests = 0
  const load = createWeatherLoader({
    cache: createWeatherCache(() => now),
    update: next => { state = next },
    request: (...args) => { requests++; return request(...args) }
  })
  return { load, at: ms => { now = ms }, state: () => state, requests: () => requests }
}

for (const scenario of json('cache_cases.json')) {
  test(scenario.case, async () => {
    const app = setup()
    await app.load(moscow)
    app.at(scenario.age_seconds * 1000)
    await app.load(moscow, { force: scenario.case === 'manual_refresh' })
    assert.equal(app.requests(), scenario.request_expected ? 2 : 1)
    assert.equal(app.state().fromCache, !scenario.request_expected)
  })
}

const checks = {
  async select_city() {
    const app = setup()
    await app.load(moscow)
    return app.state().record.data.forecast.length === 3 ? 'forecast_visible' : 'empty'
  },
  async api_error() {
    let fail = false
    const app = setup(async () => { if (fail) throw new Error('Сеть недоступна'); return mock })
    await app.load(moscow)
    fail = true
    await app.load(moscow, { force: true })
    assert.equal(app.state().stale, true)
    assert.equal(app.state().record.data, mock)
    return app.state().error ? 'error_message' : 'silent'
  },
  favorite_add() {
    const values = new Map()
    const storage = { getItem: key => values.get(key), setItem: (key, value) => values.set(key, value) }
    assert.equal(writeFavorites(storage, toggleFavorite([], moscow.id)), true)
    return readFavorites(storage, [moscow]).includes(moscow.id) ? 'persisted' : 'lost'
  },
  unknown_weather_code() {
    const codes = json('weather_codes.json', '../../pr15/dataset/')
    return describeCode(999, codes).label === 'Неизвестное состояние' ? 'fallback_label' : 'unknown'
  }
}
for (const scenario of json('test_cases.json')) {
  test(scenario.case, async () => assert.equal(await checks[scenario.case](), scenario.expected))
}

test('old requests cannot replace the selected city; duplicate requests coalesce', async () => {
  let finish
  let firstSignal
  const app = setup((city, offline, signal) => {
    if (city.id === petersburg.id) return Promise.resolve({ city: city.id })
    firstSignal = signal
    return new Promise(resolve => { finish = resolve })
  })
  const pending = app.load(moscow)
  await app.load(moscow)
  assert.equal(app.requests(), 1)
  await app.load(petersburg)
  assert.equal(firstSignal.aborted, true)
  finish({ city: moscow.id })
  await pending
  assert.equal(app.state().record.data.city, petersburg.id)
})

test('demo and live cache are separate', async () => {
  const app = setup()
  await app.load(moscow, { offline: true })
  await app.load(moscow)
  assert.equal(app.requests(), 2)
  await app.load(moscow, { offline: true })
  assert.equal(app.requests(), 2)
})
