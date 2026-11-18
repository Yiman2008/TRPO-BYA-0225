import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import assert from 'node:assert/strict'
import { describeCode, forecastUrl, normalizeForecast, parseCities, requestWeather } from '../src/weather.js'

const read = name => readFileSync(new URL('../dataset/' + name, import.meta.url), 'utf8')
const fields = JSON.parse(read('api_fields.json'))
const mock = JSON.parse(read('mock_forecast.json'))
const codes = JSON.parse(read('weather_codes.json'))
const cities = parseCities(read('cities.csv'))

test('request uses selected city and dataset fields', () => {
  const url = forecastUrl(cities[3], fields)
  assert.equal(url.searchParams.get('timezone'), cities[3].timezone)
  assert.equal(url.searchParams.get('latitude'), String(cities[3].latitude))
  assert.equal(url.searchParams.get('current'), fields.current.join(','))
  assert.equal(url.searchParams.get('daily'), fields.daily.join(','))
})

test('mock contains current conditions and dates with temperature and code', () => {
  const { current, forecast } = normalizeForecast(mock, fields)
  assert.equal(current.temperature_2m, 12.4)
  assert.equal(forecast.length, 3)
  assert.equal(describeCode(forecast[1].weather_code, codes).label, 'Дождь')
  assert.equal(describeCode(999, codes).label, 'Неизвестное состояние')
})

test('handles network, HTTP, and malformed responses', async () => {
  await assert.rejects(requestWeather(cities[0], fields, { fetcher: async () => { throw new TypeError() } }), /Сеть/)
  await assert.rejects(requestWeather(cities[0], fields, { fetcher: async () => ({ ok: false, status: 503 }) }), /503/)
  await assert.rejects(requestWeather(cities[0], fields, { fetcher: async () => ({
    ok: true, json: async () => ({ current: mock.current, daily: { time: ['2026-09-16'] } })
  }) }), /формате/)
  await assert.rejects(requestWeather(cities[0], fields, { fetcher: async () => ({
    ok: true, json: async () => { throw new SyntaxError() }
  }) }), /JSON/)
})
