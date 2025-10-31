/**
 * 复选框事件处理模块
 * 提供隔离的复选框事件处理，防止事件冒泡影响其他组件
 */

const CheckboxHandler = (function() {
    'use strict';
    
    /**
     * 初始化复选框事件处理
     * @param {string} checkboxSelector - 复选框选择器
     * @param {string} selectAllSelector - 全选复选框选择器
     * @param {function} onStateChange - 状态变化回调函数
     */
    function init(checkboxSelector, selectAllSelector, onStateChange) {
        if (!checkboxSelector || !selectAllSelector) {
            console.error('CheckboxHandler: 必须提供复选框选择器');
            return;
        }
        
        // 绑定全选复选框事件
        bindSelectAllCheckbox(selectAllSelector, checkboxSelector, onStateChange);
        
        // 绑定单个复选框事件
        bindIndividualCheckboxes(checkboxSelector, selectAllSelector, onStateChange);
    }
    
    /**
     * 绑定全选复选框事件
     */
    function bindSelectAllCheckbox(selectAllSelector, checkboxSelector, onStateChange) {
        const selectAllElement = document.querySelector(selectAllSelector);
        if (!selectAllElement) return;
        
        selectAllElement.addEventListener('change', function(e) {
            // 阻止事件冒泡
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            const isChecked = this.checked;
            const checkboxes = document.querySelectorAll(checkboxSelector);
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
            });
            
            // 使用 requestAnimationFrame 确保 DOM 更新完成后再调用回调
            requestAnimationFrame(function() {
                if (typeof onStateChange === 'function') {
                    onStateChange();
                }
            });
        }, true); // 使用捕获阶段，确保最先处理
    }
    
    /**
     * 绑定单个复选框事件
     */
    function bindIndividualCheckboxes(checkboxSelector, selectAllSelector, onStateChange) {
        const checkboxes = document.querySelectorAll(checkboxSelector);
        const selectAllElement = document.querySelector(selectAllSelector);
        
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function(e) {
                // 阻止事件冒泡
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                // 更新全选复选框状态
                updateSelectAllCheckbox(checkboxes, selectAllElement);
                
                // 使用 requestAnimationFrame 确保 DOM 更新完成后再调用回调
                requestAnimationFrame(function() {
                    if (typeof onStateChange === 'function') {
                        onStateChange();
                    }
                });
            }, true); // 使用捕获阶段，确保最先处理
        });
    }
    
    /**
     * 更新全选复选框状态
     */
    function updateSelectAllCheckbox(checkboxes, selectAllElement) {
        if (!selectAllElement) return;
        
        const totalCount = checkboxes.length;
        const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
        
        selectAllElement.checked = totalCount > 0 && checkedCount === totalCount;
    }
    
    /**
     * 获取选中的复选框值
     */
    function getCheckedValues(checkboxSelector) {
        const checkboxes = document.querySelectorAll(checkboxSelector);
        return Array.from(checkboxes)
            .filter(cb => cb.checked)
            .map(cb => cb.value);
    }
    
    /**
     * 获取选中的复选框数量
     */
    function getCheckedCount(checkboxSelector) {
        const checkboxes = document.querySelectorAll(checkboxSelector);
        return Array.from(checkboxes).filter(cb => cb.checked).length;
    }
    
    /**
     * 公开 API
     */
    return {
        init: init,
        getCheckedValues: getCheckedValues,
        getCheckedCount: getCheckedCount
    };
})();
