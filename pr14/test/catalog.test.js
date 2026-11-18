import { readFileSync } from 'node:fs'
import { test } from 'node:test'
import assert from 'node:assert/strict'
import { adaptItems, createItem, filterItems, toggleItem } from '../src/domain.js'

const previous = name => JSON.parse(readFileSync(new URL('../../pr13/dataset/' + name, import.meta.url)))
const read = name => JSON.parse(readFileSync(new URL('../dataset/' + name, import.meta.url)))
const source = previous('items.json')

test('maps the active flag to catalog statuses and preserves IDs', () => {
  const items = adaptItems(source)
  assert.equal(items.length, 10)
  assert.equal(filterItems(items, 'doing', '').length, 5)
  assert.deepEqual(items.map(item => item.id), source.map(item => item.id))
})

for (const scenario of previous('edge_cases.json')) {
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

const cases = {
  B01: () => assert.equal(filterItems(adaptItems(source), 'doing', '').length, 5),
  B02: () => {
    const original = Object.freeze({ ...adaptItems(source)[0] })
    assert.equal(toggleItem([original], original.id)[0].status, 'done')
    assert.equal(original.status, 'new')
  },
  B03: () => assert.deepEqual(filterItems(adaptItems(source), 'doing', '').map(row => row.id), [2, 4, 6, 8, 10]),
  B04: () => {
    const items = adaptItems(read('broken_items.json'))
    assert.deepEqual(filterItems(items, 'all', 'без названия').map(row => row.id), [2, 3])
  }
}
for (const { id, description } of read('bug_cases.json')) test(id + ' ' + description, cases[id])
