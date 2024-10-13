<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download File - FileShare</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
    <div class="min-h-screen bg-base-200 flex items-center">
        <div class="card mx-auto w-full max-w-md shadow-xl bg-base-100">
            <div class="card-body">
                <h2 class="card-title text-2xl font-bold mb-4">Download File</h2>
                <?php if (isset($error)): ?>
                    <div class="alert alert-error mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php elseif (isset($file)): ?>
                    <p class="mb-4">File: <?php echo htmlspecialchars($file['metadata']['originalName']); ?></p>
                    <?php if ($file['passwordHash']): ?>
                        <form action="/download.php" method="post">
                            <input type="hidden" name="slug" value="<?php echo htmlspecialchars($file['slug']); ?>" />
                            <div class="form-control mb-4">
                                <label class="label" for="password">
                                    <span class="label-text">This file is password protected. Please enter the password:</span>
                                </label>
                                <input type="password" id="password" name="password" class="input input-bordered" required />
                            </div>
                            <div class="form-control">
                                <button type="submit" class="btn btn-primary">Download</button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="form-control">
                            <a href="/download.php?slug=<?php echo urlencode($file['slug']); ?>&download=true" class="btn btn-primary">Download File</a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p>File not found or has expired.</p>
                <?php endif; ?>
                <div class="text-center mt-4">
                    <a href="/" class="link link-primary">Back to Home</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
