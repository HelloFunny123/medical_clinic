/* Real Chrome browser checks and submission screenshots.
 * NODE_PATH=/path/to/node_modules node tests/browser.cjs
 * Needs playwright and axe-core; application itself has no Node dependency.
 */
const { chromium } = require('playwright');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const ROOT = path.resolve(__dirname, '..');
const BASE = process.env.CLINIC_BASE_URL || 'http://127.0.0.1:8080';
const URL_PREFIX = new URL(BASE).pathname.replace(/\/$/,'');
const SHOTS = path.join(ROOT, 'report/screenshots');
const results = {date: new Intl.DateTimeFormat('en-CA', {timeZone:'Asia/Ho_Chi_Minh', year:'numeric',month:'2-digit',day:'2-digit'}).format(new Date()), browser:'Google Chrome', php:'XAMPP PHP 8.0.28', checks:[], responsive:[], accessibility:[], consoleErrors:[]};
let browser;
function check(value, message) { assert.ok(value, message); results.checks.push(message); console.log('PASS:', message); }
async function ready(page, url) {
  const response = await page.goto(BASE + url, {waitUntil:'networkidle'});
  await page.evaluate(async () => {
    await document.fonts.ready;
    document.querySelectorAll('img').forEach(image => { image.loading = 'eager'; });
    await Promise.all([...document.images].map(image => image.complete ? Promise.resolve() : new Promise(resolve => {image.onload = resolve; image.onerror = resolve;})));
  });
  return response;
}
async function snap(page, filename, fullPage=false) { await page.screenshot({path:path.join(SHOTS, filename),fullPage,animations:'disabled'}); }
(async () => {
  fs.mkdirSync(SHOTS, {recursive:true});
  browser = await chromium.launch({executablePath:process.env.CHROME_PATH || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',headless:true});
  const context = await browser.newContext({viewport:{width:1366,height:768},deviceScaleFactor:1,reducedMotion:'reduce'});
  // Deliberately block the CDN: the vendored copy must provide the entire layout offline.
  await context.route('https://cdn.jsdelivr.net/**', route => route.abort());
  const page = await context.newPage();
  page.on('pageerror', error => results.consoleErrors.push(error.message));
  check((await ready(page,'/public/index.php')).status() === 200,'Homepage responds with HTTP 200');
  check(await page.locator('#service-grid article.card').count() === 6,'Six medical service Bootstrap cards render');
  check(await page.locator('header nav,main,footer').count() === 3,'Semantic header navigation, main and footer exist');
  check(await page.locator('h1').count() === 1,'Homepage has one primary heading');
  check(await page.locator('img:not([alt]),img[alt=""]').count() === 0,'All images have meaningful alternative text');
  check(await page.evaluate(()=>[...document.images].every(image=>image.complete && image.naturalWidth>0)),'All local images load successfully');
  await snap(page,'02-homepage-desktop.png'); await snap(page,'02b-homepage-desktop-full.png',true);
  await page.locator('#services').scrollIntoViewIfNeeded();
  await page.evaluate(()=>window.scrollTo(0,document.querySelector('#services').offsetTop-95));
  await snap(page,'02c-catalog-desktop.png');
  await page.setViewportSize({width:1366,height:1366});
  await ready(page,'/public/index.php');
  await page.evaluate(()=>window.scrollTo(0,document.querySelector('#services').offsetTop-95));
  await snap(page,'02d-six-service-catalog.png');
  const sizes = [[320,700],[375,667],[390,844],[576,800],[768,1024],[820,1180],[991,900],[992,900],[1024,768],[1366,768],[1920,1080]];
  for (const [width,height] of sizes) {
    await page.setViewportSize({width,height});
    for (const route of ['/public/index.php','/pages/item_details.php?id=1','/pages/appointment.php?service=1']) {
      await ready(page,route);
      const geometry = await page.evaluate(()=>({viewport:innerWidth,document:document.documentElement.scrollWidth,body:document.body.scrollWidth,images:[...document.images].every(image=>image.getBoundingClientRect().right<=innerWidth+1 && image.getBoundingClientRect().left>=-1)}));
      check(geometry.document<=width && geometry.body<=width && geometry.images,`${width}×${height} ${route}: no horizontal overflow or overflowing images`);
      let columns = null;
      if (route === '/public/index.php') {
        const positions = await page.locator('#service-grid [data-service-card]').evaluateAll(cards=>cards.map(card=>Math.round(card.getBoundingClientRect().top)));
        columns = positions.filter(top=>Math.abs(top-positions[0])<2).length;
        check(columns === (width<768?1:width<992?2:3),`${width}px catalog has the required ${columns}-column Bootstrap grid`);
      }
      results.responsive.push({width,height,page:route,columns,noHorizontalScroll:true,imagesContained:true});
    }
  }
  await page.setViewportSize({width:375,height:667});
  await ready(page,'/public/index.php'); await snap(page,'03-homepage-mobile.png'); await snap(page,'03b-homepage-mobile-full.png',true);
  await page.locator('.navbar-toggler').click();
  check(await page.locator('#mainNavbar').isVisible(),'Mobile hamburger menu expands');
  check(await page.locator('.navbar-toggler').getAttribute('aria-expanded') === 'true','Mobile navigation updates aria-expanded');
  await snap(page,'03c-mobile-navigation.png');
  await page.locator('#mainNavbar [data-search-link]').click(); await page.waitForTimeout(400);
  check(await page.locator('#service-search').evaluate(el=>el===document.activeElement),'Search navigation focuses the search input');
  check(!(await page.locator('#mainNavbar').isVisible()),'Mobile menu collapses after navigation');
  await page.evaluate(()=>window.scrollTo(0,document.querySelector('#services').offsetTop-80)); await snap(page,'03d-mobile-catalog.png');
  await page.setViewportSize({width:768,height:1024}); await ready(page,'/public/index.php');
  await page.evaluate(()=>window.scrollTo(0,document.querySelector('#services').offsetTop-90)); await snap(page,'04-homepage-tablet.png');
  await page.setViewportSize({width:1366,height:768}); await ready(page,'/public/index.php#services');
  await page.locator('[data-filter="Primary care"]').click();
  check(await page.locator('#service-grid [data-service-card]:visible').count() === 2,'Category filter shows only two primary care services');
  await page.locator('#service-search').fill('preventive');
  check(await page.locator('#service-grid [data-service-card]:visible').count() === 1,'Search combines correctly with category filtering');
  await page.locator('#service-search').fill('nothingmatches');
  check(await page.locator('#empty-state').isVisible(),'An accessible empty state appears for no matches');
  await page.locator('#reset-filters').click();
  check(await page.locator('#service-grid [data-service-card]:visible').count() === 6,'Reset restores all six services');
  await page.locator('[data-filter="Primary care"]').click();
  await page.locator('[data-save-service="1"]').click();
  check(await page.locator('#feedback-toast').isVisible(),'Saving a service produces visible JavaScript feedback');
  check(await page.locator('[data-save-service="1"]').getAttribute('aria-pressed') === 'true','Saved state is exposed to assistive technology');
  await page.evaluate(()=>window.scrollTo(0,document.querySelector('#services').offsetTop-95)); await snap(page,'06-javascript-interaction.png');
  await page.reload({waitUntil:'networkidle'});
  check(await page.locator('[data-save-service="1"]').getAttribute('aria-pressed') === 'true','Saved service persists after page reload');
  await page.locator('#saved-filter').click(); check(await page.locator('#service-grid [data-service-card]:visible').count() === 1,'Saved-only filter works');
  await page.locator('[data-save-service="1"]').click(); check(await page.locator('#empty-state').isVisible(),'Removing the last saved service updates the empty state');
  for (let id=1;id<=6;id++) {
    check((await ready(page,`/pages/item_details.php?id=${id}`)).status()===200,`Service ${id} details are available`);
    check(await page.locator('#detail-title').innerText() !== '' && await page.locator('.detail-book').getAttribute('href') === `${URL_PREFIX}/pages/appointment.php?service=${id}`,`Service ${id} action preserves the selected service`);
  }
  for (const id of ['999','0','-1','1%20OR%201=1','%3Cscript%3E','1%26x','%5B%5D']) check((await ready(page,`/pages/item_details.php?id=${id}`)).status()===404,`Invalid service ID ${id} returns a helpful HTTP 404`);
  await ready(page,'/pages/item_details.php?id=1'); await snap(page,'05-item-details.png'); await snap(page,'05b-item-details-full.png',true);
  await page.setViewportSize({width:375,height:667}); await snap(page,'05c-item-details-mobile.png',true);
  await page.setViewportSize({width:1366,height:900}); await ready(page,'/pages/appointment.php?service=1');
  check(await page.locator('#service-id').inputValue()==='1','Appointment form preselects the detail-page service');
  const post = async overrides => page.evaluate(async values => {
    const form=document.getElementById('appointment-form'); const body=new FormData(form);
    Object.entries(values).forEach(([key,value])=>body.set(key,value));
    const response=await fetch(form.action,{method:'POST',body,headers:{Accept:'application/json'}});
    return {status:response.status,data:await response.json()};
  },overrides);
  let invalid = await post({csrf:'invalid'}); check(invalid.status===403,'Server rejects invalid CSRF tokens');
  invalid = await post({}); check(invalid.status===422 && !!invalid.data.errors.email,'Server validates a direct request without relying on JavaScript');
  const nextDate = new Date(); nextDate.setUTCDate(nextDate.getUTCDate()+2); while(nextDate.getUTCDay()===0)nextDate.setUTCDate(nextDate.getUTCDate()+1);
  const preferredDate=nextDate.toISOString().slice(0,10);
  await page.locator('#full-name').fill('Demo Student'); await page.locator('#email').fill('demo@example.com'); await page.locator('#preferred-date').fill(preferredDate); await page.locator('#preferred-time').selectOption('09:00'); await page.locator('#consent').check();
  await snap(page,'07-appointment-form.png');
  const submission=await page.locator('#appointment-form').evaluate(form=>Object.fromEntries(new FormData(form).entries()));
  await page.locator('#submit-request').click(); await page.locator('#booking-success:visible').waitFor();
  check(await page.locator('#confirmation-reference').innerText().then(value=>value.startsWith('WN-')),'A real server request produces a saved demo reference');
  check(await page.locator('#confirmation-price').innerText()==='250,000 ₫','Server confirmation price matches the catalog and detail page');
  const reference=await page.locator('#confirmation-reference').innerText();
  await snap(page,'07b-appointment-confirmation.png');
  const retry=await context.request.post(BASE+'/pages/appointment.php',{form:{...submission,quoted_price:'1'},headers:{Accept:'application/json'}});
  const retryResult=await retry.json(); check(retryResult.confirmation.reference===reference && Number(retryResult.confirmation.quoted_price)===250000,'HTTP retry is idempotent and ignores a client-supplied price');
  await page.reload({waitUntil:'networkidle'}); check(await page.locator('#booking-success').isVisible(),'Confirmation survives refresh without another submission');
  for (const resource of ['/storage/clinic.sqlite','/app/services.php','/database/schema.sql','/tests/backend.php']) check((await context.request.get(BASE+resource)).status()===403,`Private resource ${resource} is denied by the development router`);
  const noJs=await browser.newContext({javaScriptEnabled:false,viewport:{width:1366,height:768}}); const basic=await noJs.newPage();
  await basic.route('https://cdn.jsdelivr.net/**',route=>route.abort()); await basic.goto(BASE+'/pages/appointment.php?service=2');
  await basic.locator('#full-name').fill('No JS Demo'); await basic.locator('#email').fill('nojs@example.com'); await basic.locator('#preferred-date').fill(preferredDate); await basic.locator('#preferred-time').selectOption('10:00'); await basic.locator('#consent').check(); await basic.locator('#submit-request').click();
  check(await basic.locator('#booking-success').isVisible(),'Appointment submission works without JavaScript through POST/Redirect/GET'); await noJs.close();
  for (const width of [375,1366]) {
    await page.setViewportSize({width,height:width===375?667:768});
    for (const route of ['/public/index.php','/pages/item_details.php?id=1','/pages/appointment.php']) {
      await ready(page,route); await page.addScriptTag({path:require.resolve('axe-core/axe.min.js')});
      const audit = await page.evaluate(async()=>axe.run(document,{runOnly:{type:'tag',values:['wcag2a','wcag2aa','wcag21a','wcag21aa']}}));
      const violations=audit.violations.map(item=>({id:item.id,impact:item.impact,description:item.description,nodes:item.nodes.map(node=>({target:node.target,summary:node.failureSummary}))}));
      results.accessibility.push({width,page:route,violations,passes:audit.passes.length});
      check(violations.length===0,`${width}px ${route}: axe WCAG A/AA audit reports no violations`);
    }
  }
  check(results.consoleErrors.length===0,'No uncaught JavaScript errors occurred during browser testing');
  fs.writeFileSync(path.join(ROOT,'tests/results.json'),JSON.stringify(results,null,2));
  console.log(`\nAll ${results.checks.length} browser checks passed.`);
})().catch(error=>{console.error(error);results.error=error.message;fs.writeFileSync(path.join(ROOT,'tests/results.json'),JSON.stringify(results,null,2));process.exitCode=1;}).finally(async()=>{if(browser)await browser.close();});
