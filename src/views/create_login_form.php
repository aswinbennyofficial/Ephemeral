<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ucfirst($action); ?> - FileShare</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
    <div class="min-h-screen bg-base-200 flex items-center">
        <div class="card mx-auto w-full max-w-md shadow-xl bg-base-100">
            <div class="card-body">
                <h2 class="card-title text-2xl font-bold mb-4"><?php echo ucfirst($action); ?></h2>
                <?php if (isset($error)): ?>
                    <div class="alert alert-error mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>
                <form action="/create_or_login.php" method="post">
                    <input type="hidden" name="action" value="<?php echo $action; ?>">
                    <?php if ($action === 'register'): ?>
                        <div class="form-control">
                            <label class="label" for="firstName">
                                <span class="label-text">First Name</span>
                            </label>
                            <input type="text" id="firstName" name="firstName" class="input input-bordered" required />
                        </div>
                        <div class="form-control">
                            <label class="label" for="lastName">
                                <span class="label-text">Last Name</span>
                            </label>
                            <input type="text" id="lastName" name="lastName" class="input input-bordered" required />
                        </div>
                    <?php endif; ?>
                    <div class="form-control">
                        <label class="label" for="email">
                            <span class="label-text">Email</span>
                        </label>
                        <input type="email" id="email" name="email" class="input input-bordered" required />
                    </div>
                    <div class="form-control">
                        <label class="label" for="password">
                            <span class="label-text">Password</span>
                        </label>
                        <input type="password" id="password" name="password" class="input input-bordered" required />
                    </div>
                    <div class="form-control mt-6">
                        <button type="submit" class="btn btn-primary"><?php echo ucfirst($action); ?></button>
                    </div>
                </form>
                <div class="text-center mt-4">
                    <?php if ($action === 'login'): ?>
                        <p>Don't have an account? <a href="/create_or_login.php?action=register" class="link link-primary">Register</a></p>
                    <?php else: ?>
                        <p>Already have an account? <a href="/create_or_login.php?action=login" class="link link-primary">Login</a></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>