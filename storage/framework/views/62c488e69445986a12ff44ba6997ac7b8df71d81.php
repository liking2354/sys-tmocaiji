<?php switch($status):
    case (0): ?>
        <span class="badge badge-secondary"><?php echo e($statusText); ?></span>
        <?php break; ?>
    <?php case (1): ?>
        <span class="badge badge-warning">
            <i class="fas fa-spinner fa-spin"></i> <?php echo e($statusText); ?>

        </span>
        <?php break; ?>
    <?php case (2): ?>
        <span class="badge badge-success"><?php echo e($statusText); ?></span>
        <?php break; ?>
    <?php case (3): ?>
        <span class="badge badge-danger"><?php echo e($statusText); ?></span>
        <?php break; ?>
    <?php default: ?>
        <span class="badge badge-secondary"><?php echo e($statusText ?? '未知'); ?></span>
<?php endswitch; ?><?php /**PATH /Users/tanli/Documents/php-code/sys-tmocaiji/resources/views/collection-tasks/partials/status-badge.blade.php ENDPATH**/ ?>