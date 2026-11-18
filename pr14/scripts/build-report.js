import { readdirSync, readFileSync } from 'node:fs'
import { gzipSync } from 'node:zlib'
for (const file of readdirSync('dist', { recursive: true }).filter(name => /\.(js|css|html)$/.test(name))) {
  const bytes = readFileSync('dist/' + file)
  console.log(file, bytes.length, 'bytes;', gzipSync(bytes).length, 'bytes gzip')
}
