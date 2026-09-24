<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

        <title><?php echo e(config('app.name', 'SafeEdu Admin')); ?></title>

        <link rel="stylesheet" href="<?php echo e(asset('css/variables.css')); ?>">
        <link rel="stylesheet" href="<?php echo e(asset('css/main.css')); ?>">
        
        <style>
            body {
                background-color: var(--bg-main);
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                margin: 0;
            }
            .auth-card {
                background: white;
                padding: 40px;
                border-radius: var(--radius-lg);
                box-shadow: var(--shadow-md);
                width: 100%;
                max-width: 450px;
                border: 1px solid var(--border-color);
            }
            .auth-logo {
                text-align: center;
                margin-bottom: 30px;
                color: var(--primary);
            }
            .auth-logo h1 {
                font-size: 1.8rem;
                font-weight: 700;
                margin-top: 10px;
                color: var(--text-main);
            }
            .form-control {
                width: 100%;
                padding: 12px 14px;
                border: 1px solid var(--border-color);
                border-radius: var(--radius-md);
                font-size: 0.9rem;
                outline: none;
                transition: border-color 0.2s;
            }
            .form-control:focus {
                border-color: var(--primary);
            }
            .form-label {
                display: block;
                font-size: 0.85rem;
                font-weight: 600;
                color: var(--text-main);
                margin-bottom: 6px;
            }
            .form-group {
                margin-bottom: 20px;
            }
            .btn-full {
                width: 100%;
                justify-content: center;
                padding: 12px;
                font-size: 0.95rem;
            }
            .auth-links {
                text-align: center;
                margin-top: 20px;
                font-size: 0.85rem;
            }
            .auth-links a {
                color: var(--primary);
                text-decoration: none;
                font-weight: 600;
            }
        </style>
    </head>
    <body>
        <div class="auth-card">
            <div class="auth-logo">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
                <h1>SafeEdu Admin</h1>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 5px;">Portal Manajemen Keamanan & Fasilitas</p>
            </div>

            <?php echo e($slot); ?>

        </div>
    </body>
</html>
<?php /**PATH D:\SafeEdu\admin\resources\views/layouts/guest.blade.php ENDPATH**/ ?>