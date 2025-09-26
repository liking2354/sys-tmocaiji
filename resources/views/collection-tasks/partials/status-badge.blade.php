@switch($status)
    @case(0)
        <span class="badge badge-secondary">{{ $statusText }}</span>
        @break
    @case(1)
        <span class="badge badge-warning">
            <i class="fas fa-spinner fa-spin"></i> {{ $statusText }}
        </span>
        @break
    @case(2)
        <span class="badge badge-success">{{ $statusText }}</span>
        @break
    @case(3)
        <span class="badge badge-danger">{{ $statusText }}</span>
        @break
    @default
        <span class="badge badge-secondary">{{ $statusText ?? '未知' }}</span>
@endswitch