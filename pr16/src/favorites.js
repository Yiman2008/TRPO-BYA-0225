const FAVORITES_KEY = 'trpo.weather.favorites.v1'

export function readFavorites(storage, cities) {
  try {
    const stored = JSON.parse(storage.getItem(FAVORITES_KEY) ?? '[]')
    if (!Array.isArray(stored)) return []
    const valid = new Set(cities.map(city => city.id))
    return [...new Set(stored.filter(id => valid.has(id)))]
  } catch {
    return []
  }
}

export function writeFavorites(storage, ids) {
  try {
    storage.setItem(FAVORITES_KEY, JSON.stringify(ids))
    return true
  } catch {
    return false
  }
}

export function toggleFavorite(ids, id) {
  return ids.includes(id) ? ids.filter(saved => saved !== id) : [...ids, id]
}
