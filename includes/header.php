<?php
if (!isset($base_path)) $base_path = "./";
if (!isset($page_title)) $page_title = "INSPIRE Lite Portal";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($page_title) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base_path ?>assets/css/style.css">
    <script>
        (function() {
            const width = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth;
            if (width <= 768) {
                document.documentElement.classList.add("preload-collapsed");
            } else {
                document.documentElement.classList.add("preload-expanded");
            }
        })();
    </script>
    <script src="<?= $base_path ?>assets/js/main.js" defer></script>
</head>
<body class="dashboard-page">
