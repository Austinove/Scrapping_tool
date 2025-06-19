<?php
require_once __DIR__ . '/ProductRepository.php';
class ProductService
{
  private ProductRepository $product_repo;
  private array $products_data;
  public function __construct(array $data)
  {
    $this->product_repo = new ProductRepository();
    $this->products_data = $data;
  }
  public function get_products(): array
  {
    // file to store last seen ID
    $last_id_file = __DIR__ . '/logs/last_id.json';
    $last_id_array = file_exists($last_id_file) ? json_decode(file_get_contents($last_id_file), true) : [];
    $last_id = isset($last_id_array['last_seen_id']) ? (int)$last_id_array['last_seen_id'] : 0;

    //fetch products in the next 200 rows
    $scrape_prod_result = $this->product_repo->fetch_products("products_id > $last_id");
    
    //If no products fetched, fetch from the begining the next 200 rows.
    if(empty($scrape_prod_result)) {
      $scrape_prod_result = $this->product_repo->fetch_products();
    }
    
    //update the alst seen ID in last_id_file file
    if(!empty($scrape_prod_result)) {
      $last_id = end($scrape_prod_result)['prod_id'];
      file_put_contents($last_id_file, json_encode(['last_seen_id' => $last_id]));
    }
    return $scrape_prod_result;
  }

  public function save_scraped_products(): array
  {
    // save product prices to the Database.
    foreach ($this->products_data['products'] as $product_info) {
      $saved_products = [
        'products_manufacturers_model' => $product_info['manuf'],
        'new_price' => $product_info['new_price'],
        'old_price' => $product_info['products_price'],
        'special_price' => $product_info['offer_price'],
        'available' => $product_info['available'],
        'products_model' => $product_info['products_model'],
        'products_name_de' => $product_info['name'],
        'timestamp' => date('Y-m-d H:i:s')
      ];

      // save prices..........
      $this->product_repo->check_saved_products($product_info['products_model']) ?
      $this->product_repo->update_products($saved_products) : $this->product_repo->save_products($saved_products);
    }
    return ['status' => '200'];
  }
}