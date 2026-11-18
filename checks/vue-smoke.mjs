import assert from 'node:assert/strict'
import { readFile } from 'node:fs/promises'
import { chromium } from 'playwright'

const work = process.argv[2]
const url = 'http://127.0.0.1:4173/'
const browser = await chromium.launch()
const page = await browser.newPage()
const errors = []
page.on('pageerror', error => errors.push(error.message))
page.on('console', message => {
  if (message.type() === 'error' || message.type() === 'warning') errors.push(message.text())
})
page.on('response', response => {
  if (response.url().startsWith(url) && response.status() >= 400) {
    errors.push(response.status() + ' ' + response.url())
  }
})

try {
  if (['pr12', 'pr13', 'pr14'].includes(work)) {
    await page.goto(url + (work === 'pr12' ? '' : '?state=options'))
    await page.getByRole('heading', { name: 'Задача 12', exact: true }).waitFor()
    await page.getByLabel('Статус').selectOption('done')
    await page.getByRole('heading', { name: 'Задача 2', exact: true }).waitFor()
    assert.equal(await page.getByRole('listitem').count(), 4)
    await page.getByLabel('Статус').selectOption('all')
    await page.getByLabel('Поиск по названию').fill('ЗАДАЧА 10')
    await page.getByRole('heading', { name: 'Задача 10', exact: true }).waitFor()
    assert.equal(await page.getByRole('listitem').count(), 1)
    await page.getByLabel('Поиск по названию').fill('')
    await page.getByRole('button', { name: 'Добавить' }).click()
    await page.getByRole('alert').waitFor()
    await page.getByLabel('Новая задача').fill('Новая проверка')
    await page.getByRole('button', { name: 'Добавить' }).click()
    const card = page.getByRole('article').filter({ hasText: 'Новая проверка' })
    await card.getByRole('button', { name: 'Отметить готовой' }).click()
    await card.getByRole('button', { name: 'Вернуть в новые' }).waitFor()
    await page.goto(url + '?state=loading')
    await page.getByText('Загружаем задачи…', { exact: true }).waitFor()
    await page.goto(url + '?state=empty')
    await page.getByText('Задачи не найдены.').waitFor()
    await page.goto(url + '?state=error')
    await page.getByText('Ошибка загрузки. Test error').waitFor()
    if (work === 'pr14') {
      await page.goto(url + '?state=broken')
      await page.getByRole('heading', { name: 'Без названия' }).first().waitFor()
      assert.equal(await page.getByRole('heading', { name: 'Без названия' }).count(), 2)
    }
  } else {
    let requests = 0
    const fixture = await readFile(new URL('../' + work + '/dataset/mock_forecast.json', import.meta.url), 'utf8')
    await page.route('https://api.open-meteo.com/**', async route => {
      requests++
      assert.equal(new URL(route.request().url()).searchParams.get('forecast_days'), '3')
      await route.fulfill({ contentType: 'application/json', headers: { 'access-control-allow-origin': '*' }, body: fixture })
    })
    await page.goto(url)
    await page.getByRole('button', { name: 'Загрузить прогноз' }).click()
    await page.getByText('Сначала выберите город.', { exact: true }).waitFor()
    const select = page.getByRole('combobox')
    async function choose(city) {
      await select.selectOption(city)
      await page.getByRole('heading', { name: city + ' · Сейчас' }).waitFor()
    }
    await choose('Москва')
    await page.getByText('12.4 °C', { exact: true }).waitFor()
    await choose('Казань')
    await choose('Москва')
    if (work === 'pr16') {
      assert.equal(requests, 2)
      await page.getByText(/Из кэша/).waitFor()
      await page.getByRole('button', { name: 'Обновить' }).click()
      await page.waitForFunction(() => {
        const refresh = [...document.querySelectorAll('button')].find(button => button.textContent.trim() === 'Обновить')
        return refresh && !refresh.disabled
      })
      assert.equal(requests, 3)
      await page.getByRole('button', { name: 'В избранное', exact: true }).click()
      await page.reload()
      await page.getByRole('button', { name: 'Москва', exact: true }).waitFor()
    }
    const before = requests
    await page.getByLabel('Деморежим без API').check()
    await choose('Казань')
    await page.getByText(/Демонстрационные данные/).waitFor()
    assert.equal(requests, before)
  }
  assert.deepEqual(errors, [])
  console.log(work + ': production browser checks passed')
} finally {
  await browser.close()
}
