export function parseCities(csv) {
  return csv.trim().split(/\r?\n/).slice(1).map(line => {
    const [name, latitude, longitude, timezone] = line.split(';')
    return { id: name, name, latitude: Number(latitude), longitude: Number(longitude), timezone }
  })
}

export function forecastUrl(city, fields) {
  const url = new URL('https://api.open-meteo.com/v1/forecast')
  url.search = new URLSearchParams({
    latitude: city.latitude,
    longitude: city.longitude,
    timezone: city.timezone,
    current: fields.current.join(','),
    daily: fields.daily.join(','),
    forecast_days: '3',
    wind_speed_unit: 'kmh'
  })
  return url
}

export function normalizeForecast(payload, fields) {
  const current = payload?.current
  const daily = payload?.daily
  const dates = daily?.time
  if (!fields.current.every(field => Number.isFinite(current?.[field])) ||
      !Array.isArray(dates) || !dates.length || new Set(dates).size !== dates.length ||
      !dates.every(date => typeof date === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(date)) ||
      !fields.daily.every(field => Array.isArray(daily[field]) &&
        daily[field].length === dates.length && daily[field].every(Number.isFinite))) {
    throw new Error('Сервис вернул прогноз в неизвестном формате.')
  }
  const forecast = dates.map((date, index) => ({
    date,
    ...Object.fromEntries(fields.daily.map(field => [field, daily[field][index]]))
  }))
  return { current: Object.fromEntries(fields.current.map(field => [field, current[field]])), forecast }
}

export function describeCode(code, codes) {
  const label = codes[String(code)]
  if (!label) return { icon: '◌', label: 'Неизвестное состояние' }
  const icon = code < 2 ? '☀' : code === 2 ? '🌤' : code < 50 ? '☁' : '☂'
  return { icon, label }
}

export async function requestWeather(city, fields, { fetcher = fetch, signal } = {}) {
  let response
  try {
    response = await fetcher(forecastUrl(city, fields), { signal })
  } catch (cause) {
    if (signal?.aborted) throw cause
    throw new Error('Сеть недоступна. Проверьте подключение или включите деморежим.')
  }
  if (!response.ok) throw new Error('Сервис погоды вернул HTTP ' + response.status + '.')
  let payload
  try {
    payload = await response.json()
  } catch {
    throw new Error('Сервис вернул некорректный JSON.')
  }
  return normalizeForecast(payload, fields)
}
