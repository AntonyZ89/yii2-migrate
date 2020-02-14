<?php foreach ($fields as $field): ?>
        $this->addColumn('<?=
            $table
        ?>', '<?=
            $field['property']
        ?>', $this-><?=
            $field['decorators']
        ?>);
<?php endforeach;
