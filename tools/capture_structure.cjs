const fs = require('node:fs');
const path = require('node:path');
const {chromium} = require('playwright');
const root = path.resolve(__dirname,'..');
const expanded = new Set(['public','public/css','public/js','pages','components']);
function walk(relative='',prefix='') {
  const entries=fs.readdirSync(path.join(root,relative),{withFileTypes:true}).filter(e=>!e.name.startsWith('.') && e.name!=='submission').sort((a,b)=>(b.isDirectory()-a.isDirectory())||a.name.localeCompare(b.name));
  return entries.map((entry,index)=>{
    const last=index===entries.length-1;
    const item=path.posix.join(relative,entry.name);
    const line=`${prefix}${last?'└──':'├──'} ${entry.name}${entry.isDirectory()?'/':''}`;
    return [line,...(entry.isDirectory()&&expanded.has(item)?walk(item,prefix+(last?'    ':'│   ')):[])];
  }).flat();
}
(async()=>{
  const tree=['medical_clinic/',...walk()].join('\n');
  const browser=await chromium.launch({executablePath:process.env.CHROME_PATH||'/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',headless:true});
  const page=await browser.newPage({viewport:{width:1050,height:1130},deviceScaleFactor:1});
  await page.setContent(`<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Project folder structure</title><style>*{box-sizing:border-box}body{margin:0;padding:45px 60px;background:#f4f6f1;color:#183c34;font-family:Arial,sans-serif}.label{font-size:12px;letter-spacing:2px;color:#176b5b;margin-bottom:12px}h1{font-size:32px;margin:0 0 10px}p{font-size:13px;color:#61726a;margin:0 0 28px}.window{border:1px solid #dce5d8;border-radius:12px;overflow:hidden;background:white}.bar{background:#eaf0e6;border-bottom:1px solid #dce5d8;padding:16px 22px;font-size:13px;display:flex;justify-content:space-between}.tree{margin:0;padding:26px 34px;font:17px/1.65 'Andale Mono',monospace;white-space:pre-wrap}.foot{font-size:11px;margin-top:20px;color:#61726a}</style></head><body><div class="label">LAB 1.3 · PROJECT EXPLORER</div><h1>Website folder structure</h1><p>Actual project directory: /Applications/XAMPP/xamppfiles/htdocs/medical_clinic</p><div class="window"><div class="bar"><strong>medical_clinic</strong><span>PHP · Bootstrap · CSS · JavaScript</span></div><pre class="tree"></pre></div><div class="foot">Directory entries read directly from the project filesystem. Supporting folders are collapsed.</div></body></html>`);
  await page.locator('pre').evaluate((el,text)=>el.textContent=text,tree);
  const height=await page.evaluate(()=>document.documentElement.scrollHeight);
  await page.setViewportSize({width:1050,height});
  await page.screenshot({path:path.join(root,'report/screenshots/01-project-structure.png'),fullPage:true});
  fs.writeFileSync(path.join(root,'report/project-structure.txt'),tree+'\n');
  await browser.close(); console.log('Project folder screenshot captured from actual directory entries.');
})().catch(error=>{console.error(error);process.exitCode=1});
