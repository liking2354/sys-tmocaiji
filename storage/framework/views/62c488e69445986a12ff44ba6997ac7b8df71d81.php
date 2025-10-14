<?php
    $statusConfig = [
        0 => ['class' => 'badge-secondary', 'icon' => '', 'text' => '未开始'],
        1 => ['class' => 'badge-warning', 'icon' => '<i class="fas fa-spinner fa-spin"></i> ', 'text' => '进行中'],
        2 => ['class' => 'badge-success', 'icon' => '', 'text' => '已完成'],
        3 => ['class' => 'badge-danger', 'icon' => '', 'text' => '失败'],
        4 => ['class' => 'badge-warning', 'icon' => '<i class="fas fa-clock"></i> ', 'text' => '超时'],
    ];
    
    $config = $statusConfig[$status] ?? ['class' => 'badge-secondary', 'icon' => '', 'text' => $statusText ?? '未知'];
?>

<span class="badge <?php echo e($config['class']); ?>">
    <?php echo $config['icon']; ?><?php echo e($statusText ?? $config['text']); ?>

</span><?php /**PATH /Users/tanli/Documents/php-code/sys-tmocaiji/resources/views/collection-tasks/partials/status-badge.blade.php ENDPATH**/ ?>