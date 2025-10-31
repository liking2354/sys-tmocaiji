@php
    $statusConfig = [
        0 => ['class' => 'bg-secondary', 'icon' => '', 'text' => '未开始'],
        1 => ['class' => 'bg-warning', 'icon' => '<i class="fas fa-spinner fa-spin"></i> ', 'text' => '进行中'],
        2 => ['class' => 'bg-success', 'icon' => '', 'text' => '已完成'],
        3 => ['class' => 'bg-danger', 'icon' => '', 'text' => '失败'],
        4 => ['class' => 'bg-warning', 'icon' => '<i class="fas fa-clock"></i> ', 'text' => '超时'],
    ];
    
    $config = $statusConfig[$status] ?? ['class' => 'bg-secondary', 'icon' => '', 'text' => $statusText ?? '未知'];
@endphp

<span class="badge {{ $config['class'] }}">
    {!! $config['icon'] !!}{{ $statusText ?? $config['text'] }}
</span>