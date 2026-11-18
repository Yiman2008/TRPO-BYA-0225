import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import assert from 'node:assert/strict'
import { adaptItems, createItem, filterItems, toggleItem } from '../src/domain.js'

const read = name => JSON.parse(readFileSync(new URL('../dataset/' + name, import.meta.url)))
const source = read('items.json')

test('maps the active flag to catalog statuses and preserves IDs', () => {
  const items = adaptItems(source)
  assert.equal(items.length, 10)
  assert.equal(filterItems(items, 'doing', '').length, 5)
  assert.deepEqual(items.map(item => item.id), source.map(item => item.id))
})

for (const scenario of read('edge_cases.json')) {
  test(scenario.case, () => {
    const items = adaptItems(scenario.items)
    if (scenario.case === 'empty_list') assert.deepEqual(filterItems(items, 'all', ''), [])
    else assert.deepEqual(items[0], { id: 1, title: 'Без статуса', status: 'new', priority: 1 })
  })
}

test('ported actions preserve the previous catalog behavior', () => {
  const items = adaptItems(source)
  assert.equal(createItem(items, '  Новая  ').id, 11)
  assert.throws(() => createItem(items, ' '), /Введите/)
  assert.equal(toggleItem(items, 2)[1].status, 'done')
  assert.equal(items[1].status, 'doing')
})
