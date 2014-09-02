<?php
$form_id = uniqid('options_form');
?>
<?php echo View::forge('lib_options::admin/subviews/toolbar', $view_params + array('uniqid' => $form_id), false); ?>

<div id="<?= $form_id ?>" class="page">
    <div class="line">
        <div class="col c12">
            <h1 class="title" style="margin-bottom: 1em;"><?= $form_name ?></h1>
        </div>
    </div>
    <?php
    echo $fieldset->open($lib_options['url_save']);

    $has_restricted_fields = false;
    foreach ($fieldset->field() as $field) {
        if ($field->isRestricted()) {
            // Only use one <div> to wrap all restricted fields (instead of one per field)
            if (!$has_restricted_fields) {
                echo '<div style="display:none;">';
                $has_restricted_fields = true;
            }
            echo $field->set_template('{field}')->build();
            // We don't use the {description} placeholder, so build() should return an empty string
            $field->set_template('{description}');
        }
    }
    if ($has_restricted_fields) {
        echo '</div>';
    }

    echo $fieldset->build_hidden_fields();

    $layout = $lib_options['config']['layout'];
    foreach ($layout as $view) {
        if (!empty($view['view'])) {
            $view['params'] = empty($view['params']) ? array() : $view['params'];
            echo View::forge($view['view'], $view['params'] + $view_params, false);
        }
    }

    echo $fieldset->close();

    ?>
</div>

<script language="JAVAScript">
    require(
            [
                'jquery-nos'
            ],
            function($) {
                var $form = $('#<?= $form_id ?>');
                $form.nosFormAjax();
                $form.nosFormUI();
                $form.nosOnShow();
                $form.nosTabs('update', <?=  \Format::forge($view_params['lib_options']['config']['tab'])->to_json() ?>);
            }
            );
</script>

<?php //Common field plugin
echo \View::forge('crud/context_common_fields', array('container_id' => $form_id), false);