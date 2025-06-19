<?php
require_once __DIR__ . '/Logger.php';

class EndpointClient
{
  private string $url;
  private string $secret;
  private string $log_file;
  private array $retry_delays = [5, 10, 30, 60, 120, 300, 600];
  private int $max_attemps = 10;

  public function __construct(string $url, string $secret, string $log_file)
  {
    $this->url = $url;
    $this->secret = $secret;
    $this->log_file = $log_file;
  }

  public function send(array $data)
  {
    $attempt = 0;
    while (true) {
      $attempt++;
      try {
        $content = json_encode(['pass' => $this->secret, 'data' => $data]);
        $options = [
          'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST',
            'content' => $content
          ]
        ];
        $context = stream_context_create($options);
        $response = file_get_contents($this->url, false, $context);
// return $response;
        if ($response !== false) {
          return json_decode($response, true);
        } else {
          // return $response;
          // exit(1);
          $error = error_get_last()['message'] ?? 'Unknown Error';
          Logger::log($this->log_file, [
              'timestamp' => date('Y-m-d H:i:s'),
              'attempt' => $attempt,
              'status' => 'failed',
              'message' => 'Failed to reach endpoint',
              'error' => $error
          ]);
          throw new Exception("Failed to reach endpoint");
        }
      } catch (Exception $ex) {
        //Catch error and retry.
        if($attempt >= $this->max_attemps) {
          Logger::log($this->log_file, [
            'timestamp' => date('Y-m-d H:i:s'),
            'attempt' => $attempt,
            'status' => 'gave_up',
            'message' => 'Giving up after too many failures.',
            'error' => $ex->getMessage()
          ]);
          echo "Giving up after $attempt attempts.\n";
          exit(1);
        }

        $delay = $this->retry_delays[min($attempt - 1, count($this->retry_delays) - 1)];
        Logger::log($this->log_file, [
          'timestamp' => date('Y-m-d H:i:s'),
          'attempt' => $attempt,
          'status' => 'retrying',
          'message' => "Retrying in $delay seconds...",
          'error' => $ex->getMessage()
        ]);
        echo "[Retry $attempt] Waiting $delay seconds...\n";
        sleep($delay);
      }
      if ($this->should_stop()) exit('Stopped by stopping file in EndpointClient.php \n');
    }
  }

  public function should_stop(): bool
  {
    return file_exists(__DIR__ . '/../logs/stop_process.txt');
  }
}
