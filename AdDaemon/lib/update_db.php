#!/usr/bin/php
<?php

include_once('db.php');

$db = db_connect();

$backup = '/tmp/upgrade_backup.db';
echo "Creating $backup in case we hose the db.\n";
shell_exec("/usr/bin/sqlite3 $DBPATH '.save $backup'");

foreach($SCHEMA as $table_name => $table_schema) {
  $existing_schema = [];
  $existing_column_name_list = [];
  
  $res = db_all("pragma table_info( $table_name )");

  foreach($res as $row) {
    $existing_schema[] = "${row['name']} ${row['type']}";
    $existing_column_name_list[] = $row['name'];
  }

  $new_schema = implode (',', sql_kv($table_schema, '', ''));

  // This means we need to create the table
  if (count($existing_column_name_list) == 0) {
    echo "Creating the $table_name table.\n";
    $sql = "create table $table_name ( $new_schema )";
    echo $sql;
    $db->exec($sql);

  } else {

    // Otherwise we may need to add columns to the table
    $new_column_name_list = array_keys($table_schema);
    $column_to_add_list = array_diff($new_column_name_list, $existing_column_name_list);
    $shared_column_list = array_intersect($new_column_name_list, $existing_column_name_list);

    if(count($column_to_add_list)) {
      echo "Adding the following columns to $table_name:\n";
      echo "  " . implode(', ', $column_to_add_list) . "\n\n";

      $existing_schema_str = implode(',', $existing_schema);
      $existing_rows_str = implode(',', $existing_column_name_list);
      $shared_rows_str = implode(',', $shared_column_list);
 
      $add_column_sql = "
        DROP TABLE IF EXISTS my_backup;
        CREATE TEMPORARY TABLE my_backup($existing_schema_str);
        INSERT INTO my_backup SELECT $existing_rows_str FROM $table_name;
        DROP TABLE $table_name;
        CREATE TABLE $table_name($new_schema);
        INSERT INTO $table_name ($shared_rows_str) SELECT $shared_rows_str FROM my_backup;
        DROP TABLE my_backup;
      ";

      //echo $add_column_sql;
      $db->exec($add_column_sql);

      // If we added columns then we need to revisit our pragma
      $existing_column_name_list = get_column_list($table_name);
    }
    $column_to_remove_list = array_diff($existing_column_name_list, $new_column_name_list);

    // See if we need to remove any columns
    if (count($column_to_remove_list) > 0) {
      echo "Removing the following columns from $table_name:\n";
      echo "  " . implode(', ', $column_to_remove_list) . "\n\n";

      $our_schema = implode(',', sql_kv($table_schema, '', ''));
      $our_columns = implode(',', $new_column_name_list);

      $drop_column_sql = "
        DROP TABLE IF EXISTS my_backup;
        CREATE TEMPORARY TABLE my_backup($our_schema);
        INSERT INTO my_backup SELECT $our_columns FROM $table_name;
        DROP TABLE $table_name;
        CREATE TABLE $table_name($our_schema);
        INSERT INTO $table_name SELECT $our_columns FROM my_backup;
        DROP TABLE my_backup;
      ";

      //echo $drop_column_sql;
      $db->exec($drop_column_sql);
    }
  }
}
