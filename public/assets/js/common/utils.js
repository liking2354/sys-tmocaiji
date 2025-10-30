/* ============================================
   工具函数 - 通用工具库
   ============================================ */

const Utils = (function() {
    'use strict';
    
    /**
     * 防抖函数
     * @param {Function} func - 要执行的函数
     * @param {Number} wait - 等待时间（毫秒）
     * @returns {Function}
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    /**
     * 节流函数
     * @param {Function} func - 要执行的函数
     * @param {Number} limit - 限制时间（毫秒）
     * @returns {Function}
     */
    function throttle(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
    
    /**
     * 深拷贝对象
     * @param {Object} obj - 要拷贝的对象
     * @returns {Object}
     */
    function deepClone(obj) {
        if (obj === null || typeof obj !== 'object') return obj;
        if (obj instanceof Date) return new Date(obj.getTime());
        if (obj instanceof Array) return obj.map(item => deepClone(item));
        if (obj instanceof Object) {
            const clonedObj = {};
            for (const key in obj) {
                if (obj.hasOwnProperty(key)) {
                    clonedObj[key] = deepClone(obj[key]);
                }
            }
            return clonedObj;
        }
    }
    
    /**
     * 合并对象
     * @param {Object} target - 目标对象
     * @param {Object} source - 源对象
     * @returns {Object}
     */
    function merge(target, source) {
        const result = deepClone(target);
        for (const key in source) {
            if (source.hasOwnProperty(key)) {
                result[key] = source[key];
            }
        }
        return result;
    }
    
    /**
     * 格式化日期
     * @param {Date|String} date - 日期对象或字符串
     * @param {String} format - 格式字符串 (yyyy-MM-dd HH:mm:ss)
     * @returns {String}
     */
    function formatDate(date, format = 'yyyy-MM-dd HH:mm:ss') {
        if (typeof date === 'string') {
            date = new Date(date);
        }
        
        const map = {
            'yyyy': date.getFullYear(),
            'MM': String(date.getMonth() + 1).padStart(2, '0'),
            'dd': String(date.getDate()).padStart(2, '0'),
            'HH': String(date.getHours()).padStart(2, '0'),
            'mm': String(date.getMinutes()).padStart(2, '0'),
            'ss': String(date.getSeconds()).padStart(2, '0')
        };
        
        return format.replace(/yyyy|MM|dd|HH|mm|ss/g, matched => map[matched]);
    }
    
    /**
     * 格式化文件大小
     * @param {Number} bytes - 字节数
     * @returns {String}
     */
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }
    
    /**
     * 生成UUID
     * @returns {String}
     */
    function generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            const r = Math.random() * 16 | 0;
            const v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }
    
    /**
     * 获取URL参数
     * @param {String} name - 参数名
     * @returns {String|null}
     */
    function getUrlParam(name) {
        const url = new URL(window.location);
        return url.searchParams.get(name);
    }
    
    /**
     * 设置URL参数
     * @param {String} name - 参数名
     * @param {String} value - 参数值
     */
    function setUrlParam(name, value) {
        const url = new URL(window.location);
        url.searchParams.set(name, value);
        window.history.pushState({}, '', url);
    }
    
    /**
     * 删除URL参数
     * @param {String} name - 参数名
     */
    function removeUrlParam(name) {
        const url = new URL(window.location);
        url.searchParams.delete(name);
        window.history.pushState({}, '', url);
    }
    
    /**
     * 检查元素是否在视口内
     * @param {Element} element - DOM元素
     * @returns {Boolean}
     */
    function isInViewport(element) {
        const rect = element.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    }
    
    /**
     * 平滑滚动到元素
     * @param {Element|String} target - 目标元素或选择器
     * @param {Number} offset - 偏移量
     */
    function scrollToElement(target, offset = 0) {
        if (typeof target === 'string') {
            target = document.querySelector(target);
        }
        if (target) {
            const top = target.getBoundingClientRect().top + window.scrollY - offset;
            window.scrollTo({ top, behavior: 'smooth' });
        }
    }
    
    /**
     * 复制文本到剪贴板
     * @param {String} text - 要复制的文本
     * @returns {Promise}
     */
    function copyToClipboard(text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(text);
        } else {
            return new Promise((resolve, reject) => {
                const textarea = document.createElement('textarea');
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                try {
                    document.execCommand('copy');
                    resolve();
                } catch (err) {
                    reject(err);
                }
                document.body.removeChild(textarea);
            });
        }
    }
    
    /**
     * 延迟执行
     * @param {Number} ms - 延迟时间（毫秒）
     * @returns {Promise}
     */
    function delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
    
    /**
     * 重试函数
     * @param {Function} func - 要执行的函数
     * @param {Number} times - 重试次数
     * @param {Number} interval - 重试间隔（毫秒）
     * @returns {Promise}
     */
    async function retry(func, times = 3, interval = 1000) {
        for (let i = 0; i < times; i++) {
            try {
                return await func();
            } catch (error) {
                if (i === times - 1) throw error;
                await delay(interval);
            }
        }
    }
    
    /**
     * 验证邮箱
     * @param {String} email - 邮箱地址
     * @returns {Boolean}
     */
    function isValidEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }
    
    /**
     * 验证手机号
     * @param {String} phone - 手机号
     * @returns {Boolean}
     */
    function isValidPhone(phone) {
        const regex = /^1[3-9]\d{9}$/;
        return regex.test(phone);
    }
    
    /**
     * 验证URL
     * @param {String} url - URL地址
     * @returns {Boolean}
     */
    function isValidUrl(url) {
        try {
            new URL(url);
            return true;
        } catch (error) {
            return false;
        }
    }
    
    // 公开接口
    return {
        debounce,
        throttle,
        deepClone,
        merge,
        formatDate,
        formatFileSize,
        generateUUID,
        getUrlParam,
        setUrlParam,
        removeUrlParam,
        isInViewport,
        scrollToElement,
        copyToClipboard,
        delay,
        retry,
        isValidEmail,
        isValidPhone,
        isValidUrl
    };
})();
