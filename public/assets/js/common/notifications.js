/* ============================================
   通知管理 - Toastr 封装
   ============================================ */

const Notifications = (function() {
    'use strict';
    
    // 配置 Toastr
    toastr.options = {
        closeButton: true,
        debug: false,
        newestOnTop: true,
        progressBar: true,
        positionClass: 'toast-top-right',
        preventDuplicates: false,
        onclick: null,
        showDuration: 300,
        hideDuration: 1000,
        timeOut: 5000,
        extendedTimeOut: 1000,
        showEasing: 'swing',
        hideEasing: 'linear',
        showMethod: 'fadeIn',
        hideMethod: 'fadeOut'
    };
    
    /**
     * 显示成功通知
     * @param {String} message - 消息内容
     * @param {String} title - 标题
     * @param {Object} options - 选项
     */
    function success(message, title = '成功', options = {}) {
        toastr.success(message, title, options);
    }
    
    /**
     * 显示错误通知
     * @param {String} message - 消息内容
     * @param {String} title - 标题
     * @param {Object} options - 选项
     */
    function error(message, title = '错误', options = {}) {
        toastr.error(message, title, options);
    }
    
    /**
     * 显示警告通知
     * @param {String} message - 消息内容
     * @param {String} title - 标题
     * @param {Object} options - 选项
     */
    function warning(message, title = '警告', options = {}) {
        toastr.warning(message, title, options);
    }
    
    /**
     * 显示信息通知
     * @param {String} message - 消息内容
     * @param {String} title - 标题
     * @param {Object} options - 选项
     */
    function info(message, title = '信息', options = {}) {
        toastr.info(message, title, options);
    }
    
    /**
     * 清除所有通知
     */
    function clear() {
        toastr.clear();
    }
    
    /**
     * 移除指定通知
     * @param {jQuery} $toast - Toastr 元素
     */
    function remove($toast) {
        toastr.remove($toast);
    }
    
    /**
     * 设置配置
     * @param {Object} options - 配置选项
     */
    function setOptions(options) {
        toastr.options = Object.assign(toastr.options, options);
    }
    
    /**
     * 显示加载中通知
     * @param {String} message - 消息内容
     * @returns {jQuery}
     */
    function loading(message = '加载中...') {
        const $toast = $('<div class="toast-loading">' +
            '<i class="fas fa-spinner fa-spin mr-2"></i>' +
            message +
            '</div>');
        
        const $container = $('.toast-container');
        if ($container.length === 0) {
            $('<div class="toast-container"></div>').appendTo('body');
        }
        
        $toast.appendTo('.toast-container');
        return $toast;
    }
    
    /**
     * 显示确认对话框
     * @param {String} message - 消息内容
     * @param {Function} onConfirm - 确认回调
     * @param {Function} onCancel - 取消回调
     */
    function confirm(message, onConfirm, onCancel) {
        if (window.confirm(message)) {
            if (typeof onConfirm === 'function') {
                onConfirm();
            }
        } else {
            if (typeof onCancel === 'function') {
                onCancel();
            }
        }
    }
    
    /**
     * 显示提示对话框
     * @param {String} message - 消息内容
     * @param {String} defaultValue - 默认值
     * @returns {String|null}
     */
    function prompt(message, defaultValue = '') {
        return window.prompt(message, defaultValue);
    }
    
    /**
     * 显示警告对话框
     * @param {String} message - 消息内容
     */
    function alert(message) {
        window.alert(message);
    }
    
    // 公开接口
    return {
        success,
        error,
        warning,
        info,
        clear,
        remove,
        setOptions,
        loading,
        confirm,
        prompt,
        alert
    };
})();
