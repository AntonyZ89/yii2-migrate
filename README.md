yii2-migrate
============

<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=YATHVT293SXDL&source=url">
  <img src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" alt="Donate with PayPal" />
</a>

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist antonyz89/yii2-migrate dev-master
```

or add

```
"antonyz89/yii2-migrate": "dev-master"
```

to the require section of your `composer.json` file.


USAGE
---------
**console/config/main.php**

```
'controllerMap' => [
    'migrate' => [
        'class' => 'antonyz89\migrate\MigrateController'
    ],
],
```


MIGRATE COMMANDS
------------

`yii migrate/create`

`yii migrate/fresh`

`yii migrate/full` ( Fresh + Seeder ) ( requires [antonyz89/yii2-seeder](https://github.com/AntonyZ89/yii2-seeder) )
  
MIGRATION
-------------

It is no longer necessary to create index and foreign keys, columns that end with `_id` will have a foreign key associated with the name that comes before `_id`.

Example:

`company_id` column is a foreign key to the `company` table.

If this is not the case, add your column to the variable `$ignoreColumns` and these columns will not have indexing or foreign key automatically associated with them.

```php
public $ignoreColumns = [
    'company_id'
];
```

To disable association of indexing and foreign keys for all columns, simply assign `$autoGenerateIndexAndForeignKey = false`

```php
public $autoGenerateIndexAndForeignKey = true;
```

When a column is added individually (`addColumn()`), an index and a foreign key are also generated if the column follows the pattern mentioned above.
The procedures for disabling automatic association are the same

When a column is dropped (`dropColumn()`), if a foreign key exists, it is automatically removed.
If for some reason you don't want this to happen, just `public $autoDropForeignKey = false;` or add the column in `$ignoreColumns` to disable it for specific columns.

to create a index and foreign key use `indexAndForeignKey($column, $options = [])`

`$options` example:

```php
$options = [
    'table' => '{{%user}}',
    'ref_table' => 'company',
    'ref_table_id' => 'id',
    'delete' => 'CASCADE',
    'update' => 'CASCADE'
];

// in migrate
$this->indexAndForeignKey('company_id', $options);
```

To set global default value of `ON UPDATE` and `ON DELETE`, replace `Migration::$onDelete` and ` Migration::$onUpdate` in `bootstrap.php`

```php
use antonyz89\migrate\Migration;

Migration::$onDelete = 'RESTRICT'; // default: CASCADE
Migration::$onUpdate = 'CASCADE'; // default: CASCADE
```