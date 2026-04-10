import { test, expect } from '@playwright/test';

const baseURL = process.env.BASE_URL || 'http://127.0.0.1:8000';
const token = process.env.QA_TOKEN;
const studentName = `QA Peserta ${Date.now()}`;

async function assertNoPageErrors(page) {
  const body = await page.locator('body').innerText();
  expect(body).not.toContain('Not Found');
  expect(body).not.toContain('Server Error');
  expect(body).not.toContain('Internal Server Error');
}

async function loginAdmin(page) {
  await page.goto(`${baseURL}/admin/login`);
  await expect(page.getByText('Masuk ke dashboard')).toBeVisible();
  await page.locator('input[type="email"]').fill('admin@mbc.test');
  await page.locator('input[type="password"]').fill('password');
  await page.getByRole('button', { name: 'Masuk' }).click();
  await expect(page).toHaveURL(/\/admin$/);
  await expect(page.getByRole('heading', { name: 'Dashboard' })).toBeVisible();
}

async function expectNoBodyHorizontalOverflow(page) {
  const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
  expect(overflow).toBeLessThanOrEqual(2);
}

test.describe('MBC CBT smoke flow', () => {
  test('admin pages render on desktop and mobile', async ({ browser }) => {
    for (const viewport of [
      { width: 1440, height: 1000, name: 'desktop' },
      { width: 390, height: 844, name: 'mobile' },
    ]) {
      const page = await browser.newPage({ viewport });
      await loginAdmin(page);

      for (const path of ['/admin/exams', '/admin/questions', '/admin/tokens', '/admin/results', '/admin/guide']) {
        await page.goto(`${baseURL}${path}`);
        await page.waitForLoadState('networkidle');
        await assertNoPageErrors(page);
        await expectNoBodyHorizontalOverflow(page);
      }

      await page.goto(`${baseURL}/admin/results`);
      await page.getByRole('link', { name: 'Detail' }).first().click();
      await expect(page.getByText('Detail jawaban per soal')).toBeVisible();
      await assertNoPageErrors(page);
      await expectNoBodyHorizontalOverflow(page);

      await page.goto(`${baseURL}/admin/questions`);
      await page.getByRole('button', { name: 'Edit' }).first().click();
      await expect(page.getByText(/Edit soal|Input soal/)).toBeVisible();
      await page.screenshot({ path: `test-results/admin-questions-${viewport.name}.png`, fullPage: true });
      await page.close();
    }
  });

  test('student can answer non-A options and is blocked from submitting blanks', async ({ page }) => {
    test.skip(!token, 'QA_TOKEN is required');

    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto(`${baseURL}/ujian`);
    await expect(page.getByText('Masukkan token')).toBeVisible();
    await page.getByPlaceholder('XXXX-XXXX-XXXX').fill(token);
    await page.getByPlaceholder('Nama lengkap').fill(studentName);
    await page.getByPlaceholder('Kelas').fill('6');
    await page.getByPlaceholder('Nomor HP').fill('081234567890');
    await page.getByPlaceholder('Asal sekolah').fill('QA School');
    await page.getByRole('button', { name: 'Mulai ujian' }).click();
    await expect(page.getByText('Soal 1', { exact: true })).toBeVisible();

    await page.getByText('B. 60').click();
    await assertNoPageErrors(page);
    await page.getByRole('button', { name: 'Berikutnya' }).click();
    await expect(page.getByText('Soal 2')).toBeVisible();
    await page.getByText('C. 23').click();
    await assertNoPageErrors(page);
    page.once('dialog', async (dialog) => dialog.accept());
    await page.getByRole('button', { name: 'Submit ujian' }).click();
    await expect(page.getByText('Belum bisa submit')).toBeVisible();
    await expect(page.getByText(/Masih ada .* soal kosong/)).toBeVisible();
    await assertNoPageErrors(page);
    await page.screenshot({ path: 'test-results/student-submit-warning-mobile.png', fullPage: true });
  });
});
