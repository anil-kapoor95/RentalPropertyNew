<?php
if (!defined('ROOT_PATH')) { header('HTTP/1.1 403 Forbidden'); exit; }
$base    = $tpl['base'];
$company = $tpl['company'] !== '' ? $tpl['company'] : __('lblAllProperties', true);
$icoBed  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9v11M2 13h20v7M22 13v7M4 9h10a2 2 0 0 1 2 2M8 9V7a2 2 0 0 1 2-2h2"/></svg>';
$icoBath = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12V6a2 2 0 0 1 2-2 2 2 0 0 1 2 2M3 12h18v3a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4v-3ZM6 20l-1 2M18 20l1 2M8 6h.01"/></svg>';
$icoArrow= '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';
$icoPin  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-5.2-7-11a7 7 0 1 1 14 0c0 5.8-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo pjSanitize::html($company); ?></title>
<link rel="stylesheet" href="<?php echo $tpl['themeCss']; ?>">
<link rel="stylesheet" href="<?php echo $tpl['cssUrl']; ?>">
</head>
<body class="rpbc">
<div class="rpbc-wrap">
    <div class="rpbc-grid">
        <?php $items = $tpl['items']; include PJ_INSTALL_PATH . 'app/views/pjListings/_cards.php'; ?>
    </div>
</div>
</body>
</html>