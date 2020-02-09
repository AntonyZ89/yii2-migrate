<?php

namespace antonyz89\migrate;

use Yii;
use yii\db\Migration as MigrationBase;

/**
 * Class Migration
 * @package console\migrations\base
 *
 * @property null|string $defaultTableOptions
 * @property boolean $autoGenerateIndexAndForeignKey
 * @property boolean $autoDropIndexAndForeignKey
 * @property array $ignoreColumns
 */
class Migration extends MigrationBase
{
    private $_table = null;
    public $autoGenerateIndexAndForeignKey = true;
    public $autoDropIndexAndForeignKey = true;
    public $ignoreColumns = [];

    private const ACTION_ADD = 1;
    private const ACTION_DROP = 2;

    private function checkColumn($table, $column, $action = self::ACTION_ADD)
    {
        if (preg_match("/_id$/", $column) && !$this->isIgnoringColumn($column))
            switch ($action) {
                case self::ACTION_ADD:
                    if ($this->autoGenerateIndexAndForeignKey)
                        $this->createIndexAndForeignKey($column);
                    break;
                case self::ACTION_DROP:
                    if ($this->autoDropIndexAndForeignKey && ($foreignKey = $this->getForeignKey($table, $column)))
                        $this->dropForeignKey($foreignKey->name, $table);
            }
    }

    public function getDefaultTableOptions()
    {
        $tableOptions = null;
        if ($this->db->driverName === 'mysql') {
            // http://stackoverflow.com/questions/766809/whats-the-difference-between-utf8-general-ci-and-utf8-unicode-ci
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
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
        $values = implode($values, "', '");
        return "ENUM ('$values')" . ($notNull ? ' NOT NULL' : '');
    }

    private function extractTableName($tableName)
    {
        return preg_replace("/{{%(.+)}}/", '$1', $tableName);
    }

    private function generateString($prefix, $only_table, $column)
    {
        return "$prefix-$only_table-$column";
    }

    /**
     * @inheritDoc
     */
    public function createTable($table, $columns, $options = null)
    {
        $this->_table = $table = $this->addPrefix($table);

        parent::createTable($table, $columns, $options !== null ? $options : $this->defaultTableOptions);

        foreach (array_keys($columns) as $column)
            $this->checkColumn($table, $column);
    }

    /**
     * @param $column
     * @param array $options
     *
     * options example:
     *
     * $options = [
     *   'table' => 'table_name'
     *   'ref_table' => 'reference_table_name'
     *   'ref_table_id' => 'reference_table_id'
     *   'delete' => 'CASCADE'
     *   'update' => 'CASCADE'
     * ]
     */
    public function createIndexAndForeignKey($column, $options = [])
    {
        $table = $this->_table;
        $ref_table = null;
        $ref_table_id = 'id';
        $delete = 'CASCADE';
        $update = 'CASCADE';

        foreach ($options as $option => $value)
            $$option = $value;

        if ($ref_table === null)
            $ref_table = preg_replace("/_id$/", '', $column);

        $ref_table = $this->addPrefix($ref_table);
        $table = $this->addPrefix($table);

        $only_table = $this->extractTableName($table);

        $this->createIndex(
            $this->generateString('idx', $only_table, $column),
            $this->_table,
            $column
        );
        $this->addForeignKey(
            $this->generateString('fk', $only_table, $column),
            $this->_table,
            $column,
            $ref_table,
            $ref_table_id,
            $delete,
            $update
        );
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
        return in_array($this->extractTableName($tableName), Yii::$app->db->schema->tableNames);
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
     * @return object|null
     */
    private function getForeignKey($table, $column)
    {
        $foreignKeys = Yii::$app->db->schema->getTableSchema($table)->foreignKeys;

        foreach ($foreignKeys as $fkName => $foreignKey) {
            $fkTableName = array_shift($foreignKey);
            foreach ($foreignKey as $fk => $fk_table_pk) {
                if ($fk === $column) {
                    return (object)[
                        'name' => $fkName,
                        'foreign_key' => $fk,
                        'reference_table' => $fkTableName,
                        'reference_table_id' => $fk_table_pk,
                    ];
                }
            }
        }
        return null;
    }

    /**
     * @param $column
     * @return bool
     */
    private function isIgnoringColumn($column)
    {
        return in_array($column, $this->ignoreColumns);
    }

    /**
     * @param string $table
     * @return string
     */
    private function addPrefix($table)
    {
        if (!preg_match("/^{{%.+}}$/", $table))
            return "{{%$table}}";

        return $table;
    }
}
