/**
 * 数据清理模块
 * 功能：数据清理、批量操作、选择管理
 * 
 * 设计原则：
 * 1. 不使用 $(document).on() 事件委托，避免事件冒泡到 document 级别
 * 2. 直接在元素上绑定事件，保证事件隔离
 * 3. 防止与导航栏事件处理器冲突
 */

$(document).ready(function() {
    // 初始化计数
    updateServerCount();
    updateCollectorCount();
    updateDateRange();
    
    // 服务器全选/取消全选
    $('#selectAllServers').change(function() {
        $('.server-checkbox').prop('checked', $(this).prop('checked'));
        updateServerCount();
    });
    
    // 采集组件全选/取消全选
    $('#selectAllCollectors').change(function() {
        $('.collector-checkbox').prop('checked', $(this).prop('checked'));
        updateCollectorCount();
    });
    
    // 单个服务器复选框变化事件 - 直接在元素上绑定
    document.querySelectorAll('.server-checkbox').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            updateServerCount();
            updateServerSelectAllState();
        });
    });
    
    // 单个采集组件复选框变化事件 - 直接在元素上绑定
    document.querySelectorAll('.collector-checkbox').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            updateCollectorCount();
            updateCollectorSelectAllState();
        });
    });
    
    // 服务器组展开/收起功能
    $('.toggle-group-servers').click(function() {
        var groupId = $(this).data('group-id');
        var container = $('#group-servers-' + groupId);
        var icon = $(this).find('i');
        
        if (container.is(':visible')) {
            container.hide();
            icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
        } else {
            container.show();
            icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
        }
    });
    
    // 清理按钮点击事件
    $('#cleanupBtn').click(function() {
        if ($('.server-checkbox:checked').length === 0) {
            alert('请至少选择一个服务器');
            return;
        }
        
        if ($('.collector-checkbox:checked').length === 0) {
            alert('请至少选择一个采集组件');
            return;
        }
        
        updateServerCount();
        updateCollectorCount();
        updateDateRange();
        $('#confirmModal').modal('show');
    });
    
    // 确认清理按钮点击事件
    $('#confirmCleanupBtn').click(function() {
        $('#cleanupForm').submit();
    });
    
    // 初始化数据存储占用图表
    initializeStorageChart();
    
    // 日期输入框变化事件 - 直接在元素上绑定
    var startDateInput = document.getElementById('start_date');
    var endDateInput = document.getElementById('end_date');
    
    if (startDateInput) {
        startDateInput.addEventListener('change', function() {
            updateDateRange();
        });
    }
    
    if (endDateInput) {
        endDateInput.addEventListener('change', function() {
            updateDateRange();
        });
    }
});

/**
 * 更新服务器全选状态
 */
function updateServerSelectAllState() {
    var allChecked = $('.server-checkbox').length > 0 && 
                     $('.server-checkbox').length === $('.server-checkbox:checked').length;
    $('#selectAllServers').prop('checked', allChecked);
}

/**
 * 更新采集组件全选状态
 */
function updateCollectorSelectAllState() {
    var allChecked = $('.collector-checkbox').length > 0 && 
                     $('.collector-checkbox').length === $('.collector-checkbox:checked').length;
    $('#selectAllCollectors').prop('checked', allChecked);
}

/**
 * 更新服务器计数
 */
function updateServerCount() {
    var count = $('.server-checkbox:checked').length;
    $('.server-count').text(count + ' 已选择');
    $('#serverCount').text(count);
}

/**
 * 更新采集组件计数
 */
function updateCollectorCount() {
    var count = $('.collector-checkbox:checked').length;
    $('.collector-count').text(count + ' 已选择');
    $('#collectorCount').text(count);
}

/**
 * 更新日期范围显示
 */
function updateDateRange() {
    var startDate = $('#start_date').val();
    var endDate = $('#end_date').val();
    
    if (startDate && endDate) {
        $('#dateRange').text(startDate + ' 至 ' + endDate);
    } else if (startDate) {
        $('#dateRange').text(startDate + ' 至 现在');
    } else if (endDate) {
        $('#dateRange').text('全部 至 ' + endDate);
    } else {
        $('#dateRange').text('全部');
    }
}

/**
 * 初始化数据存储占用图表
 */
function initializeStorageChart() {
    var storageCtx = document.getElementById('storageChart');
    if (!storageCtx) return;
    
    var ctx = storageCtx.getContext('2d');
    var storageChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['系统进程', '环境变量', 'Nginx配置', 'PHP配置'],
            datasets: [{
                label: '数据存储占用',
                data: [35, 15, 25, 25],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.2)',
                    'rgba(54, 162, 235, 0.2)',
                    'rgba(255, 206, 86, 0.2)',
                    'rgba(75, 192, 192, 0.2)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}
