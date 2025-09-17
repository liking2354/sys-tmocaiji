<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class QueueWorkCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tmocaiji:queue:work {--timeout=120 : 任务超时时间(秒)} {--tries=3 : 任务重试次数} {--sleep=3 : 队列空闲时休眠时间(秒)}'; 

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '启动队列工作进程，处理采集任务';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $timeout = $this->option('timeout');
        $tries = $this->option('tries');
        $sleep = $this->option('sleep');
        
        $this->info('启动队列工作进程...');
        $this->info("超时时间: {$timeout}秒, 重试次数: {$tries}, 休眠时间: {$sleep}秒");
        
        $command = "queue:work --timeout={$timeout} --tries={$tries} --sleep={$sleep}";
        
        $this->info("执行命令: php artisan {$command}");
        $this->call('queue:work', [
            '--timeout' => $timeout,
            '--tries' => $tries,
            '--sleep' => $sleep,
        ]);
        
        return 0;
    }
}