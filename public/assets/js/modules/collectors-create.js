/**
 * 采集器创建模块
 * 功能：文件上传、组件类型切换、脚本内容管理
 */

$(document).ready(function() {
    // 文件上传显示文件名
    $('.custom-file-input').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName);
    });
    
    // 根据组件类型显示/隐藏相应表单项
    function toggleTypeFields() {
        var type = $('input[name="type"]:checked').val();
        if (type === 'script') {
            $('#script_type_fields').show();
            $('#program_type_fields').hide();
            $('#script_content_field').show();
            $('#script_content').prop('required', true);
            $('#program_file').prop('required', false);
        } else {
            $('#script_type_fields').hide();
            $('#program_type_fields').show();
            $('#script_content_field').hide();
            $('#script_content').prop('required', false);
            $('#program_file').prop('required', true);
        }
    }
    
    // 初始化表单状态
    toggleTypeFields();
    
    // 监听类型选择变化
    $('input[name="type"]').change(function() {
        toggleTypeFields();
    });
});
