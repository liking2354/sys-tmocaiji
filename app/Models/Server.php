<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class Server extends Model
{
    use HasFactory;
    
    /**
     * 模型的「引导」方法
     *
     * @return void
     */
    protected static function booted()
    {
        // 当服务器被创建、更新或删除时，清除相关缓存
        static::saved(function ($server) {
            Log::info('服务器数据变更，清除缓存', ['server_id' => $server->id]);
            // 清除特定服务器相关缓存
            $cacheKey = 'servers:group_' . $server->group_id;
            Cache::forget($cacheKey);
            // 清除可能的分页缓存
            for ($i = 1; $i <= 5; $i++) {
                Cache::forget('servers:page_' . $i . ':per_page_20');
                Cache::forget('servers:group_' . $server->group_id . ':page_' . $i . ':per_page_20');
            }
        });
        
        static::deleted(function ($server) {
            Log::info('服务器被删除，清除缓存', ['server_id' => $server->id]);
            // 清除特定服务器相关缓存
            $cacheKey = 'servers:group_' . $server->group_id;
            Cache::forget($cacheKey);
            // 清除可能的分页缓存
            for ($i = 1; $i <= 5; $i++) {
                Cache::forget('servers:page_' . $i . ':per_page_20');
                Cache::forget('servers:group_' . $server->group_id . ':page_' . $i . ':per_page_20');
            }
        });
    }
    
    /**
     * 可批量赋值的属性
     *
     * @var array
     */
    protected $fillable = [
        'group_id',
        'name',
        'ip',
        'port',
        'username',
        'password',
        'status',
        'last_check_time',
    ];
    
    /**
     * 应该被转换为日期的属性
     *
     * @var array
     */
    protected $dates = [
        'last_check_time',
    ];
    
    /**
     * 获取服务器所属的分组
     */
    public function group()
    {
        return $this->belongsTo(ServerGroup::class, 'group_id');
    }
    
    /**
     * 获取服务器关联的采集组件
     */
    public function collectors()
    {
        return $this->belongsToMany(Collector::class, 'server_collector');
    }

    /**
     * 获取服务器的任务详情
     */
    public function taskDetails()
    {
        return $this->hasMany(TaskDetail::class);
    }

    /**
     * 获取服务器的采集历史
     */
    public function collectionHistory()
    {
        return $this->hasMany(CollectionHistory::class);
    }

    /**
     * 获取最近的采集历史
     */
    public function recentCollectionHistory($days = 7)
    {
        return $this->collectionHistory()
            ->with(['collector'])
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc');
    }

    /**
     * 获取服务器最后一次采集时间
     */
    public function getLastCollectionTimeAttribute()
    {
        $lastCollection = $this->collectionHistory()
            ->orderBy('created_at', 'desc')
            ->first();
        
        return $lastCollection ? $lastCollection->created_at : null;
    }

}