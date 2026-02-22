<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Cardinal Stage'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="<?php echo env('APP_URL'); ?>/styles/theme.css" rel="stylesheet">
</head>
<body>
    <?php include app_path('views/components/navigation.php'); ?>
    
    <main>
        <?php echo $content ?? ''; ?>
    </main>

    <?php include app_path('views/components/footer.php'); ?>

    <script src="<?php echo env('APP_URL'); ?>/js/main.js" defer></script>
</body>
</html>
