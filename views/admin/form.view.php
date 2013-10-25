<?php
$form_id = uniqid('options_form');
?>
<?php echo View::forge('lib_options::admin/subviews/toolbar', $view_params + array('uniqid' => $form_id), false); ?>

<div id="<?= $form_id ?>" class="page">
    <div class="line">
        <div class="col c12">
            <h1 class="title" style="margin-bottom: 1em;"><?= $app_name.' options' ?></h1>
        </div>
    </div>
    <?php
    echo $fieldset->open($options['url_save']);
    echo $fieldset->build_hidden_fields();

    $layout = $options['config']['layout'];
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
                $form.nosTabs('update', <?=  \Format::forge($view_params['options']['config']['tab'])->to_json() ?>);
            }
            );
</script>