<!doctype html>
<html>
	<head>
		<meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title><?php __('script_name') ?> by PHPJabbers.com</title>
        <?php if (!in_array(isset($_GET['controller']) ? $_GET['controller'] : '', array('pjFront', 'pjFrontEnd', 'pjFrontPublic', 'pjCron', 'pjInstaller'))): ?>
        <meta name="csrf-token" content="<?php echo pjAppController::getCsrfToken(); ?>" />
        <?php endif; ?>
        <?php
        $cnt = count($controller->getCss());
        foreach ($controller->getCss() as $i => $css)
        {
        	echo '<link rel="stylesheet" href="'.(isset($css['remote']) && $css['remote'] ? NULL : PJ_INSTALL_URL).$css['path'].$css['file'].'">';
        	echo "\n";
        	if ($i < $cnt - 1)
        	{
        		echo "\t";
        	}
        }
        ?>
        <link rel="stylesheet" href="<?php echo PJ_INSTALL_URL; ?>app/web/css/admin-modern.css?v=<?php echo @filemtime(PJ_INSTALL_PATH . 'app/web/css/admin-modern.css'); ?>">
	</head>
	<body>
		<div id="wrapper">
			<?php require dirname(__FILE__) . '/elements/menu-left.php'; ?>
			<div id="page-wrapper" class="gray-bg dashbard-1">
    			<?php
    			require dirname(__FILE__) . '/elements/menu-top.php'; 
    			
    			require $content_tpl;
    			
    			include dirname(__FILE__) . '/elements/footer-default.php'; 
    			?>
    		</div>
		</div><!-- #wrapper -->
		<?php 
    	$cnt = count($controller->getJs());
    	foreach ($controller->getJs() as $i => $js)
    	{
    		$_pj_remote = isset($js['remote']) && $js['remote'];
    		$_pj_fs = rtrim(PJ_INSTALL_PATH, '/\\') . '/' . ltrim($js['path'].$js['file'], '/');
    		$_pj_ver = (!$_pj_remote && @file_exists($_pj_fs)) ? '?v=' . @filemtime($_pj_fs) : '';
    		echo '<script src="'.($_pj_remote ? NULL : PJ_INSTALL_URL).$js['path'].$js['file'].$_pj_ver.'"></script>';
    		echo "\n";
    		if ($i < $cnt - 1)
    		{
    			echo "\t";
    		}
    	}
    	?>
	</body>
</html>