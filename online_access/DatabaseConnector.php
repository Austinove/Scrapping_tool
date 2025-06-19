<?php
// include_once __DIR__ . '/Logger.php';
class DatabaseConnector
{
  private string $temp_db_table = 'db_name';
  public static function products_db_connector()
  {
    //fetch products from DB 
    require_once('');
    $sql_arr = $data20['test_shop'];

    if($sql_arr['sql_db'] !== 'db_name') {
      //Throw an error
      $query_error = 'Shop Database Not found.';
      // Logger::log('errors.json', [
        // 'file' => __FILE__,
        // 'Line' => 15
        // 'error' => $query_error,
      // ]);
      http_response_code(403); // Forbidden
      echo $query_error;
      exit;
    }
    $dbconnect = mysqli_connect($sql_arr['sql_server'], $sql_arr['sql_user'], $sql_arr['sql_pass'], $sql_arr['sql_db']);

    if (!$dbconnect) {
      //Throw an error
      $query_error = 'Shop Database Connection Failed: ' . mysqli_connect_error();
      // Logger::log('errors.json', [
        // 'file' => __FILE__,
        // 'Line' => 15
        // 'error' => $query_error,
      // ]);
      die($query_error);
    }
    return $dbconnect;
  }

  public function tempolary_db_connector(): array
  {
    $temp_sql_arr = [
      'sql_server' => 'localhost',
      'sql_user' => '',
      'sql_pass' => '$',
      'sql_db' => 'DB'
    ];
    $temp_dbconnect = mysqli_connect($temp_sql_arr['sql_server'], $temp_sql_arr['sql_user'], $temp_sql_arr['sql_pass'], $temp_sql_arr['sql_db']);

    if (!$temp_dbconnect) {
      //Throw an error
      $query_error = 'Connection to templary DB Failed: ' . mysqli_connect_error();
      // Logger::log('errors.json', [
        // 'file' => __FILE__,
        // 'Line' => 15
        // 'error' => $query_error,
      // ]);
      die($query_error);
    }
    return [
      'temp_db_conn' => $temp_dbconnect,
      'temp_db_table' => $this->temp_db_table
    ];
  }
}
