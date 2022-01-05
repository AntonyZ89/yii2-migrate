<?php

namespace antonyz89\migrate;

use Yii;
use yii\db\Migration as MigrationBase;
use yii\helpers\ArrayHelper;

/**
 * Class Migration
 * @package console\migrations\base
 *
 * @property null|string $defaultTableOptions
 * @property boolean $autoGenerateIndexAndForeignKey
 * @property boolean $autoDropIndexAndForeignKey
 * @property array $ignoreColumns
 *
 * @property-read $table
 */
class Migration extends MigrationBase
{
    private $_table = null;
    public $autoGenerateIndexAndForeignKey = true;
    public $autoDropIndexAndForeignKey = true;
    public $ignoreColumns = [];

    public static $onDelete = 'CASCADE';
    public static $onUpdate = 'CASCADE';

    private const ACTION_ADD = 1;
    private const ACTION_DROP = 2;

    private function checkColumn($table, $column, $action = self::ACTION_ADD)
    {
        if (preg_match('/_id$/', $column) && !$this->isIgnoringColumn($column))
            switch ($action) {
                case self::ACTION_ADD:
                    if ($this->autoGenerateIndexAndForeignKey) {
                        $this->createIndexAndForeignKey($column);
                    }
                    break;
                case self::ACTION_DROP:
                    if ($this->autoDropIndexAndForeignKey) {
                        if (count(($foreignKeys = $this->getForeignKey($table, $column)))) {
                            foreach ($foreignKeys as $foreignKey) {
                                $this->dropForeignKey($foreignKey['name'], $table);
                            }
                        }
                        if (count(($indexes = $this->getIndexes($table, $column)))) {
                            foreach ($indexes as $index) {
                                $this->dropIndex($index['Key_name'], $table);
                            }
                        }
                    }
            }
    }

