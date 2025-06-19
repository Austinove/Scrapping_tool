<?php
require_once __DIR__ . '/DatabaseConnector.php';
require_once __DIR__ . '/DBHelper.php';
class ProductRepository
{
  private DBHelper $prod_db_con;
  private string $table_name;
  private DBHelper $temp_db_con;
  public function __construct()
  {
    $dbs_connector = new DatabaseConnector();
    $this->prod_db_con = new DBHelper($dbs_connector->products_db_connector());
    $temp_db = $dbs_connector->tempolary_db_connector();
    $this->temp_db_con = new DBHelper($temp_db['temp_db_conn']);
    $this->table_name = $temp_db['temp_db_table'];
  }
  public function fetch_products(string $other_where_clause = ''): array
  {
    //fetch products in the next 200 rows
    $select_array = [
      'products_id AS prod_id', 
      'products_manufacturers_model AS manuf',
      'products_price',
      'products_model'
    ];
    $where_array = ['products_status' => 1];
    $additional_statements = 'ORDER BY products_id ASC LIMIT 100';
    return $this->prod_db_con->fetch('products', $select_array, $where_array, $other_where_clause, $additional_statements);
  }

  public function save_products(array $product): bool 
  {
    //save products
    return $this->temp_db_con->insert($this->table_name, $product);
  }

  public function update_products(array $product): bool 
  {
    $where_array = [
      'products_model' => $product['products_model']
    ];
    //update products
    return $this->temp_db_con->update($this->table_name, $product, $where_array);
  }

  public function check_saved_products(string $model_number): bool
  {
    $select_array = ['scraper_id'];
    $where_array = ['products_model' => $model_number];
    $row = $this->temp_db_con->fetch($this->table_name, $select_array, $where_array);
    return !empty($row);
  }
}