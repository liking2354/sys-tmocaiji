<!-- 分页组件 - 始终显示 -->
<div class="modern-pagination-wrapper">
    <!-- 分页信息 -->
    <div class="pagination-header">
        <div class="pagination-info">
            <span class="info-text">
                第 <strong>{{ $paginator->currentPage() }}</strong> 页，共 <strong>{{ $paginator->lastPage() }}</strong> 页 ({{ $paginator->total() }} 条)
            </span>
        </div>
        <div class="pagination-per-page">
            <label for="per-page-select">每页：</label>
            <select id="per-page-select" class="per-page-select" onchange="changePerPage(this.value)">
                <option value="10" {{ request('per_page') == 10 || !request('per_page') ? 'selected' : '' }}>10</option>
                <option value="20" {{ request('per_page') == 20 ? 'selected' : '' }}>20</option>
                <option value="30" {{ request('per_page') == 30 ? 'selected' : '' }}>30</option>
                <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
            </select>
        </div>
    </div>

    <!-- 分页导航 -->
    @if ($paginator->hasPages())
        <nav class="modern-pagination" role="navigation" aria-label="分页导航">
            <ul class="pagination-list">
                {{-- 首页链接 --}}
                @if ($paginator->onFirstPage())
                    <li class="pagination-item disabled">
                        <span class="pagination-link" aria-disabled="true">
                            <i class="fas fa-chevron-double-left"></i>
                            <span class="link-text">首页</span>
                        </span>
                    </li>
                @else
                    <li class="pagination-item">
                        <a class="pagination-link" href="{{ $paginator->url(1) }}" rel="first" aria-label="首页">
                            <i class="fas fa-chevron-double-left"></i>
                            <span class="link-text">首页</span>
                        </a>
                    </li>
                @endif

                {{-- 上一页链接 --}}
                @if ($paginator->onFirstPage())
                    <li class="pagination-item disabled">
                        <span class="pagination-link" aria-disabled="true">
                            <i class="fas fa-chevron-left"></i>
                            <span class="link-text">上一页</span>
                        </span>
                    </li>
                @else
                    <li class="pagination-item">
                        <a class="pagination-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="上一页">
                            <i class="fas fa-chevron-left"></i>
                            <span class="link-text">上一页</span>
                        </a>
                    </li>
                @endif

                {{-- 分页数字 --}}
                @foreach ($elements as $element)
                    {{-- "..." 分隔符 --}}
                    @if (is_string($element))
                        <li class="pagination-item disabled">
                            <span class="pagination-link pagination-dots">{{ $element }}</span>
                        </li>
                    @endif

                    {{-- 页码链接数组 --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <li class="pagination-item active">
                                    <span class="pagination-link" aria-current="page" aria-label="当前页，第 {{ $page }} 页">
                                        {{ $page }}
                                    </span>
                                </li>
                            @else
                                <li class="pagination-item">
                                    <a class="pagination-link" href="{{ $url }}" aria-label="第 {{ $page }} 页">
                                        {{ $page }}
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- 下一页链接 --}}
                @if ($paginator->hasMorePages())
                    <li class="pagination-item">
                        <a class="pagination-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="下一页">
                            <span class="link-text">下一页</span>
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                @else
                    <li class="pagination-item disabled">
                        <span class="pagination-link" aria-disabled="true">
                            <span class="link-text">下一页</span>
                            <i class="fas fa-chevron-right"></i>
                        </span>
                    </li>
                @endif

                {{-- 末页链接 --}}
                @if ($paginator->hasMorePages())
                    <li class="pagination-item">
                        <a class="pagination-link" href="{{ $paginator->url($paginator->lastPage()) }}" rel="last" aria-label="末页">
                            <span class="link-text">末页</span>
                            <i class="fas fa-chevron-double-right"></i>
                        </a>
                    </li>
                @else
                    <li class="pagination-item disabled">
                        <span class="pagination-link" aria-disabled="true">
                            <span class="link-text">末页</span>
                            <i class="fas fa-chevron-double-right"></i>
                        </span>
                    </li>
                @endif
            </ul>
        </nav>
    @endif

    <!-- 页码跳转 -->
    <div class="pagination-jump">
        <form id="jump-to-page-form" onsubmit="jumpToPage(event)">
            <label for="jump-page-input">跳转</label>
            <input type="number" id="jump-page-input" class="jump-page-input" min="1" max="{{ $paginator->lastPage() }}" placeholder="页">
            <button type="submit" class="btn-jump">GO</button>
        </form>
    </div>
</div>

<script>
    function changePerPage(perPage) {
        const url = new URL(window.location);
        url.searchParams.set('per_page', perPage);
        url.searchParams.set('page', 1); // 重置到第一页
        window.location.href = url.toString();
    }

    function jumpToPage(event) {
        event.preventDefault();
        const pageInput = document.getElementById('jump-page-input');
        const page = parseInt(pageInput.value);
        const maxPage = {{ $paginator->lastPage() }};

        if (isNaN(page) || page < 1 || page > maxPage) {
            alert('请输入有效的页码（1-' + maxPage + '）');
            return;
        }

        const url = new URL(window.location);
        url.searchParams.set('page', page);
        window.location.href = url.toString();
    }
</script>
