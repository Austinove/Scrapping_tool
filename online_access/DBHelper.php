<?php
// include_once __DIR__ . '/Logger.php';
class DBHelper
{
  protected mysqli $conn;

  public function __construct(mysqli $conn)
  {
    $this->conn = $conn;
  }

  public function fetch(string $table, array $select, array $where_params = [], string $other_where_params = '', string $other_params = ''): array
  {
    // Validate SELECT fields
    if (empty($select)) {
      $query_error = 'SQL array for SELECT Query sent is empty. SELECT array cannot be empty.';
      //Throw an error
      // Logger::log('errors.json', [
        // 'file' => __FILE__,
        // 'Line' => 15
        // 'error' => $query_error,
      // ]);
      throw new InvalidArgumentException($query_error);
    }
    // SELECT clause
    $select_string = implode(', ', $select);
    // WHERE clause
    $where_string = $this->build_where_string($where_params, $other_where_params);
    // Build final SQL query
    $sql = "SELECT $select_string FROM $table $where_string $other_params";
    $query_prepare = $this->conn->prepare($sql);
    if (!$query_prepare) {
      $query_error = 'Prepare failed: ' . $this->conn->error;
      //throw an error
      // Logger::log('errors.json', [
        // 'file' => __FILE__,
        // 'Line' => 31
        // 'error' => $query_error,
      // ]);
      throw new Exception($query_error);
    }
    // Bind parameters
    if (is_array($where_params) && !empty($where_params)) {
      [$types, $values] = $this->build_type_values($where_params);
      $query_prepare->bind_param($types, ...$values);
    }
    // Execute and fetch
    $query_prepare->execute();
    $result = $query_prepare->get_result();
    if (!$result) {
      $query_error = 'Query failed: ' . $query_prepare->error;
      //throw an error
      // Logger::log('errors.json', [
        // 'file' => __FILE__,
        // 'Line' => 50
        // 'error' => $query_error,
      // ]);
      throw new RuntimeException($query_error);
    }
    return $result->fetch_all(MYSQLI_ASSOC);
  }

  public function insert(string $table, array $data): bool
  {
      $columns = implode(', ', array_keys($data));
      $placeholders = implode(', ', array_fill(0, count($data), '?'));
      $query_prepare = $this->conn->prepare("INSERT INTO $table ($columns) VALUES ($placeholders)");
      if (!$query_prepare) {
        $query_error = 'Prepare failed in Insert: ' . $this->conn->error;
        //throw an error
        // Logger::log('errors.json', [
          // 'file' => __FILE__,
          // 'Line' => 68
          // 'error' => $query_error,
        // ]);
        throw new Exception($query_error);
      }

      [$types, $values] = $this->build_type_values($data);
      $query_prepare->bind_param($types, ...$values);
      return $query_prepare->execute();
  }

  public function update(string $table, array $data, array $where_params): bool
  {
      if (empty($data)) {
        $query_error = 'Update data cannot be empty.';
        //throw an error
        // Logger::log('errors.json', [
          // 'file' => __FILE__,
          // 'Line' => 86
          // 'error' => $query_error,
        // ]);
        throw new InvalidArgumentException($query_error);
      }
      if (empty($where_params)) {
        $query_error = 'WHERE clause cannot be empty to prevent full-table updates.';
        //throw an error
        // Logger::log('errors.json', [
          // 'file' => __FILE__,
          // 'Line' => 96
          // 'error' => $query_error,
        // ]);
        throw new InvalidArgumentException($query_error);
      }
      $set_parts = [];
      foreach (array_keys($data) as $column) {
          $set_parts[] = "$column = ?";
      }
      $set_string = implode(', ', $set_parts);

      // WHERE clause
      $where_string = $this->build_where_string($where_params);

      $query_prepare = $this->conn->prepare("UPDATE $table SET $set_string $where_string");
      if (!$query_prepare) {
        $query_error = 'Prepare failed in Update: ' . $this->conn->error;
        //throw an error
        // Logger::log('errors.json', [
          // 'file' => __FILE__,
          // 'Line' => 115
          // 'error' => $query_error,
        // ]);
        throw new Exception($query_error);
      }

      [$data_types, $data_values] = $this->build_type_values($data);
      [$where_types, $where_values] = $this->build_type_values($where_params);
      $query_prepare->bind_param($data_types . $where_types, ...array_merge($data_values, $where_values));

      return $query_prepare->execute();
  }

  public function delete(string $table, array $where_params): bool
  {
      if (empty($where_params)) {
        $query_error = 'WHERE clause cannot be empty in deleting.';
        //throw an error
        // Logger::log('errors.json', [
          // 'file' => __FILE__,
          // 'Line' => 136
          // 'error' => $query_error,
        // ]);
        throw new InvalidArgumentException($query_error);
      }
      // WHERE clause
      $where_string = $this->build_where_string($where_params);
      $query_prepare = $this->conn->prepare("DELETE FROM $table $where_string");
      if (!$query_prepare) {
        $query_error = 'Prepare failed in Delete: ' . $this->conn->error;
        //throw an error
        // Logger::log('errors.json', [
          // 'file' => __FILE__,
          // 'Line' => 148
          // 'error' => $query_error,
        // ]);
        throw new Exception($query_error);
      }

      [$types, $values] = $this->build_type_values($where_params);
      $query_prepare->bind_param($types, ...$values);
      return $query_prepare->execute();
  }

  private function build_where_string(array $data, $other_where_params = ''): string
  {
      // WHERE clause
      $where_string = 'WHERE ';
      if ($other_where_params != '') {
          $where_string .=  $other_where_params . ' AND ';
      }
      $where_parts = [];
      if (is_array($data) && !empty($data)) {
          foreach (array_keys($data) as $column) {
              $where_parts[] = "$column = ?";
          }
          $where_string .= implode(' AND ', $where_parts);
      }
      return $where_string;
  }

  private function build_type_values(array $data): array
  {
      $types = '';
      $values = [];
      if (is_array($data) && !empty($data)) {
          foreach ($data as $value) {
              if (is_int($value)) {
                  $types .= 'i';
              } elseif (is_float($value)) {
                  $types .= 'd';
              } else {
                  $types .= 's';
              }
              $values[] = $value;
          }
      }

      return [$types, $values];
  }
}