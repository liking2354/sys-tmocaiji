<?php $__env->startSection('title', '登录 - TMO云迁移'); ?>

<?php $__env->startSection('styles'); ?>
<style>
    .login-page {
        min-height: 100vh;
        background: linear-gradient(135deg, #3498db, #2c3e50);
        padding-top: 50px;
        padding-bottom: 50px;
    }
    .login-card {
        border: none;
        border-radius: 15px;
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }
    .login-header {
        background: linear-gradient(135deg, #3498db, #2980b9);
        padding: 25px;
        text-align: center;
        border-bottom: none;
    }
    .login-header h3 {
        margin: 0;
        color: white;
        font-weight: 600;
        font-size: 1.3rem;
    }
    .login-body {
        padding: 40px;
    }
    .login-logo {
        text-align: center;
        margin-bottom: 30px;
    }
    .login-logo i {
        font-size: 48px;
        color: #3498db;
    }
    .form-group {
        margin-bottom: 25px;
    }
    .form-control {
        height: 50px;
        border-radius: 10px;
        padding-left: 20px;
        border: 1px solid #e0e0e0;
        transition: all 0.3s;
    }
    .form-control:focus {
        border-color: #3498db;
        box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
    }
    .input-group-text {
        background-color: transparent;
        border-right: none;
        border-top-left-radius: 10px;
        border-bottom-left-radius: 10px;
        color: #3498db;
    }
    .input-group .form-control {
        border-left: none;
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }
    .btn-login {
        height: 50px;
        border-radius: 10px;
        font-weight: 600;
        font-size: 16px;
        background: linear-gradient(135deg, #3498db, #2980b9);
        border: none;
        box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        transition: all 0.3s;
    }
    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 7px 20px rgba(52, 152, 219, 0.4);
    }
    .remember-me {
        display: flex;
        align-items: center;
    }
    .remember-me input {
        margin-right: 10px;
    }
    
    /* 响应式设计 */
    @media (max-width: 767px) {
        .login-page {
            padding-top: 20px;
            padding-bottom: 20px;
        }
        .login-body {
            padding: 25px;
        }
        .login-header h3 {
            font-size: 1.2rem;
        }
        .login-logo i {
            font-size: 36px;
        }
        .form-control {
            height: 45px;
        }
        .btn-login {
            height: 45px;
        }
    }
    
    @media (max-width: 480px) {
        .login-header {
            padding: 15px;
        }
        .login-body {
            padding: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
    }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="login-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="login-card card">
                    <div class="login-header">
                        <h3>TMO云迁移</h3>
                    </div>

                    <div class="login-body card-body">
                        <div class="login-logo">
                            <i class="fas fa-server"></i>
                        </div>
                        
                        <h4 class="text-center mb-4">用户登录</h4>
                        
                        <form method="POST" action="<?php echo e(route('login')); ?>">
                            <?php echo csrf_field(); ?>

                            <div class="form-group">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    </div>
                                    <input id="username" type="text" placeholder="请输入用户名" class="form-control <?php $__errorArgs = ['username'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="username" value="<?php echo e(old('username')); ?>" required autocomplete="username" autofocus>
                                </div>
                                <?php $__errorArgs = ['username'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <span class="invalid-feedback d-block mt-1" role="alert">
                                        <strong><?php echo e($message); ?></strong>
                                    </span>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            <div class="form-group">
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    </div>
                                    <input id="password" type="password" placeholder="请输入密码" class="form-control <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="password" required autocomplete="current-password">
                                </div>
                                <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <span class="invalid-feedback d-block mt-1" role="alert">
                                        <strong><?php echo e($message); ?></strong>
                                    </span>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            <div class="form-group">
                                <div class="remember-me">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember" <?php echo e(old('remember') ? 'checked' : ''); ?>>
                                    <label class="form-check-label" for="remember">
                                        记住我
                                    </label>
                                </div>
                            </div>

                            <div class="form-group mb-0">
                                <button type="submit" class="btn btn-primary btn-block btn-login">
                                    <i class="fas fa-sign-in-alt mr-2"></i> 登录
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="text-center mt-3 text-white">
                    <small>&copy; <?php echo e(date('Y')); ?> TMO云迁移</small>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /Users/tanli/Documents/php-code/sys-tmocaiji/resources/views/auth/login.blade.php ENDPATH**/ ?>