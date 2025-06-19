<?php
/*$manufacturerNo = isset($_GET['manuf']) ? escapeshellarg($_GET['manuf']) : "'36115A4FFD9'";

// Run Node script
$command = "node scrap.js " . escapeshellarg($manufacturerNo);
exec($command);
// $descriptorSpec = [
    // 1 => ['pipe', 'w'], // stdout
    // 2 => ['pipe', 'w']  // stderr
// ];

// $process = proc_open($command, $descriptorSpec, $pipes);

// if (is_resource($process)) {
    // $stdout = stream_get_contents($pipes[1]);
    // $stderr = stream_get_contents($pipes[2]);

    // fclose($pipes[1]);
    // fclose($pipes[2]);

    // $returnCode = proc_close($process);

    // if ($returnCode !== 0) {
        // echo "Error (code $returnCode):\n" . $stderr;
    // } else {
        // echo "Success:\n" . $stdout;
    // }
// } else {
    // echo "Failed to start process.";
// }



// Read result
$result = file_get_contents("result.json");
header('Content-Type: application/json');
echo $result;*/
set_time_limit(0);
$manufacturerNos = ['36115A4FFD9', '82 72 2 287 886', '82 73 2 420 634', '36 13 2 461 758', '82 29 2 355 518', '51 16 2 462 825']; // Add more if needed

$command = 'node ../scrape.js ' . implode(' ', array_map('escapeshellarg', $manufacturerNos));
exec($command, $output, $code);
// $descriptorSpec = [
    // 1 => ['pipe', 'w'], // stdout
    // 2 => ['pipe', 'w']  // stderr
// ];
// $process = proc_open($command, $descriptorSpec, $pipes);

// if (is_resource($process)) {
    // $stdout = stream_get_contents($pipes[1]);
    // $stderr = stream_get_contents($pipes[2]);

    // fclose($pipes[1]);
    // fclose($pipes[2]);

    // $returnCode = proc_close($process);

    // if ($returnCode !== 0) {
        // echo "Error (code $returnCode):\n" . $stderr;
    // } else {
        // echo "Success:\n" . $stdout;
    // }
// } else {
    // echo "Failed to start process.";
// }
if ($code === 0 && !empty($output)) {
    $resultJson = implode("", $output);
    $data = '<pre>'.print_r(json_decode($resultJson, true), true).'</pre>';

    if (json_last_error() === JSON_ERROR_NONE) {
        echo $data;
    } else {
        echo "Invalid JSON returned.\n";
    }
} else {
    echo "Scraping failed. Check logs/failed.json for details.\n";
}

