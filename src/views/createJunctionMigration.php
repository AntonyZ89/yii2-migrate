<?php
/**
 * This view is used by antonyz89/migrate/MigrateController.php.
 *
 * The following variables are available in this view:
 * @since 2.0.7
 * @deprecated since 2.0.8
 */
/* @var $className string the new migration class name without namespace */
/* @var $namespace string the new migration class namespace */
/* @var $table string the name table */
/* @var $field_first string the name field first */
/* @var $field_second string the name field second */

echo "<?php\n";
if (!empty($namespace)) {
    echo "\nnamespace {$namespace};\n";
}
?>

use antonyz89\migrate\Migration;

/**
 * Handles the creation of table `<?= $table ?>` which is a junction between
 * table `<?= $field_first ?>` and table `<?= $field_second ?>`.
 */
class <?= $className ?> extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('<?= $table ?>', [
            '<?= $field_first ?>_id' => $this->integer(),
            '<?= $field_second ?>_id' => $this->integer(),
            'PRIMARY KEY(<?= $field_first ?>_id, <?= $field_second ?>_id)',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('<?= $table ?>');
    }
}
