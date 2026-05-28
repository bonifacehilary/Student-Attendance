<?php
namespace StudentAttendance\Utils;

class Utility{

    public static function safeQuery($query, $params = [], $query_type = 'SELECT', $one_row = null)
    {
        $conn = $GLOBALS['db'] ?? null;
        if ($conn === null) {
            throw new \RuntimeException('Database connection not initialized. Ensure config/bootstrap.php is required before using Utility.');
        }

        try {
            if ($query_type == 'SELECT') {
                $result = $conn->executeQuery($query, $params);
            } else {
                $affectedRows = $conn->executeStatement($query, $params);
            }
        } catch (\Throwable $e) {
            error_log("Failed Query: $query. Error: " . $e->getMessage());
            throw new \Exception($e->getMessage());
        }

        if ($query_type == 'INSERT') {
            try {
                return $conn->lastInsertId();
            } catch (\Throwable $e) {
                return null;
            }
        } elseif ($query_type == 'SELECT') {
            return $one_row ? $result->fetchAssociative() : $result->fetchAllAssociative();
        } else {
            return true;
        }
    }

     /**
     *
     * Prepare data before inserting, the output of this function is expected to be a
     * clean insert statement. General assumption is that field names on post corresponds to
     * table columns on the database
     * @param string $table
     * @param array $post_array
     * @return array
     */
    public static function safePrepareInsertQuery(string $table, array $post_array)
    {
        $fields = [];
        $params = [];

        foreach ($post_array as $key => $value) {
            $value = strval($value);

            if ($value === '0' || (!empty($value) && $value !== 'null')) {
                $fields[] = "`$key` = ?";
                $params[] = $value;
            }
        }

        $fieldsStr = implode(', ', $fields);

        $query = "INSERT INTO `$table` SET $fieldsStr";

        return [
            'query' => $query,
            'params' => $params
        ];
    }

    public static function insert($table, $array_data)
    {
        $q = self::safePrepareInsertQuery($table, $array_data);
        return self::safeQuery($q['query'], $q['params'], 'INSERT');
    }

    /**
     * Insert multiple rows into a table
     * @param $table String Name of the table
     * @param $columns Array of columns to be inserted
     * @param $data Array of all rows to tbe inserted
     * @return bool
     * @throws JsonException
     */
    public static function bulkInsert($table, $columns, $data)
    {
        if (empty($data)) return true;

        $q = "INSERT INTO `{$table}` (" . implode(', ', $columns) . ') VALUES ';
        $rows = [];
        foreach ($data as $datum) {
            $escapedValues = array_map(function($value) {
                if ($value === null || $value === '') return 'NULL';
                if (is_numeric($value) && !is_string($value)) return $value;
                return "'" . addslashes((string)$value) . "'";
            }, array_values($datum));
            $rows[] = "(" . implode(', ', $escapedValues) . ")";
        }
        $q .= implode(', ', $rows);
        
        return self::safeQuery($q, [], 'INSERT');
    }


    /**
    * @param string $table
    * @param mixed $id
    * @param array $post_array
    * @param string $id_column
    * @return array
     */
    public static function safePrepareUpdateQuery($table, $id, $post_array, $id_column = 'id')
    {
        $fields = [];
        $params = [];

        foreach ($post_array as $key => $value) {

            if ($value !== null) {
                $fields[] = "`$key` = ?";
                $params[] = $value;
            } else {
                $fields[] = "`$key` = NULL";
            }
        }

        $setClause = implode(', ', $fields);

        $query = "UPDATE `$table` SET $setClause WHERE `$id_column` = ?";
        $params[] = $id;

        return [
            'query' => $query,
            'params' => $params
        ];
    }

    /**
     * Updates a a row with a given key-value pair
     * @param string $table
     * @param int $id
     * @param array $array_data
     * @return boolean
     * @throws '\Exception'?
     */
    public static function update($table, $id, $array_data)
    {
        $q = self::safePrepareUpdateQuery((string)$table, $id, $array_data);
        try {
            return self::safeQuery($q['query'], $q['params'], 'UPDATE');
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public static function delete($table, $id, $id_column_name = 'id')
    {
        $q = "DELETE FROM {$table} WHERE {$id_column_name} = ?";
        return self::safeQuery($q, [$id], 'DELETE');
    }

      /**
     * UI Notifications (SWAL)
     * @param $text
     * @param string $type
     * @param callable|null $title
     * @param int $buttons
     */
    public static function notify($text, $type = 'success', $title = null, $buttons = 1)
    {
        if ($title === null) {
            echo '<script>Swal.fire(' . json_encode((string)$text) . ');</script>';
            return;
        }

        $options = [
            'html' => (string)$text,
            'title' => (string)$title,
            'icon' => (string)$type,
            'showConfirmButton' => $buttons !== null ? true : false,
        ];

        if ($buttons === null) {
            $options['allowOutsideClick'] = true;
            $options['showConfirmButton'] = false;
        }

        echo '<script>Swal.fire(' . json_encode($options) . ');</script>';
    }

    public static function getLoggedInStudent(): ?array
    {
        if (!isset($_SESSION['student_id'])) {
            return null;
        }

        return [
            'id' => (int) $_SESSION['student_id'],
            'name' => $_SESSION['student_name'] ?? '',
        ];
    }

}