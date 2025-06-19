const path = require('path');
const fs = require('fs');
const { chromium } = require('playwright-chromium');
const UserAgent = require('user-agents');

// Accept manufacturer numbers as command line arguments
// const products = JSON.parse(process.argv[2]);
// Get path to JSON file
const inputPath = process.argv[2];
if (!inputPath || !fs.existsSync(inputPath)) {
    console.error("Input file not found.");
    process.exit(1);
}

// Read and parse the JSON
const raw = fs.readFileSync(inputPath, 'utf8');
let products;
try {
    products = JSON.parse(raw);
} catch (e) {
    console.error("Failed to parse JSON:", e);
    process.exit(1);
}

// //generate a random chromium User-Agent
// const userAgent = new UserAgent(/chrome/).toString();

//async requests for scraping.
(async () => {
  // const browser = await chromium.launch();
  // //generate a random chromium User-Agent
  // const userAgent = new UserAgent().toString();
  
  // const context = await browser.newContext({
  //   userAgent,
  //   locale: 'de-DE'
  // });
  // const page = await context.newPage();

  // //Block unnecessary resources
  // await page.route('**/*', (route) => {
  //   const request = route.request();
  //   const resourceType = request.resourceType();

  //   if (['image', 'stylesheet', 'font', 'media'].includes(resourceType)) {
  //     route.abort();
  //   } else {
  //     route.continue();
  //   }
  // });
  const results = [];
  const failures = [];
  const notfound = [];

  for (const product of products) {
    const browser = await chromium.launch();
    //generate a random chromium User-Agent
    const userAgent = new UserAgent().toString();
    const context = await browser.newContext({
      userAgent,
      locale: 'de-DE'
    });
    const page = await context.newPage();

 
    //Block unnecessary resources
    await page.route('**/*', (route) => {
      const request = route.request();
      const resourceType = request.resourceType();

      if (['image', 'stylesheet', 'font', 'media'].includes(resourceType)) {
        route.abort();
      } else {
        route.continue();
      }
    });
    
    try {
      const new_manuf = product.manuf.replace(/\s+/g, '');
      const url = `https://www.domain.com?q=${new_manuf}`;
      await page.goto(url, { timeout: 10000, waitUntil: 'domcontentloaded' });

      //Adjust these selectors based on real site structure
      const raw_json = await page.$eval("script[type='application/ld+json']", el => el.textContent).catch(() => '');
      let page_Data = {};
      try {
        page_Data = JSON.parse(raw_json);
      } catch (e) {
        console.log('INVALID JSON:', e);
      }
      
      result_object = {
        prod_id: product.prod_id,
        products_price: product.products_price,
        products_model: product.products_model,
        manuf: product.manuf,
        name: page_Data.name ? page_Data.name : 'N/A',
        price: await page.$eval('div.flex.gap-2.text-xs > .line-through', el => el.innerText).catch(() => 'N/A'),
        offer_price: page_Data.offers ? page_Data.offers.price : 'N/A',
        delivery: await page.$eval('p.flex.items-center > span.ml-2.text-sm', el => el.innerText).catch(() => 'N/A'),
        discount_price: await page.$eval('div.flex.gap-2.text-xs > p.inline-block > span.bg-l24-black.px-1.text-white', el => el.innerText).catch(() => 'N/A'),
        available: 1,
        timestamp: new Date().toISOString()
      }
      // checking for many parameters not N/A
      // const keys_to_check = ['price'];
      // const all_not_null = keys_to_check.every(key => result_object[key] !== 'N/A');
      // if there are no offeres, we take the current price.
      if(result_object.price === 'N/A') {
        result_object.price = result_object.offer_price;
      }
      if(result_object.price !== 'N/A') {
        initial_price = parse_de_number(result_object.products_price);
        new_price = parse_de_number(result_object.price);
        if(new_price > initial_price) {
          result_object.new_price = new_price;
          results.push(result_object);
        }
      } else {
        //handle default/mandetory properties
        result_object.available = 0;
        result_object.new_price = 0;
        result_object.offer_price = 0;
        results.push(result_object);
        notfound.push(result_object);
      }
    } catch (err) {
      failures.push({
        prod_id: product.prod_id,
        manuf: product.manuf,
        error: err.message,
        timestamp: new Date().toISOString(),
      });
    }
 
    await browser.close();
    
    //make a random wait between 3-5 seconds
    await sleep((Math.random() * 5000) + 3000);
  }
  

  // Output notfound products to stdout for PHP
  record_to_file(notfound, '../logs/notfound.json')

  //Log failed ones to file
  record_to_file(failures, '../logs/failed.json');
  
  // return results
  console.log(JSON.stringify(results));
  process.exit(0);
  // await browser.close();
})();

//function to get number from German formart
const parse_de_number = (num_string) => {
  let num = num_string.includes(',') ? num_string.replace(/\./g, '').replace(',','.') : num_string;
  num = num.replace('€','');
  return parseFloat(num);
}

//save logs to files
const record_to_file = (required_data, file_path) => {
  let log_data = [];
  if (fs.existsSync(path.join(__dirname, file_path))) {
    try {
      log_data = JSON.parse(fs.readFileSync(file_path, 'utf8'));
    } catch (_) {
      log_data = [];
    }
  }
  log_data.push(...required_data);
  fs.writeFileSync(file_path, JSON.stringify(log_data, null, 2), 'utf-8');
}

//make a delay in requests
const sleep = (ms) => {
  return new Promise(resolve => setTimeout(resolve, ms));
}

