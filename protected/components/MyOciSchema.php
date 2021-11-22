<?php

class MyOciSchema extends COciSchema
{
    protected function findColumns($table)
    {
        $this->_createCache($table->schemaName, $table->name);

        $sql = <<<EOD
        SELECT column_name, data_type, nullable, data_default, key
        FROM stars.yii_cache_table_columns
        WHERE table_name = '{$table->name}' AND schema_name = '{$table->schemaName}'
        ORDER BY column_id
EOD;
        $command = $this->getDbConnection()->createCommand($sql);

        if (($columns = $command->queryAll()) === array()) {
            return false;
        }

        foreach ($columns as $column) {
            $c = $this->createColumn($column);

            $table->columns[$c->name] = $c;
            if ($c->isPrimaryKey) {
                if ($table->primaryKey === null) {
                    $table->primaryKey = $c->name;
                } else if (is_string($table->primaryKey)) {
                    $table->primaryKey = array($table->primaryKey, $c->name);
                } else {
                    $table->primaryKey[] = $c->name;
                }

                $table->sequenceName = '';
            }
        }

        return true;
    }

    protected function findConstraints($table)
    {
        $sql = <<<EOD
        SELECT column_name, table_ref, column_ref
        FROM stars.yii_cache_table_constraints
        WHERE table_name = '{$table->name}' AND schema_name = '{$table->schemaName}'
        ORDER BY position
EOD;
        $command = $this->getDbConnection()->createCommand($sql);

        foreach ($command->queryAll() as $row) {
            $name = $row["COLUMN_NAME"];
            $table->foreignKeys[$name] = array($row["TABLE_REF"], $row["COLUMN_REF"]);

            if (isset($table->columns[$name])) {
                $table->columns[$name]->isForeignKey = true;
            }
        }
    }

    protected function findTableNames($schema = '')
    {
        if ($schema === '') {
            $sql = "SELECT table_name, '' AS table_schema FROM stars.yii_cache_table_names";
            $command = $this->getDbConnection()->createCommand($sql);
        } else {
            $sql = <<<EOD
            SELECT table_name, schema_name AS table_schema
            FROM stars.yii_cache_table_names
            WHERE schema_name = :schema
EOD;
            $command = $this->getDbConnection()->createCommand($sql);
            $command->bindParam(':schema', $schema);
        }

        $rows = $command->queryAll();
        $names = array();

        foreach ($rows as $row) {
            $names[] = $row['TABLE_NAME'];
        }

        return $names;
    }

    private function _createCache($schemaName, $tableName)
    {
        $sql = <<<EOD
        SELECT COUNT(*)
        FROM stars.yii_cache_table_columns
        WHERE table_name = '{$tableName}' AND schema_name = '{$schemaName}'
EOD;
        $count = $this->getDbConnection()->createCommand($sql)->queryScalar();

        if ($count < 1) {
            $sql = "CALL stars.yii_cache_insert_columns(:p_schema_name, :p_table_name)";
            $command = $this->getDbConnection()->createCommand($sql);
            $command->bindParam(':p_schema_name', $schemaName, PDO::PARAM_STR);
            $command->bindParam(':p_table_name', $tableName, PDO::PARAM_STR);
            $command->execute();

            $sql = "CALL stars.yii_cache_insert_constraints(:p_schema_name, :p_table_name)";
            $command = $this->getDbConnection()->createCommand($sql);
            $command->bindParam(':p_schema_name', $schemaName, PDO::PARAM_STR);
            $command->bindParam(':p_table_name', $tableName, PDO::PARAM_STR);
            $command->execute();
        }
    }
}
