<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\OperationLogService;
use Illuminate\Support\Facades\Auth;

class LogOperations
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // 只记录已认证用户的操作
        if (Auth::check()) {
            $this->logOperation($request, $response);
        }

        return $response;
    }

    /**
     * 记录操作日志
     *
     * @param Request $request
     * @param $response
     */
    private function logOperation(Request $request, $response)
    {
        // 跳过不需要记录的路由
        if ($this->shouldSkipLogging($request)) {
            return;
        }

        $method = $request->method();
        $route = $request->route();
        $routeName = $route ? $route->getName() : '';
        $uri = $request->getRequestUri();

        // 根据HTTP方法和路由确定操作类型
        $action = $this->determineAction($method, $routeName, $uri);
        
        if (!$action) {
            return;
        }

        // 生成操作内容描述
        $content = $this->generateContent($request, $routeName, $action);

        // 记录日志
        OperationLogService::log($action, $content);
    }

    /**
     * 判断是否应该跳过日志记录
     *
     * @param Request $request
     * @return bool
     */
    private function shouldSkipLogging(Request $request)
    {
        $skipRoutes = [
            'dashboard',
            'admin.operation-logs.index',
            'admin.operation-logs.show',
            'admin.operation-logs.chart-data',
        ];

        $skipPaths = [
            '/api/',
            '/assets/',
            '/css/',
            '/js/',
            '/images/',
        ];

        $route = $request->route();
        $routeName = $route ? $route->getName() : '';
        $uri = $request->getRequestUri();

        // 跳过指定的路由
        if (in_array($routeName, $skipRoutes)) {
            return true;
        }

        // 跳过指定的路径
        foreach ($skipPaths as $path) {
            if (strpos($uri, $path) !== false) {
                return true;
            }
        }

        // 跳过GET请求（除了特定的操作）
        if ($request->method() === 'GET' && !$this->isImportantGetRequest($routeName)) {
            return true;
        }

        return false;
    }

    /**
     * 判断是否是重要的GET请求
     *
     * @param string $routeName
     * @return bool
     */
    private function isImportantGetRequest($routeName)
    {
        $importantGetRoutes = [
            'servers.console',
            'servers.check',
            'collection-tasks.progress',
            'task-details.result',
            'admin.operation-logs.export',
        ];

        return in_array($routeName, $importantGetRoutes);
    }

    /**
     * 确定操作类型
     *
     * @param string $method
     * @param string $routeName
     * @param string $uri
     * @return string|null
     */
    private function determineAction($method, $routeName, $uri)
    {
        // 根据路由名称确定操作类型
        if ($routeName) {
            $routeParts = explode('.', $routeName);
            $lastPart = end($routeParts);

            switch ($lastPart) {
                case 'store':
                    return 'create';
                case 'update':
                    return 'update';
                case 'destroy':
                    return 'delete';
                case 'import':
                    return 'import';
                case 'export':
                    return 'export';
                case 'verify':
                    return 'verify';
                case 'install':
                    return 'install';
                case 'uninstall':
                    return 'uninstall';
                case 'execute':
                case 'execute-single':
                    return 'execute';
                case 'retry':
                    return 'retry';
                case 'cancel':
                    return 'cancel';
                case 'cleanup':
                    return 'cleanup';
                case 'batch-delete':
                case 'batch-destroy':
                    return 'batch_operation';
                case 'console':
                    return 'view';
                case 'check':
                    return 'verify';
            }
        }

        // 根据HTTP方法确定操作类型
        switch ($method) {
            case 'POST':
                return 'create';
            case 'PUT':
            case 'PATCH':
                return 'update';
            case 'DELETE':
                return 'delete';
            case 'GET':
                return 'view';
            default:
                return null;
        }
    }

    /**
     * 生成操作内容描述
     *
     * @param Request $request
     * @param string $routeName
     * @param string $action
     * @return string
     */
    private function generateContent(Request $request, $routeName, $action)
    {
        $uri = $request->getRequestUri();
        $method = $request->method();

        // 根据路由名称生成更具体的描述
        if ($routeName) {
            $routeParts = explode('.', $routeName);
            $resource = isset($routeParts[0]) ? $routeParts[0] : '';
            $operation = isset($routeParts[1]) ? $routeParts[1] : '';

            // 资源名称映射
            $resourceMap = [
                'servers' => '服务器',
                'server-groups' => '服务器分组',
                'collectors' => '采集组件',
                'collection-tasks' => '采集任务',
                'collection-history' => '采集历史',
                'admin' => '系统管理',
                'users' => '用户',
                'roles' => '角色',
                'permissions' => '权限',
                'operation-logs' => '操作日志',
            ];

            $resourceName = $resourceMap[$resource] ?? $resource;

            // 操作类型映射
            $actionMap = [
                'create' => '创建',
                'update' => '更新',
                'delete' => '删除',
                'view' => '查看',
                'import' => '导入',
                'export' => '导出',
                'verify' => '验证',
                'install' => '安装',
                'uninstall' => '卸载',
                'execute' => '执行',
                'retry' => '重试',
                'cancel' => '取消',
                'cleanup' => '清理',
                'batch_operation' => '批量操作',
            ];

            $actionName = $actionMap[$action] ?? $action;

            // 获取资源ID（如果存在）
            $resourceId = $this->extractResourceId($request, $routeName);
            $resourceIdText = $resourceId ? "，ID: {$resourceId}" : '';

            return "{$actionName}{$resourceName}{$resourceIdText}";
        }

        // 默认描述
        return "{$method} {$uri}";
    }

    /**
     * 提取资源ID
     *
     * @param Request $request
     * @param string $routeName
     * @return string|null
     */
    private function extractResourceId(Request $request, $routeName)
    {
        $route = $request->route();
        if (!$route) {
            return null;
        }

        $parameters = $route->parameters();

        // 常见的资源参数名
        $resourceParams = [
            'server',
            'serverGroup',
            'collector',
            'task',
            'user',
            'role',
            'permission',
            'operationLog',
            'id',
        ];

        foreach ($resourceParams as $param) {
            if (isset($parameters[$param])) {
                $value = $parameters[$param];
                // 如果是模型实例，获取其ID
                if (is_object($value) && method_exists($value, 'getKey')) {
                    return $value->getKey();
                }
                return $value;
            }
        }

        return null;
    }
}