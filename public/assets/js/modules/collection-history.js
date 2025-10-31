/* ============================================
   采集历史记录模块
   ============================================ */

// 查看结果
function viewResult(historyId) {
    $('#resultModal').modal('show');
    $('#resultContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> 加载中...</div>');
    
    // 使用全局变量中的 API 基础 URL
    var url = window.apiBaseUrl + '/' + historyId + '/result';
    
    $.ajax({
        url: url,
        type: 'GET',
        success: function(response) {
            if (response.success) {
                var content = '';
                if (typeof response.data.result === 'object') {
                    content = '<pre class="json-formatter">' + JSON.stringify(response.data.result, null, 2) + '</pre>';
                } else {
                    content = '<pre class="bg-light p-3">' + response.data.result + '</pre>';
                }
                $('#resultContent').html(content);
            } else {
                $('#resultContent').html('<div class="alert alert-danger">加载失败：' + response.message + '</div>');
            }
        },
        error: function(xhr) {
            $('#resultContent').html('<div class="alert alert-danger">请求失败：' + xhr.responseText + '</div>');
        }
    });
}

// 查看错误
function viewError(historyId, errorMessage) {
    $('#errorModal').modal('show');
    $('#errorContent').text(errorMessage);
}

// 初始化
$(document).ready(function() {
    console.log('采集历史记录模块已加载');
});
