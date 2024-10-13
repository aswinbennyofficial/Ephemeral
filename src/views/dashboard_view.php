<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - FileShare</title>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
    <div class="container mx-auto px-4 py-8">
        <div class="navbar bg-base-100 shadow-lg rounded-box mb-8">
            <div class="flex-1">
                <a class="btn btn-ghost normal-case text-xl">FileShare</a>
            </div>
            <div class="flex-none">
                <ul class="menu menu-horizontal px-1">
                    <li><a href="/dashboard.php">Dashboard</a></li>
                    <li><a href="/logout.php">Logout</a></li>
                </ul>
            </div>
        </div>

        <h1 class="text-4xl font-bold mb-8">Welcome, <?php echo htmlspecialchars($user['firstName']); ?>!</h1>

        <div class="mb-8">
            <h2 class="text-2xl font-semibold mb-4">Upload a New File</h2>
            <form action="/upload.php" method="post" enctype="multipart/form-data" class="flex items-center space-x-4">
                <input type="file" name="file" class="file-input file-input-bordered w-full max-w-xs" required />
                <input type="password" name="password" placeholder="Optional password" class="input input-bordered" />
                <button type="submit" class="btn btn-primary">Upload</button>
            </form>
        </div>

        <div>
            <h2 class="text-2xl font-semibold mb-4">Your Files</h2>
            <?php if (empty($files)): ?>
                <p>You haven't uploaded any files yet.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <thead>
                            <tr>
                                <th>File Name</th>
                                <th>Upload Date</th>
                                <th>Expiry Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($files as $file): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($file['metadata']['originalName']); ?></td>
                                    <td><?php echo htmlspecialchars($file['created_at']); ?></td>
                                    <td><?php echo htmlspecialchars($file['expiry']); ?></td>
                                    <td>
                                        <a href="/download.php?slug=<?php echo urlencode($file['slug']); ?>" class="btn btn-sm btn-primary">Download</a>
                                        <button class="btn btn-sm btn-secondary" onclick="copyToClipboard('<?php echo htmlspecialchars($_SERVER['HTTP_HOST'] . '/download.php?slug=' . $file['slug']); ?>')">Share</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                alert('Link copied to clipboard!');
            }, function(err) {
                console.error('Could not copy text: ', err);
            });
        }
    </script>
</body>
</html>