<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Collector extends Model
{
    use HasFactory;
    
    /**
     * 模型的「引导」方法
     *
     * @return void
     */
    protected static function booted()
    {
        // 当采集组件被创建、更新或删除时，清除相关缓存
        static::saved(function ($collector) {
            Log::info('采集组件数据变更，清除缓存', ['collector_id' => $collector->id]);
            // 清除特定采集组件相关缓存
            $statusKey = 'collectors:status_' . $collector->status;
            $typeKey = 'collectors:type_' . $collector->type;
            Cache::forget($statusKey);
            Cache::forget($typeKey);
            // 清除可能的分页缓存
            for ($i = 1; $i <= 5; $i++) {
                Cache::forget('collectors:page_' . $i . ':per_page_20');
                Cache::forget('collectors:status_' . $collector->status . ':page_' . $i . ':per_page_20');
                Cache::forget('collectors:type_' . $collector->type . ':page_' . $i . ':per_page_20');
                Cache::forget('collectors:status_' . $collector->status . ':type_' . $collector->type . ':page_' . $i . ':per_page_20');
            }
        });
        
        static::deleted(function ($collector) {
            Log::info('采集组件被删除，清除缓存', ['collector_id' => $collector->id]);
            // 清除特定采集组件相关缓存
            $statusKey = 'collectors:status_' . $collector->status;
            $typeKey = 'collectors:type_' . $collector->type;
            Cache::forget($statusKey);
            Cache::forget($typeKey);
            // 清除可能的分页缓存
            for ($i = 1; $i <= 5; $i++) {
                Cache::forget('collectors:page_' . $i . ':per_page_20');
                Cache::forget('collectors:status_' . $collector->status . ':page_' . $i . ':per_page_20');
                Cache::forget('collectors:type_' . $collector->type . ':page_' . $i . ':per_page_20');
                Cache::forget('collectors:status_' . $collector->status . ':type_' . $collector->type . ':page_' . $i . ':per_page_20');
            }
        });
    }
    
    /**
     * 可批量赋值的属性
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'script_content',
        'script_path',
        'file_path',
        'status',
        'type',
        'version',
        'deployment_config',
    ];
    
    /**
     * 应该被转换为原生类型的属性
     *
     * @var array
     */
    protected $casts = [
        'deployment_config' => 'array',
    ];
    
    /**
     * 获取关联此采集组件的服务器
     */
    public function servers()
    {
        return $this->belongsToMany(Server::class, 'server_collector')
            ->withPivot('installed_at', 'status')
            ->withTimestamps();
    }

    /**
     * 获取采集组件的任务详情
     */
    public function taskDetails()
    {
        return $this->hasMany(TaskDetail::class);
    }

    /**
     * 获取采集组件的采集历史
     */
    public function collectionHistory()
    {
        return $this->hasMany(CollectionHistory::class);
    }

    /**
     * 获取最近的采集统计
     */
    public function getRecentStats($days = 7)
    {
        return $this->collectionHistory()
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as success,
                SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) as failed,
                AVG(execution_time) as avg_execution_time
            ')
            ->first();
    }

    
    /**
     * 判断是否为脚本类采集组件
     *
     * @return bool
     */
    public function isScriptType(): bool
    {
        return $this->type === 'script';
    }
    
    /**
     * 判断是否为程序类采集组件
     *
     * @return bool
     */
    public function isProgramType(): bool
    {
        return $this->type === 'program';
    }
    
    /**
     * 获取采集组件类型的显示名称
     *
     * @return string
     */
    public function getTypeNameAttribute(): string
    {
        return $this->type === 'program' ? '程序类' : '脚本类';
    }
    
    /**
     * 获取脚本内容，优先从文件系统读取
     *
     * @return string
     */
    public function getScriptContent(): string
    {
        // 如果有script_path且文件存在，则从文件读取
        if (!empty($this->script_path) && Storage::exists($this->script_path)) {
            return Storage::get($this->script_path);
        }
        
        // 否则返回数据库中存储的内容
        return $this->script_content;
    }
}