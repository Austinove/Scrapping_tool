<?php
require_once __DIR__ . '/Logger.php';
require_once __DIR__ . '/EndpointClient.php';

class ScraperRunner
{
    private EndpointClient $client;
    private string $temp_file;
    private string $script_path;
    private string $response_log;

    public function __construct()
    {
        $this->client = new EndpointClient(
            'https://domain.com',
            'mySecret123',
            __DIR__ . '/../logs/retry_logs.json'
        );
        $this->temp_file = __DIR__ . '/temp_input.json';
        $this->script_path = realpath(__DIR__ . '/../scrape.js');
        $this->response_log = __DIR__ . '/../logs/response_logs.json';
    }

    public function run()
    {
        $start = microtime(true);
        $response_array = ['exec_time' => '---Execution Time Not set----'];

        $scrape_prod_result = $this->client->send(['type' => 'fetch_products']);
        // print_r($scrape_prod_result); exit;
        file_put_contents($this->temp_file, json_encode($scrape_prod_result));
        
        $command = 'node ' . escapeshellarg($this->script_path) . ' ' . escapeshellarg($this->temp_file);
        exec($command, $output, $code);

        if ($code === 0 && !empty($output)) {
            $result = json_decode(implode("", $output), true);
            if (json_last_error() === JSON_ERROR_NONE && !empty($result)) {
                $response_array['endpoint_save_scraped_products'] = $this->client->send([
                    'type' => 'save_products',
                    'products' => $result
                ]);
            } else {
                $response_array['error_in_scrping_json_data'] = "Invalid JSON returned.";
            }
        } else {
            $response_array['scraping_failure'] = "Scraping failed. Check logs/failed.json for details.";
        }

        // Calculate and echo the time taken
        $end = microtime(true);
        $executionTime = $end - $start;
        $response_array['exec_time'] = "---Execution Time:" . round($executionTime, 4) . " seconds";
        $response_array['log_timer'] = date('Y-m-d H:i:s');
        Logger::log($this->response_log, $response_array);
        
        // Kill the while loop process.
        if ($this->client->should_stop()) exit('Stopped by stopping file, in scraperRunner.php \n');
    }
}

/* BOC CMD response tester
$descriptorSpec = [
    1 => ['pipe', 'w'], // stdout
    2 => ['pipe', 'w']  // stderr
];
$process = proc_open($command, $descriptorSpec, $pipes);

if (is_resource($process)) {
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);

    fclose($pipes[1]);
    fclose($pipes[2]);

    $returnCode = proc_close($process);

    if ($returnCode !== 0) {
        echo "Error (code $returnCode):\n" . $stderr;
    } else {
        echo "Success:\n" . $stdout;
    }
} else {
    echo "Failed to start process.";
}
exit;
BOC CMD response tester*/