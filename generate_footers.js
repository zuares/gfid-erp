const puppeteer = require('puppeteer');
const fs = require('fs');

(async () => {
  const browser = await puppeteer.launch();
  const page = await browser.newPage();
  
  // Set viewport large enough
  await page.setViewport({ width: 1200, height: 2000 });
  
  // Load the HTML file we created earlier
  await page.goto('http://127.0.0.1:8000/storage/footer_designs.html', { waitUntil: 'networkidle2' });
  
  // Get all footer elements
  const footers = await page.$$('.footer-canvas');
  
  for (let i = 0; i < footers.length; i++) {
    const footer = footers[i];
    // Take a screenshot of just this element
    await footer.screenshot({
      path: `storage/app/public/templates/footers/template_${i + 1}.png`
    });
    console.log(`Saved template_${i + 1}.png`);
  }
  
  await browser.close();
})();