    public function getDefaultTableOptions()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }
        return $tableOptions;
    }

    /**
     * @param $values string[] with values
     * @param bool $notNull if or not "not null"
     * @return string
     */

    public function enum($values, $notNull = false)
    {
        $values = implode("', '", $values);
        return "ENUM ('$values')" . ($notNull ? ' NOT NULL' : '');
    }

    private function extractTableName($tableName)
    {
        return preg_replace('/{{%(.+)}}/', '$1', $tableName);
    }

    private function generateString($prefix, $table, $column)
    {
        $table = $this->extractTableName($table);

        return "$prefix-$table-$column";
    }

    /**
     * @inheritDoc
     */
    public function createTable($table, $columns, $options = null)
    {
        $this->_table = $table = $this->addPrefix($table);

        parent::createTable($table, $columns, $options ?? $this->defaultTableOptions);

        foreach (array_keys($columns) as $column) {
            $this->checkColumn($table, $column);
        }
    }

    /**
     * Creates a junction table
     * @param string $first_table
     * @param string $second_table
     * @param array $columns
     * @param string|null $options
     *@see createTable
     *
     */
    public function createJunction(string $first_table, string $second_table, array $columns = [], $options = null)
    {
        $table = "{{%{$first_table}_{$second_table}}}";

        $_columns = ArrayHelper::merge([
            "{$first_table}_id" => $this->integer(),
            "{$second_table}_id" => $this->integer(),

            "PRIMARY KEY({$first_table}_id, {$second_table}_id)",
        ], $columns);

        if (!isset($_columns['created_at'])) {
            $_columns['created_at'] = $this->integer()->notNull();
        }

        $this->createTable($table, $_columns, $options ?? $this->defaultTableOptions);
    }

    /**
     * @param $column
     * @param array $options
     *
     * options example:
     *
     * $options = [
     *   'table' => 'table_name',
     *   'ref_table' => 'reference_table_name',
     *   'ref_table_id' => 'reference_table_id',
     *   'delete' => 'CASCADE', `default: [[self::$onDelete]]`
     *   'update' => 'CASCADE', `default: [[self::$onUpdate]]`
     *   'unique' => false
     * ]
     */
    public function createIndexAndForeignKey($column, $options = [])
    {
        $table = $this->_table;
        $ref_table = null;
        $ref_table_id = 'id';
        $delete = self::$onDelete;
        $update = self::$onUpdate;
        $unique = false;

        foreach ($options as $option => $value) {
            $$option = $value;
        }

        $ref_table && ($ref_table = $this->addPrefix($ref_table));

        $table = $this->addPrefix($table);

        foreach ((array)$column as $col) {
            $_ref_table = $ref_table;

            if ($_ref_table === null) {
                $_ref_table = preg_replace("/_id$/", '', $col);
                $_ref_table = $this->addPrefix($_ref_table);
            }

            $this->createIndex(
                $this->generateString('idx', $table, $col),
                $table,
                $col,
                $unique
            );

            $this->addForeignKey(
                $this->generateString('fk', $table, $col),
                $table,
                $col,
                $_ref_table,
                $ref_table_id,
                $delete,
                $update
            );
        }
    }

    /**
     * @param string|string[] $column
     * @param string $table
     * @return void
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @throws Exception
     */
    public function dropIndexAndForeignKey($column, $table)
    {
        $table = $this->addPrefix($table);

        foreach ((array)$column as $col) {
            $this->dropForeignKey(
                $this->generateString('fk', $table, $col),
                $table
            );

            $this->dropIndex(
                $this->generateString('idx', $table, $col),
                $table
            );
        }
    }

    public function addColumn($table, $column, $type)
    {
        $this->_table = $table = $this->addPrefix($table);

        parent::addColumn($table, $column, $type);

        $this->checkColumn($table, $column);
    }

    public function dropColumn($table, $column)
    {
        $this->_table = $table = $this->addPrefix($table);

        $this->checkColumn($table, $column, self::ACTION_DROP);

        parent::dropColumn($table, $column);
    }

    public function tableExists($tableName)
    {
        return in_array($this->extractTableName($tableName), Yii::$app->db->schema->tableNames, true);
    }

    /**
     * @param $table
     * @param $column
     *
     * result example
     *
     *  return [
     *      'name' => 'fk-table_name-reference_id',
     *      'foreign_key' => 'reference_id',
     *      'reference_table' => 'reference',
     *      'reference_table_id' => 'id',
     *  ];
     * @return array
     */
    private function getForeignKey($table, $column)
    {
        $foreignKeys = Yii::$app->db->schema->getTableSchema($table)->foreignKeys;

        $results = [];

        foreach ($foreignKeys as $fkName => $foreignKey) {
            $fkTableName = array_shift($foreignKey);
            foreach ($foreignKey as $fk => $fk_table_pk) {
                if ($fk === $column) {
                    $results[] = [
                        'name' => $fkName,
                        'foreign_key' => $fk,
                        'reference_table' => $fkTableName,
                        'reference_table_id' => $fk_table_pk,
                    ];
                }
            }
        }
        return $results;
    }

    private function getIndexes($table, $column = null)
    {
        $indexes = [];
        $result = [];

        if ($this->db->driverName === 'mysql') {
            $indexes = Yii::$app->db->createCommand("SHOW INDEX FROM $table")->queryAll();
        }

        if ($column !== null) {
            foreach ($indexes as $index) {
                if ($index['Column_name'] === $column) {
                    $result[] = $index;
                }
            }

            return $result;
        }

        return $indexes;
    }

    /**
     * @param $column
     * @return bool
     */
    private function isIgnoringColumn($column)
    {
        return in_array($column, $this->ignoreColumns, true);
    }

    /**
     * @param string $table
     * @return string
     */
    private function addPrefix($table)
    {
        if (!preg_match('/^{{%.+}}$/', $table)) {
            return "{{%$table}}";
        }

        return $table;
    }

    /**
     * @return null|string
     */
    public function getTable()
    {
        return $this->_table;
    }
}
