import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import assert from 'node:assert/strict'
import { createItem, filterItems, toggleItem } from '../src/domain.js'

const read = name => JSON.parse(readFileSync(new URL('../dataset/' + name, import.meta.url)))
const items = read('items.json')

test('dataset search, filters and empty result', () => {
  assert.equal(filterItems(items, 'all', '').length, 12)
  assert.equal(filterItems(items, 'done', '').length, 4)
  assert.deepEqual(filterItems(items, 'doing', '  ЗАДАЧА 10 ').map(row => row.id), [10])
  assert.deepEqual(filterItems(items, 'all', 'несуществующая'), [])
})

test('adds a trimmed title with a unique ID and rejects a blank title', () => {
  assert.deepEqual(createItem(items, ' Новая '), { id: 13, title: 'Новая', status: 'new', priority: 1 })
  assert.throws(() => createItem(items, '  '), /Введите/)
})

test('updates state without changing the original record', () => {
  assert.equal(toggleItem(items, 1)[0].status, 'done')
  assert.equal(toggleItem(items, 2)[1].status, 'new')
  assert.equal(items[0].status, 'doing')
})

test('dataset defines all four UI scenarios', () => {
  assert.deepEqual(read('ui_states.json').map(item => item.state), ['loading', 'error', 'empty', 'success'])
})
