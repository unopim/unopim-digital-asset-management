async function login(page, config) {
  const baseURL = config?.projects?.[0]?.use?.baseURL || process.env.BASE_URL || 'http://127.0.0.1:8000';

  await page.goto(`${baseURL}/admin/login`, { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.getByRole('textbox', { name: 'Email Address' }).fill('admin@example.com');
  await page.getByRole('textbox', { name: 'Password' }).fill('admin123');
  await page.getByRole('button', { name: 'Sign In' }).click();
  await page.waitForURL('**/admin/**', { timeout: 60000 });
}

module.exports = { login };
