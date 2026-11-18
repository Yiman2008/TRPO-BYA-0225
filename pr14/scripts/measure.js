import { readFileSync } from 'node:fs'
import { filterItems } from '../src/domain.js'

const scenarios = JSON.parse(readFileSync(new URL('../dataset/performance_cases.json', import.meta.url)))
const report = []
for (const { case: scenario, items = 1000, switches = 1 } of scenarios) {
  const rows = Array.from({ length: items }, (_, index) => ({
    id: index, title: 'Задача ' + index, status: index % 2 ? 'new' : 'done'
  }))
  function measure(cached) {
    const samples = []
    let scans = 0
    for (let run = 0; run < 15; run++) {
      let checksum = 0
      const start = performance.now()
      for (let step = 0; step < switches; step++) {
        const status = step % 2 ? 'new' : 'done'
        const scan = () => {
          scans++
          return filterItems(rows, status, '')
        }
        const visible = scan()
        checksum += visible.length + (cached ? visible : scan()).length
      }
      if (checksum !== items * switches) throw new Error('Unexpected result')
      samples.push(performance.now() - start)
    }
    samples.sort((a, b) => a - b)
    return { scans: scans / samples.length, ms: Number(samples[7].toFixed(3)) }
  }
  report.push({ scenario, items, switches, before: measure(false), after: measure(true) })
}
console.log(JSON.stringify(report, null, 2))
