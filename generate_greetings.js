import puppeteer from 'puppeteer';

(async () => {
  const browser = await puppeteer.launch({ args: ['--no-sandbox'] });
  const page = await browser.newPage();
  
  // High viewport to accommodate 800x1200 canvas
  await page.setViewport({ width: 1400, height: 1600, deviceScaleFactor: 2 });
  
  // Use file:// protocol to load the local HTML file
  await page.goto('file:///Users/ariefmuhamad/Herd/gfid-dev/generate_greeting_cards.html', { waitUntil: 'networkidle0' });
  
  for (let i = 1; i <= 3; i++) {
    const greeting = await page.$(`#greeting-${i}`);
    if (greeting) {
        await greeting.screenshot({ path: `/Users/ariefmuhamad/Herd/gfid-dev/storage/app/public/templates/greetings/template_${i}.png` });
        console.log(`Generated template_${i}.png`);
    } else {
        console.log(`Could not find greeting-${i}`);
    }
  }
  
  await browser.close();
  console.log("Done generating greetings");
})();
