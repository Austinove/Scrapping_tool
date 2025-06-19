<?php
class Logger
{
  public static function log($file, $entry) {
    //create file if it doesnt exist.
    if(!file_exists($file)) {
      file_put_contents($file, json_encode([]));
    }

    //append logs to the file.
    $log_data = json_decode(file_get_contents($file), true) ?? [];
    $log_data[] = $entry;
    file_put_contents($file, json_encode($log_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
  }
}
