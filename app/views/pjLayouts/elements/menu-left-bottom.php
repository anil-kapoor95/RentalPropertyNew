<?php
$controller_name = $controller->_get->toString('controller');
$action_name = $controller->_get->toString('action');

$isScriptPreview = $controller_name == 'pjAdminOptions' && $action_name == 'pjActionPreview';
$isScriptColors  = $controller_name == 'pjAdminOptions' && $action_name == 'pjActionColors';
$isScriptInstall = $controller_name == 'pjAdminOptions' && $action_name == 'pjActionInstall';

$hasAccessScriptPreview = pjAuth::factory('pjAdminOptions', 'pjActionPreview')->hasAccess();
$hasAccessScriptColors  = pjAuth::factory('pjAdminOptions', 'pjActionColors')->hasAccess();
$hasAccessScriptInstall = pjAuth::factory('pjAdminOptions', 'pjActionInstall')->hasAccess();
?>
<?php if ($hasAccessScriptPreview || $hasAccessScriptColors): ?>
    <li<?php echo ($isScriptPreview || $isScriptColors) ? ' class="active"' : NULL; ?>>
        <a href="#"><i class="fa fa-paint-brush"></i> <span class="nav-label"><?php __('menuDisplay');?></span><span class="fa arrow"></span></a>
        <ul class="nav nav-second-level collapse<?php echo ($isScriptPreview || $isScriptColors) ? ' in' : NULL; ?>">
            <?php if ($hasAccessScriptColors): ?>
                <li<?php echo $isScriptColors ? ' class="active"' : NULL; ?>>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminOptions&amp;action=pjActionColors"><?php __('infoThemeColorsTitle');?></a>
                </li>
            <?php endif; ?>
            <?php if ($hasAccessScriptPreview): ?>
                <li<?php echo $isScriptPreview ? ' class="active"' : NULL; ?>>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminOptions&amp;action=pjActionPreview"><?php __('menuPreview');?></a>
                </li>
            <?php endif; ?>
        </ul>
    </li>
<?php endif; ?>

<?php if ($hasAccessScriptInstall): ?>
    <li<?php echo $isScriptInstall ? ' class="active"' : NULL; ?>>
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminOptions&amp;action=pjActionInstall"><i class="fa fa-code"></i> <span class="nav-label"><?php __('menuInstall');?></span></a>
    </li>
<?php endif; ?>
