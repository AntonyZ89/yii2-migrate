<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace antonyz89\migrate;

use yii\base\NotSupportedException;
use yii\console\ExitCode;
use antonyz89\seeder\SeederController;

/**
 * Class MigrateController
 * @package console\controllers
 *
 * @property boolean $skipConfirm
 */
class MigrateController extends \yii\console\controllers\MigrateController
{
    public $generatorTemplateFiles = [
        'create_table' => 'vendor/antonyz89/yii2-migrate/src/views/createTableMigration.php',
        'drop_table' => '@yii/views/dropTableMigration.php',
        'add_column' => '@yii/views/addColumnMigration.php',
        'drop_column' => '@yii/views/dropColumnMigration.php',
        'create_junction' => '@yii/views/createTableMigration.php',
    ];

    public $skipConfirm = false;

    /**
     * Run migrate/fresh && yii seeder
     *
     * @return int
     * @throws NotSupportedException
     */
    public function actionFull()
    {
        if (YII_ENV_PROD) {
            $this->stdout("YII_ENV is set to 'prod'.\nRefreshing migrations is not possible on production systems.\n");
            return ExitCode::OK;
        }

        $this->skipConfirm = true;

        $this->truncateDatabase();
        $this->actionUp();
        (new SeederController(null, null))->actionSeed();

        $this->skipConfirm = false;
        return ExitCode::OK;
    }

    public function confirm($message, $default = false)
    {
        if ($this->skipConfirm)
            return true;

        return parent::confirm($message, $default);
    }

}
