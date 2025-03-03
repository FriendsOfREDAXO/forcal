<?php 
 $tables = [
        'forcal_entries',
        'forcal_categories',
        'forcal_venues',
        'forcal_user_categories',
        'forcal_user_media_permissions'
    ];
    
    foreach ($tables as $table) {
        $tableName = rex::getTable($table);
        rex_sql_table::get($tableName)->drop();
    }
?>
