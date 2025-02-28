<?php

// Create user-category assignment table
rex_sql_table::get(rex::getTablePrefix() . 'forcal_user_categories')
    ->ensureColumn(new rex_sql_column('id', 'int(11) unsigned', false, null, 'auto_increment'))
    ->ensureColumn(new rex_sql_column('user_id', 'int(11)', false))
    ->ensureColumn(new rex_sql_column('category_id', 'int(11)', false))
    ->ensureColumn(new rex_sql_column('createdate', 'datetime', false, 'CURRENT_TIMESTAMP'))
    ->ensureColumn(new rex_sql_column('updatedate', 'datetime', false, 'CURRENT_TIMESTAMP', 'on update CURRENT_TIMESTAMP'))
    ->setPrimaryKey('id')
    ->ensure();
