<?php
/**
 * This view is used by antonyz89/migrate/MigrateController.php.
 *
 * The following variables are available in this view:
 */
/* @var $className string the new migration class name without namespace */
/* @var $namespace string the new migration class namespace */
/* @var $table string the name table */
/* @var $fields array the fields */

echo "<?php\n";
if (!empty($namespace)) {
    echo "\nnamespace {$namespace};\n";
}
?>

use antonyz89\migrate\Migration;

/**
 * Handles dropping columns from table `<?= $table ?>`.
<?= $this->render('@yii/views/_foreignTables', [
    'foreignKeys' => $foreignKeys,
]) ?>
 */
class <?= $className ?> extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
<?= $this->render('@yii/views/_dropColumns', [
    'table' => $table,
    'fields' => $fields,
    'foreignKeys' => $foreignKeys,
])
?>
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
<?= $this->render('@yii/views/_addColumns', [
    'table' => $table,
    'fields' => $fields,
    'foreignKeys' => $foreignKeys,
])
?>
    }
}
