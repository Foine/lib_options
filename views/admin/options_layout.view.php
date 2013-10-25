<?php foreach ($lines as $line) : ?>
    <div class="line">
        <?php foreach ($line['cols'] as $col) : ?>
        <?php if (!empty($col['view'])) : ?>
        <div class="col c<?= $col['col_number'] ?>">
            <?php $params = $view_params + (isset($col['params']) ? $col['params'] : array()) + array('view_params' => $view_params);?>
            <?php echo View::forge($col['view'], $params, false); ?>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>
    </div>
<?php endforeach; ?>