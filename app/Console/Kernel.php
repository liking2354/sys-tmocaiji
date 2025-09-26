<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\CollectorUpdateCommand;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        CollectorUpdateCommand::class,
        Commands\QueueWorkCommand::class,
        Commands\ResetStuckTasksCommand::class,
        Commands\DiagnoseTasksCommand::class,
        Commands\FixTaskStatusCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // 每天凌晨2点检查采集组件更新
        $schedule->command('collector:update --check')
            ->dailyAt('02:00')
            ->appendOutputTo(storage_path('logs/collector-check.log'));
        
        // 每周日凌晨3点自动更新采集组件
        $schedule->command('collector:update')
            ->weeklyOn(0, '03:00')
            ->appendOutputTo(storage_path('logs/collector-update.log'));
            
        // 每小时重置卡住的任务（超过2小时的任务）
        $schedule->command('tasks:reset-stuck --hours=2')
            ->hourly()
            ->appendOutputTo(storage_path('logs/tasks-reset.log'));
            
        // 每10分钟监控任务状态，处理超时和失败的任务
        // $schedule->command('tasks:monitor --retry --timeout')
        //     ->everyTenMinutes()
        //     ->appendOutputTo(storage_path('logs/tasks-monitor.log'));
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}