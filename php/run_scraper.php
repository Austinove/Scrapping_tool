<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
set_time_limit(0);
require_once __DIR__ . '/ScraperRunner.php';
$scraper = new ScraperRunner();
while (true) {
  echo print_r($scraper->run(), true);
  // Kill the while loop process.
  if (file_exists(__DIR__ . '/../logs/stop_process.txt')) exit('Stopped by stopping file in run_scraper.php \n');
}
/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
set_time_limit(0);
// while (true) {
  $start = microtime(true);
  $response_array = ['exec_time' => '---Execution Time Not set----'];
  $response_logs = __DIR__ . '/../logs/response_logs.json';
  $scrape_prod_result = contact_endpoint(['type' => 'fetch_products']);
  // var_dump($scrape_prod_result);
  // echo '<pre>'.print_r($scrape_prod_result, true).'</pre>';
  // var_dump(error_get_last());
  // exit;
  // Save to temporary file
  $tempFile = __DIR__ . '\temp_input.json';
  file_put_contents($tempFile, json_encode($scrape_prod_result));
  $scriptPath = realpath(__DIR__ . '/../scrape.js');
  $command = 'node ' . escapeshellarg($scriptPath) . ' ' . escapeshellarg($tempFile);
  exec($command, $output, $code);
  if ($code === 0 && !empty($output)) {
    $result_json = json_decode(implode("", $output));
    if (json_last_error() === JSON_ERROR_NONE && count($result_json) > 0) {
      //send scraped records to end point.
      $to_save_products = [
        'type' => 'save_products',
        'products' => $result_json
      ];
      $save_response = contact_endpoint($to_save_products);
      $response_array['endpoint_save_scraped_products'] = $save_response;
    } else {
      //for any JSON errors
      $response_array['error_in_scrping_json_data'] = "Invalid JSON returned.";
    }
  } else {
    //for errors in scrapping.
    $response_array['scraping_failure'] = "Scraping failed. Check logs/failed.json for details.";
  }

  // Calculate and echo the time taken
  $end = microtime(true);
  $executionTime = $end - $start;
  $response_array['exec_time'] = "---Execution Time:" . round($executionTime, 4) . " seconds";
  $response_array['log_timer'] = date('Y-m-d H:i:s');
  log_retries($response_logs, $response_array);
  
  // Kill the while loop process.
  if (file_exists(__DIR__ . '/../logs/stop_process.txt')) {
    echo "Stopping endpoint retries.\n";
    exit;
  }
// }

function contact_endpoint($data) {
  $url = 'https://teileabwerk.de/price_scraping/online_access.php';
  $data_content = ['pass' => 'mySecret123'];
  if(is_array($data) && !empty($data)) $data_content ['data'] = $data;
  $options = [
    'http' => [
      'header'  => "Content-Type: application/json\r\n",
      'method'  => 'POST',
      'content' => json_encode($data_content),
    ]
  ];

  $context = stream_context_create($options);
  $retry_delays = [5, 10, 30, 60, 120, 300, 600];
  $attempt = 0;
  $max_attemps = 10;
  $retry_log = __DIR__ . '/../logs/retry_logs.json';
  // Initialize log file if empty
  if (!file_exists($retry_log)) {
    file_put_contents($retry_log, json_encode([]));
  }

  while (true) {
    $attempt++;
    try {
      $response = @file_get_contents($url, false, $context);
      if($response !==false) {
        return json_decode($response, true);
      } else {
        //record response error.
        $error = error_get_last();
        $error_message = $error['message'] ?? 'Unknown Error';
        log_retries($retry_log, [
          'timestamp' => date('Y-m-d H:i:s'),
          'attempt' => $attempt,
          'status' => 'failes',
          'message' => 'Failed to reach endpoint',
          'error' => $error_message
        ]);

        throw new Exception("Failed to reach endpoint");
      }
    } catch (Exception $ex) {
      //Catch error and retry.
      if($attempt >= $max_attemps) {
        log_retries($retry_log, [
          'timestamp' => date('Y-m-d H:i:s'),
          'attempt' => $attempt,
          'status' => 'gave_up',
          'message' => 'Endpoint seems down. Gave up',
          'error' => $ex->getMessage()
        ]);
        echo "Giving up after $attempt attempts.\n";
        exit(1);
      }

      $delay = $retry_delays[min($attempt - 1, count($retry_delays) - 1)];
      log_retries($retry_log, [
        'timestamp' => date('Y-m-d H:i:s'),
        'attempt' => $attempt,
        'status' => 'retrying',
        'message' => "Retrying in $delay seconds...",
        'error' => $ex->getMessage()
      ]);
      echo "[Retry $attempt] Waiting $delay seconds...\n";
      sleep($delay);
    }

    // Kill the while loop process.
    if (file_exists(__DIR__ . '/../logs/stop_process.txt')) {
      echo "Stopping endpoint retries.\n";
      exit;
    }
  }
}

//log the entries made.
function log_retries($file, $entry) {
  $log_data = json_decode(file_get_contents($file), true);
  $log_data[] = $entry;
  file_put_contents($file, json_encode($log_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}*/