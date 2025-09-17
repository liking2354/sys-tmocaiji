<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\OperationLog;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    /**
     * 登录成功后的跳转地址
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * 创建一个新的控制器实例
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
    
    /**
     * 重写登录方法，记录登录日志
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);

        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            return $this->sendLockoutResponse($request);
        }

        if ($this->attemptLogin($request)) {
            // 更新最后登录时间
            $user = Auth::user();
            $user->last_login_time = now();
            $user->save();
            
            // 记录登录日志
            OperationLog::create([
                'user_id' => $user->id,
                'action' => 'login',
                'content' => '用户登录系统',
                'ip' => $request->ip(),
            ]);
            
            return $this->sendLoginResponse($request);
        }

        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }
    
    /**
     * 重写登出方法，记录登出日志
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        // 记录登出日志
        if (Auth::check()) {
            $user = Auth::user();
            
            OperationLog::create([
                'user_id' => $user->id,
                'action' => 'logout',
                'content' => '用户退出系统',
                'ip' => $request->ip(),
            ]);
        }
        
        $this->guard()->logout();

        $request->session()->invalidate();

        return redirect('/');
    }
    
    /**
     * 获取用于验证的用户名字段
     *
     * @return string
     */
    public function username()
    {
        return 'username';
    }
}