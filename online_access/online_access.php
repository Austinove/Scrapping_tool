<?php
include_once __DIR__ . '/AuthHandler.php';
include_once __DIR__ . '/ProductService.php';
// include_once __DIR__ . '/Logger.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$input = json_decode(file_get_contents('php://input'), true);
if(!is_array($input)){
  http_response_code(404);
  echo 'Request not supported.';
  exit;
}
//perform simple Auth
$auth_handler = new AuthHandler();
$is_authorized = $auth_handler->isAuthorized($input);
if (!$is_authorized) {
  http_response_code(404); // Method Not Allowed
  // Logger::log('errors.json', [
    // 'timestamp' => date('Y-m-d H:i:s'),
    // 'file' => __FILE__,
    // 'Line' => 16
    // 'error' => 'Request not supported.',
  // ]);
  echo 'Request not supported.';
  exit;
}


//get data from post
$input_data = $input['data'] ?? [];
$request_type = isset($input_data['type']) ? $input_data['type'] : '';

//instantiate product service
$product_service = new ProductService($input_data);

//handle requests
switch ($request_type) {
  case 'fetch_products':
    echo json_encode($product_service->get_products());
    break;
  case 'save_products':
    echo json_encode($product_service->save_scraped_products());
    break;
}
/*
// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405); // Method Not Allowed
  echo 'Request not supported.';
  exit;
}

// Retrieve and validate password
$correctPassword = 'mySecret123';
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['pass']) || $input['pass'] !== $correctPassword) {
  http_response_code(403); // Forbidden
  echo 'Access denied.';
  exit;
}
  
$input_data = $input['data'];
$request_type = isset($input_data['type']) ? $input_data['type'] : '';

//fetch products from DB 
require_once(dirname(__FILE__).'/../ah3.0/settings3.0/mysql_config.php');

$sql_arr = $data20['update1-ersatzteile'];

if($sql_arr['sql_db'] !== 'update1_maria_db') {
  http_response_code(403); // Forbidden
  echo 'Database Not found.';
  exit;
}
  
$scrape_dbconnect = mysqli_connect($sql_arr['sql_server'], $sql_arr['sql_user'], $sql_arr['sql_pass'], $sql_arr['sql_db']);

if (!$scrape_dbconnect) {
  die("Database Connection Failed: " . mysqli_connect_error());
}

switch ($request_type) {
  case 'fetch_products':
    // file to store last seen ID
    $last_id_file = __DIR__ . '/logs/last_id.json';
    $last_id_array = file_exists($last_id_file) ? json_decode(file_get_contents($last_id_file), true) : [];
    $last_id = isset($last_id_array['last_seen_id']) ? (int)$last_id_array['last_seen_id'] : 0;

    //fetch products in the next 200 rows
    // $scrape_prod_result = fetch_products($scrape_dbconnect, "WHERE prod_id > $last_id");
    $scrape_prod_result = fetch_products($scrape_dbconnect, "AND products_id > $last_id");
    
    //If no products fetched, fetch from the begining the next 200 rows.
    if(empty($scrape_prod_result)) {
      $scrape_prod_result = fetch_products($scrape_dbconnect, "");
    }
    
    //update the alst seen ID in last_id_file file
    if(!empty($scrape_prod_result)) {
      $last_id = end($scrape_prod_result)['prod_id'];
      file_put_contents($last_id_file, json_encode(['last_seen_id' => $last_id]));
    }
    $scrape_prod_result = [
      [
        "prod_id" => "1",
        "manuf" => "82 72 2 287 886",
        "products_price" => "99999.0000"
      ],
      [
        "prod_id" => "2",
        "manuf" => "82 73 2 420 634",
        "products_price" => "99.0000"
      ],
      [
        "prod_id" => "3",
        "manuf" => "36 13 2 461 758",
        "products_price" => "0.3300"
      ],
      [
        "prod_id" => "4",
        "manuf" => "82 29 2 355 518",
        "products_price" => "7.0000"
      ],
      [
        "prod_id" => "5",
        "manuf" => "51 16 2 462 825",
        "products_price" => "5.0000"
      ],
      [
        "prod_id" => "6",
        "manuf" => "16 11 2 472 988",
        "products_price" => "99999.0000"
      ],
      [
        "prod_id" => "7",
        "manuf" => "36 12 2 447 402",
        "products_price" => "23.0000"
      ],
      [
        "prod_id" => "8",
        "manuf" => "36 12 2 447 244",
        "products_price" => "42.0000"
      ],
      [
        "prod_id" => "9",
        "manuf" => "36 12 2 447 422",
        "products_price" => "66.0000"
      ],
      [
        "prod_id" => "10",
        "manuf" => "01001468389",
        "products_price" => "90.0000"
      ]
    ];
    echo json_encode($scrape_prod_result);
    exit;
    break;
  case 'save_products':
    if (!isset($input_data['products']) && is_array($input_data['products'])) {
      http_response_code(403); // Forbidden
      echo 'No products sent.';
      exit;
    }
    // Update product prices to the Database.
    $saved_products = [];
    foreach ($input_data['products'] as $product_info) {
      // update prices..........
      $saved_products[] = [
        'prod_id' => $product_info['prod_id'],
        'products_price' => $product_info['products_price'],
        'manuf' => $product_info['manuf'],
        'new_price' => $product_info['new_price'],
        'timestamp' => date('Y-m-d H:i:s')
      ];
    }
    
    // save logs
    $save_product_logs = __DIR__ . '/logs/saved_products.json';
    $log_data = json_decode(file_get_contents($save_product_logs), true);
    $log_data[] = $saved_products;
    file_put_contents($save_product_logs, json_encode($log_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    echo json_encode(['status' => '200']);
    exit;
    break;
}
function fetch_products($dbconnect, $where_param) {
  //fetch products in the next 200 rows
  $scrape_query = "SELECT products_id AS prod_id, 
                                 products_manufacturers_model AS manuf,
                                 products_price
                            FROM products  
                           WHERE products_status = 1
                            $where_param
                        ORDER BY products_id ASC
                           LIMIT 20";

  $scrape_prod_query = mysqli_query($dbconnect, $scrape_query);
  if (!$scrape_prod_query) {
      die('Query Error: ' . mysqli_error($dbconnect));
  }

  return mysqli_fetch_all($scrape_prod_query, MYSQLI_ASSOC);
}*/
 