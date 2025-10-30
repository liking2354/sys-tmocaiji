/* ============================================
   API 调用封装 - AJAX 请求管理
   ============================================ */

const API = (function() {
    'use strict';
    
    // 获取 CSRF Token
    function getCsrfToken() {
        return $('meta[name="csrf-token"]').attr('content');
    }
    
    // 设置默认请求头
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': getCsrfToken()
        }
    });
    
    /**
     * 发送 GET 请求
     * @param {String} url - 请求URL
     * @param {Object} data - 请求数据
     * @param {Object} options - 选项
     * @returns {Promise}
     */
    function get(url, data = {}, options = {}) {
        return $.ajax({
            url: url,
            type: 'GET',
            data: data,
            dataType: 'json',
            ...options
        });
    }
    
    /**
     * 发送 POST 请求
     * @param {String} url - 请求URL
     * @param {Object} data - 请求数据
     * @param {Object} options - 选项
     * @returns {Promise}
     */
    function post(url, data = {}, options = {}) {
        return $.ajax({
            url: url,
            type: 'POST',
            data: data,
            dataType: 'json',
            ...options
        });
    }
    
    /**
     * 发送 PUT 请求
     * @param {String} url - 请求URL
     * @param {Object} data - 请求数据
     * @param {Object} options - 选项
     * @returns {Promise}
     */
    function put(url, data = {}, options = {}) {
        return $.ajax({
            url: url,
            type: 'PUT',
            data: data,
            dataType: 'json',
            ...options
        });
    }
    
    /**
     * 发送 DELETE 请求
     * @param {String} url - 请求URL
     * @param {Object} data - 请求数据
     * @param {Object} options - 选项
     * @returns {Promise}
     */
    function del(url, data = {}, options = {}) {
        return $.ajax({
            url: url,
            type: 'DELETE',
            data: data,
            dataType: 'json',
            ...options
        });
    }
    
    /**
     * 发送 PATCH 请求
     * @param {String} url - 请求URL
     * @param {Object} data - 请求数据
     * @param {Object} options - 选项
     * @returns {Promise}
     */
    function patch(url, data = {}, options = {}) {
        return $.ajax({
            url: url,
            type: 'PATCH',
            data: data,
            dataType: 'json',
            ...options
        });
    }
    
    /**
     * 上传文件
     * @param {String} url - 请求URL
     * @param {FormData} formData - 表单数据
     * @param {Function} onProgress - 进度回调
     * @returns {Promise}
     */
    function upload(url, formData, onProgress = null) {
        return $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                if (onProgress && xhr.upload) {
                    xhr.upload.addEventListener('progress', function(e) {
                        if (e.lengthComputable) {
                            const percentComplete = (e.loaded / e.total) * 100;
                            onProgress(percentComplete);
                        }
                    }, false);
                }
                return xhr;
            }
        });
    }
    
    /**
     * 下载文件
     * @param {String} url - 文件URL
     * @param {String} filename - 文件名
     */
    function download(url, filename = '') {
        const link = document.createElement('a');
        link.href = url;
        link.download = filename || url.split('/').pop();
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    
    /**
     * 发送 JSON 请求
     * @param {String} url - 请求URL
     * @param {String} method - 请求方法
     * @param {Object} data - 请求数据
     * @param {Object} options - 选项
     * @returns {Promise}
     */
    function json(url, method = 'GET', data = {}, options = {}) {
        return $.ajax({
            url: url,
            type: method,
            contentType: 'application/json',
            data: JSON.stringify(data),
            dataType: 'json',
            ...options
        });
    }
    
    /**
     * 批量请求
     * @param {Array} requests - 请求数组
     * @returns {Promise}
     */
    function batch(requests) {
        return $.when(...requests);
    }
    
    /**
     * 请求超时处理
     * @param {Promise} promise - Promise对象
     * @param {Number} timeout - 超时时间（毫秒）
     * @returns {Promise}
     */
    function withTimeout(promise, timeout = 30000) {
        return Promise.race([
            promise,
            new Promise((resolve, reject) => {
                setTimeout(() => reject(new Error('请求超时')), timeout);
            })
        ]);
    }
    
    /**
     * 请求重试
     * @param {Function} requestFn - 请求函数
     * @param {Number} times - 重试次数
     * @param {Number} interval - 重试间隔（毫秒）
     * @returns {Promise}
     */
    async function retry(requestFn, times = 3, interval = 1000) {
        for (let i = 0; i < times; i++) {
            try {
                return await requestFn();
            } catch (error) {
                if (i === times - 1) throw error;
                await new Promise(resolve => setTimeout(resolve, interval));
            }
        }
    }
    
    /**
     * 全局错误处理
     * @param {Function} handler - 错误处理函数
     */
    function onError(handler) {
        $(document).ajaxError(function(event, jqxhr, settings, thrownError) {
            handler(jqxhr, settings, thrownError);
        });
    }
    
    /**
     * 全局成功处理
     * @param {Function} handler - 成功处理函数
     */
    function onSuccess(handler) {
        $(document).ajaxSuccess(function(event, jqxhr, settings) {
            handler(jqxhr, settings);
        });
    }
    
    /**
     * 全局完成处理
     * @param {Function} handler - 完成处理函数
     */
    function onComplete(handler) {
        $(document).ajaxComplete(function(event, jqxhr, settings) {
            handler(jqxhr, settings);
        });
    }
    
    // 公开接口
    return {
        get,
        post,
        put,
        del,
        patch,
        upload,
        download,
        json,
        batch,
        withTimeout,
        retry,
        onError,
        onSuccess,
        onComplete,
        getCsrfToken
    };
})();
