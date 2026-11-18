export function filterItems(items, status, query) {
  const search = query.trim().toLocaleLowerCase('ru')
  return items.filter(item =>
    (status === 'all' || item.status === status) &&
    item.title.toLocaleLowerCase('ru').includes(search)
  )
}

export function createItem(items, title) {
  const cleanTitle = title.trim()
  if (!cleanTitle) throw new Error('Введите название задачи.')
  const id = items.reduce((max, item) => Math.max(max, item.id), 0) + 1
  return { id, title: cleanTitle, status: 'new', priority: 1 }
}

export function toggleItem(items, id) {
  return items.map(item => item.id === id
    ? { ...item, status: item.status === 'done' ? 'new' : 'done' }
    : item)
}

export function adaptItems(items) {
  return items.map(item => ({
    ...item,
    title: typeof item.title === 'string' && item.title.trim() ? item.title : 'Без названия',
    status: item.status ?? (item.active === true ? 'doing' : 'new'),
    priority: item.priority ?? 1
  }))
}
