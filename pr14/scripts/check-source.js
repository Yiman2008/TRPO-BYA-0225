import { execFileSync } from 'node:child_process'
import { readFileSync, readdirSync } from 'node:fs'

for (const file of readdirSync('src', { recursive: true })) {
  if (file.endsWith('.js')) execFileSync(process.execPath, ['--check', 'src/' + file], { stdio: 'inherit' })
  if (!file.endsWith('.vue')) continue
  const source = readFileSync('src/' + file, 'utf8')
  if (/\b(?:querySelector|innerHTML)\b|:key="(?:index|i)"|\bprops\.\w+\s*=(?!=)/.test(source)) {
    throw new Error('Нарушение правил шаблона или потока данных: ' + file)
  }
}
console.log('Статические проверки пройдены.')
