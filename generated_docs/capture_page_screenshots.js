const fs = require("node:fs/promises");
const path = require("node:path");
const { chromium } = require("playwright");

const BASE_URL = "http://127.0.0.1:8000";
const OUTPUT_DIR = path.join(__dirname, "screenshots");
const EDGE_PATH = "C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe";

async function ensureDir(dir) {
  await fs.mkdir(dir, { recursive: true });
}

async function shot(page, name, options = {}) {
  const file = path.join(OUTPUT_DIR, `${name}.png`);
  await page.screenshot({
    path: file,
    fullPage: options.fullPage ?? true,
  });
  console.log(`saved ${file}`);
}

async function clickByText(page, selector, text) {
  const locator = page.locator(selector, { hasText: text }).first();
  await locator.click();
}

async function login(page, email, password) {
  await page.goto(`${BASE_URL}/auth/login.php`, { waitUntil: "networkidle" });
  await page.fill('input[name="email"]', email);
  await page.fill('input[name="password"]', password);
  await page.click('button[type="submit"]:has-text("Login")');
  await page.waitForLoadState("networkidle");
}

async function loginGuest(page) {
  await page.goto(`${BASE_URL}/auth/login.php`, { waitUntil: "networkidle" });
  await page.click('button[name="guest_login"]');
  await page.waitForLoadState("networkidle");
}

async function favoriteRecipe(page, recipeId) {
  await page.goto(`${BASE_URL}/resep/detail.php?id=${recipeId}`, { waitUntil: "networkidle" });
  const favoriteButton = page.locator('[data-social-action="favorite"]');
  const cls = await favoriteButton.getAttribute("class");
  if (!cls || !cls.includes("is-active")) {
    await favoriteButton.click();
    await page.waitForTimeout(500);
  }
}

async function main() {
  await ensureDir(OUTPUT_DIR);

  const browser = await chromium.launch({
    executablePath: EDGE_PATH,
    headless: true,
  });

  const guestContext = await browser.newContext({
    viewport: { width: 1440, height: 1200 },
  });
  const guestPage = await guestContext.newPage();

  await guestPage.goto(`${BASE_URL}/auth/login.php`, { waitUntil: "networkidle" });
  await shot(guestPage, "01-login");

  await guestPage.goto(`${BASE_URL}/auth/register.php`, { waitUntil: "networkidle" });
  await shot(guestPage, "02-register");

  await guestPage.goto(`${BASE_URL}/auth/lupa-sandi.php`, { waitUntil: "networkidle" });
  await shot(guestPage, "03-forgot-password");

  await loginGuest(guestPage);
  await shot(guestPage, "04-home-guest");

  await guestPage.goto(`${BASE_URL}/cari.php?q=salad&category=salad&difficulty=mudah&sort=popular`, { waitUntil: "networkidle" });
  await shot(guestPage, "05-search");

  await guestPage.goto(`${BASE_URL}/resep/detail.php?id=7`, { waitUntil: "networkidle" });
  await shot(guestPage, "06-detail-recipe");

  await guestContext.close();

  const userContext = await browser.newContext({
    viewport: { width: 1440, height: 1200 },
  });
  const userPage = await userContext.newPage();

  await login(userPage, "demo@demo.com", "123123123");
  await shot(userPage, "07-home-user");

  await userPage.goto(`${BASE_URL}/profil/`, { waitUntil: "networkidle" });
  await shot(userPage, "08-profile");

  await userPage.goto(`${BASE_URL}/profil/edit.php`, { waitUntil: "networkidle" });
  await shot(userPage, "09-profile-edit");

  await userPage.goto(`${BASE_URL}/resep/buat.php`, { waitUntil: "networkidle" });
  await shot(userPage, "10-add-recipe");

  await userPage.goto(`${BASE_URL}/resep/myresep.php`, { waitUntil: "networkidle" });
  await shot(userPage, "11-my-recipes");

  await favoriteRecipe(userPage, 7);
  await userPage.goto(`${BASE_URL}/resep/favorite.php`, { waitUntil: "networkidle" });
  await shot(userPage, "12-favorite");

  await userPage.goto(`${BASE_URL}/profil/laporan.php`, { waitUntil: "networkidle" });
  await shot(userPage, "13-my-reports");

  await userContext.close();

  const adminContext = await browser.newContext({
    viewport: { width: 1440, height: 1200 },
  });
  const adminPage = await adminContext.newPage();

  await login(adminPage, "resepku@gmail.com", "resepku123");
  await shot(adminPage, "14-admin-dashboard");

  await adminPage.goto(`${BASE_URL}/admin/pengguna.php`, { waitUntil: "networkidle" });
  await shot(adminPage, "15-admin-users");

  await adminPage.goto(`${BASE_URL}/admin/resep.php`, { waitUntil: "networkidle" });
  await shot(adminPage, "16-admin-recipes");

  await adminPage.goto(`${BASE_URL}/admin/laporan.php`, { waitUntil: "networkidle" });
  await shot(adminPage, "17-admin-reports");

  await adminContext.close();
  await browser.close();
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
