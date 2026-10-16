/* Real WordPress admin/menu checks against the supplied baseline; temporary site only. */
const fs=require('fs'),path=require('path'),os=require('os'),assert=require('node:assert/strict');
const {spawn,execFileSync}=require('child_process'),{chromium}=require('playwright');
if(!process.env.WP_SOURCE||!process.env.PIRATEZ_MANAGER_SOURCE)throw Error('WP_SOURCE and PIRATEZ_MANAGER_SOURCE required');
const tmp=fs.mkdtempSync(path.join(os.tmpdir(),'piratez-cookie-menu-')),site=path.join(tmp,'site');
fs.cpSync(process.env.WP_SOURCE,site,{recursive:true});
for(const source of [path.resolve(__dirname,'..'),process.env.PIRATEZ_MANAGER_SOURCE]){
 execFileSync('python3',['tools/build_release.py'],{cwd:source});const cfg=JSON.parse(fs.readFileSync(path.join(source,'release-config.json')));execFileSync('python3',['-m','zipfile','-e',path.join(source,'dist',cfg.slug+'.zip'),path.join(site,'wp-content/plugins')]);
}
fs.mkdirSync(path.join(site,'wp-content/mu-plugins'),{recursive:true});
fs.writeFileSync(path.join(site,'wp-content/mu-plugins/offline.php'),`<?php add_filter('pre_http_request',function(){return new WP_Error('fixture_offline','Offline test');});`);
fs.writeFileSync(path.join(site,'menu-test.php'),`<?php require __DIR__.'/wp-load.php';require_once ABSPATH.'wp-admin/includes/plugin.php';header('Content-Type: application/json');$action=$_GET['action']??'';if($action==='activate-manager')activate_plugin('piratez-manager/piratez-manager.php');if($action==='deactivate-manager')deactivate_plugins('piratez-manager/piratez-manager.php');echo wp_json_encode(array('settings'=>get_option('piratez_cc_settings'),'wp'=>$GLOBALS['wp_version']));`);
const blueprint={steps:[{step:'defineWpConfigConsts',consts:{WP_DEBUG:true,WP_DEBUG_DISPLAY:true}},{step:'runPHP',code:"<?php require '/wordpress/wp-load.php';require_once ABSPATH.'wp-admin/includes/plugin.php';activate_plugin('piratez-cookie-consent/piratez-cookie-consent.php');"}]};
fs.writeFileSync(path.join(tmp,'blueprint.json'),JSON.stringify(blueprint));
const cli=path.join(path.dirname(require.resolve('@wp-playground/cli/package.json')),'wp-playground.js');
(async()=>{
 const server=spawn('node',[cli,'server','--php=8.3','--wordpress-install-mode=install-from-existing-files','--mount-before-install',site+':/wordpress','--blueprint',path.join(tmp,'blueprint.json'),'--login','--port=9411','--workers=1']);let logs='',browser;server.stdout.on('data',d=>logs+=d);server.stderr.on('data',d=>logs+=d);
 try{
  await new Promise((resolve,reject)=>{const deadline=Date.now()+45000,token=setInterval(()=>{if(logs.includes('Ready!')){clearInterval(token);resolve();}else if(Date.now()>deadline){clearInterval(token);reject(Error(logs));}},100);server.once('exit',()=>{clearInterval(token);reject(Error(logs));});});
  browser=await chromium.launch({headless:true,executablePath:process.env.PIRATEZ_CHROMIUM_EXECUTABLE,args:['--no-sandbox','--disable-dev-shm-usage']});
  const context=await browser.newContext(),page=await context.newPage(),base='http://127.0.0.1:9411',url=base+'/wp-admin/admin.php?page=piratez-cookie-consent';
  const state=async action=>{const response=await context.request.get(base+'/menu-test.php?'+new URLSearchParams({action}));return JSON.parse(await response.text());};
  await page.goto(url);if(page.url().includes('wp-login.php')){await page.locator('#user_login').fill('admin');await page.locator('#user_pass').fill('password');await Promise.all([page.waitForURL(u=>!u.href.includes('wp-login.php')),page.locator('#wp-submit').click()]);}
  assert.equal((await state('')).wp,'7.1.3');
  assert.equal(await page.locator('#toplevel_page_piratez-manager').count(),1);
  assert.equal(await page.locator('#menu-tools a[href*="piratez-cookie-consent"]').count(),0);
  assert.equal(await page.locator('#pcc-admin-css').count(),1);assert.equal(await page.locator('#pcc-admin-js').count(),1);
  await page.locator('input[name="piratez_cc_settings[popup_title]"]').fill('Cookie menu fixture');
  await page.locator('select[name="piratez_cc_settings[popup_style]"]').selectOption('modern');
  await Promise.all([page.waitForNavigation(),page.getByRole('button',{name:'Save Settings',exact:true}).click()]);
  assert.equal((await state('')).settings.popup_title,'Cookie menu fixture');
  await page.goto(base+'/wp-admin/tools.php?page=piratez-cookie-consent');assert.equal(page.url(),url);
  await page.goto(base+'/wp-admin/admin.php?page=piratez-manager');assert.equal(await page.locator('h1').innerText(),'PirateZ');
  await state('activate-manager');await page.goto(url);assert.equal(await page.locator('#toplevel_page_piratez-manager').count(),1);
  assert.equal(await page.locator('#toplevel_page_piratez-manager a').filter({hasText:/^Modules$/}).count(),1);
  await page.goto(base+'/wp-admin/admin.php?page=piratez-manager');assert.equal(await page.locator('h1').innerText(),'PirateZ Manager');assert.equal(await page.locator('#pcc-admin-css').count(),0);
  await state('deactivate-manager');await page.goto(url);assert.equal(await page.locator('#toplevel_page_piratez-manager').count(),1);assert.equal((await state('')).settings.popup_style,'modern');
  // Frontend consent behavior is unchanged by the admin menu move.
  const front=await browser.newContext(),frontPage=await front.newPage();await frontPage.goto(base+'/');
  await frontPage.waitForFunction(()=>!!window.pccConsent);assert.deepEqual(await frontPage.evaluate(()=>window.pccConsent.current()),{analytics:false,ads:false});
  await frontPage.locator('[data-pcc-action="accept-all"]').click();assert.deepEqual(await frontPage.evaluate(()=>window.pccConsent.current()),{analytics:true,ads:true});
  await frontPage.reload();assert.deepEqual(await frontPage.evaluate(()=>window.pccConsent.current()),{analytics:true,ads:true});await front.close();
  console.log('PASS: WordPress 7.1.3; standalone PirateZ menu; Manager reuse/deactivation; legacy redirects; settings save; screen-only assets; frontend consent unchanged.');
 }finally{if(browser)await browser.close();server.kill('SIGTERM');setTimeout(()=>server.kill('SIGKILL'),1000).unref();}
})().catch(error=>{console.error(error);process.exitCode=1;});
