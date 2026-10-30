<?php
$controller_name = $controller->_get->toString('controller');
$action_name = $controller->_get->toString('action');

// Dashboard
$isScriptDashboard = in_array($controller_name, array('pjAdmin')) && in_array($action_name, array('pjActionIndex'));

// Calendars
$isScriptCalendars = in_array($controller_name, array('pjAdminCalendars'));

// Reservations
$isScriptReservations = in_array($controller_name, array('pjAdminReservations'));
$isScriptReservationsController = $isScriptReservations && in_array($action_name, array('pjActionIndex', 'pjActionCreate', 'pjActionUpdate'));
$isScriptScheduleController = $isScriptReservations && in_array($action_name, array('pjActionSchedule'));

// Companies
$isScriptCompanies = in_array($controller_name, array('pjAdminCompanies')) && $action_name !== 'pjActionProfile';
$isScriptMyCompany = in_array($controller_name, array('pjAdminCompanies')) && $action_name === 'pjActionProfile';

// Extras
$isScriptExtras = in_array($controller_name, array('pjAdminExtras'));
$isScriptFeatures = in_array($controller_name, array('pjAdminFeatures'));

// Vouchers
$isScriptVouchers = in_array($controller_name, array('pjAdminVouchers'));

// Discounts
$isScriptDiscounts = in_array($controller_name, array('pjAdminDiscounts'));

// Settings
$isScriptOptions = in_array($controller_name, array('pjAdminOptions')) && !in_array($action_name, array('pjActionPreview', 'pjActionInstall'));
$isScriptOptionsNotifications         = $isScriptOptions && in_array($action_name, array('pjActionNotifications'));
$isScriptOptionsAi                    = $isScriptOptions && $action_name === 'pjActionAi';
// Company options: General (System Options > General of the active company)
$isScriptCompanyGeneral = $controller_name === 'pjBaseOptions' && $action_name === 'pjActionIndex';


// Permissions - Dashboard
$hasAccessScriptDashboard = pjAuth::factory('pjAdmin', 'pjActionIndex')->hasAccess();

// Permissions - Calendars
$hasAccessScriptCalendars          = pjAuth::factory('pjAdminCalendars')->hasAccess() && pjAuth::factory('pjAdminCalendars', 'pjActionIndex')->hasAccess();

// Permissions - Reservations
$hasAccessScriptReservations          = pjAuth::factory('pjAdminReservations', 'pjActionIndex')->hasAccess();
$hasAccessScriptSchedule          = pjAuth::factory('pjAdminReservations', 'pjActionSchedule')->hasAccess();

// Permissions - Companies
$hasAccessScriptCompanies          = pjAuth::factory('pjAdminCompanies', 'pjActionIndex')->hasAccess();
$hasAccessScriptMyCompany          = (int) $controller->getRoleId() === 4 && pjAuth::factory('pjAdminCompanies', 'pjActionProfile')->hasAccess();

// Permissions - Settings
$hasAccessScriptOptions                 = pjAuth::factory('pjAdminOptions')->hasAccess();
$hasAccessScriptOptionsNotifications          = $controller->isAdmin() && pjAuth::factory('pjAdminOptions', 'pjActionNotifications')->hasAccess();
$hasAccessScriptCompanyGeneral          = pjAuth::factory('pjBaseOptions', 'pjActionIndex')->hasAccess();
$hasAccessScriptOptionsAi          = $controller->isAdmin() && pjAuth::factory('pjAdminOptions', 'pjActionAi')->hasAccess();

// Permissions - Extras
$hasAccessScriptExtras          = pjAuth::factory('pjAdminExtras', 'pjActionIndex')->hasAccess();
$hasAccessScriptFeatures          = pjAuth::factory('pjAdminFeatures', 'pjActionIndex')->hasAccess();

// Permissions - Vouchers
// Subscriptions: the plan of the active company decides what is shown (and allowed, see pjEntitlement).
$pjPlanCid = (int) pjCompanyContext::activeId($controller->getRoleId(), $controller->getUserId());
$pjPlanOn = function ($feature) use ($pjPlanCid) { return !class_exists('pjEntitlement') || pjEntitlement::allows($pjPlanCid, $feature); };
$hasAccessScriptSubscriptions     = $controller->isAdmin() && class_exists('pjEntitlement') && pjEntitlement::isReady();
$hasAccessScriptMyPlan            = (int) $controller->getRoleId() === 4 && class_exists('pjEntitlement') && pjEntitlement::isReady() && pjAuth::factory('pjAdminSubscriptions', 'pjActionMyPlan')->hasAccess();
$isScriptSubPayments              = $controller->_get->toString('controller') == 'pjAdminSubscriptions' && in_array($controller->_get->toString('action'), array('pjActionGateways', 'pjActionGatewayOptions'), true);
$isScriptSubscriptions            = $controller->_get->toString('controller') == 'pjAdminSubscriptions' && !$isScriptSubPayments;
$hasAccessScriptVouchers          = pjAuth::factory('pjAdminVouchers', 'pjActionIndex')->hasAccess() && ($controller->isAdmin() || $pjPlanOn('coupons'));

// Permissions - Discounts
$hasAccessScriptDiscounts          = pjAuth::factory('pjAdminDiscounts', 'pjActionIndex')->hasAccess() && ($controller->isAdmin() || $pjPlanOn('coupons'));

$hasDemoData = pjCalendarModel::factory()->reset()->where('t1.uuid LIKE', 'rpbcd%')->where('t1.company_id', pjCompanyContext::activeId($controller->getRoleId(), $controller->getUserId()))->findCount()->getData() > 0;

?>

<?php
// ---- Active company (workspace) switcher ----
$pjcsRole = (int) $controller->getRoleId();
$pjcsUser = (int) $controller->getUserId();
$pjcsList = pjCompanyContext::allowedCompanies($pjcsRole, $pjcsUser);
$pjcsActive = pjCompanyContext::activeCompany($pjcsRole, $pjcsUser);
$pjcsToken = pjAppController::getCsrfToken();
?>
<li class="pjcs-workspace">
    <div class="pjcs-card">
        <span class="pjcs-eyebrow"><?php __('lblActiveWorkspace'); ?></span>
        <?php if ($pjcsActive): ?>
            <span class="pjcs-name" title="<?php echo pjSanitize::html($pjcsActive['name']); ?>"><?php echo pjSanitize::html($pjcsActive['name']); ?></span>
        <?php else: ?>
            <span class="pjcs-name pjcs-empty"><?php __('lblNoCompanyYet'); ?></span>
        <?php endif; ?>
    </div>
</li>
<?php if (pjCompanyContext::canSwitch($pjcsRole, $pjcsUser)): ?>
    <li class="pjcs-switch">
        <a href="#" class="pjcs-toggle"><i class="fa fa-exchange"></i> <span class="nav-label"><?php __('lblSetCompany'); ?></span><span class="fa arrow"></span></a>
        <ul class="nav nav-second-level collapse pjcs-list">
            <?php foreach ($pjcsList as $pjcsId => $pjcsRow): $pjcsIsActive = $pjcsActive && (int) $pjcsActive['id'] === (int) $pjcsId; ?>
                <li<?php echo $pjcsIsActive ? ' class="pjcs-active"' : ''; ?>>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminCompanies&amp;action=pjActionSwitch&amp;id=<?php echo pjCompanyContext::hashOf($pjcsId); ?>&amp;csrf_token=<?php echo $pjcsToken; ?>">
                        <i class="fa <?php echo $pjcsIsActive ? 'fa-check' : 'fa-circle-o'; ?>"></i>
                        <span><?php echo pjSanitize::html($pjcsRow['name']); ?></span>
                        <?php if ($pjcsRow['status'] != 'T'): ?><small class="pjcs-off"><?php __('filter_ARRAY_inactive'); ?></small><?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </li>
<?php endif; ?>

<?php if ($hasDemoData && $hasAccessScriptCalendars): ?>
    <li class="pj-demo-remove">
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminSetup&amp;action=pjActionWelcome"><i class="fa fa-trash"></i> <span class="nav-label"><?php __('lblRemoveDemoData');?></span></a>
    </li>
<?php endif; ?>

<?php if ($hasAccessScriptDashboard): ?>
    <li<?php echo $isScriptDashboard ? ' class="active"' : NULL; ?>>
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdmin&amp;action=pjActionIndex"><i class="fa fa-th-large"></i> <span class="nav-label"><?php __('plugin_base_menu_dashboard');?></span></a>
    </li>
<?php endif; ?>

<?php if ($hasAccessScriptCalendars): ?>
    <li<?php echo $isScriptCalendars ? ' class="active"' : NULL; ?>>
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminCalendars&amp;action=pjActionIndex" class="pjTsCheckAssignCalendar"><i class="fa fa-calendar"></i> <span class="nav-label"><?php __('menuCalendars');?></span></a>
    </li>
<?php endif; ?>

<?php if ($hasAccessScriptReservations || $hasAccessScriptSchedule): ?>
    <li<?php echo $isScriptReservations ? ' class="active"' : NULL; ?>>
        <a href="#"><i class="fa fa-list"></i> <span class="nav-label"><?php __('menuReservations');?></span><span class="fa arrow"></span></a>
        <ul class="nav nav-second-level collapse">
            <?php if ($hasAccessScriptSchedule): ?>
                <li<?php echo $isScriptScheduleController ? ' class="active"' : NULL; ?>><a href="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminReservations&amp;action=pjActionSchedule"><?php __('menuSchedule');?></a></li>
            <?php endif; ?>

            <?php if ($hasAccessScriptReservations): ?>
                <li<?php echo $isScriptReservationsController ? ' class="active"' : NULL; ?>><a href="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminReservations&amp;action=pjActionIndex"><?php __('menuReservationsList');?></a></li>
            <?php endif; ?>
        </ul>
    </li>
<?php endif; ?>

<?php if ($hasAccessScriptCompanies): ?>
    <li<?php echo $isScriptCompanies ? ' class="active"' : NULL; ?>>
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminCompanies&amp;action=pjActionIndex"><i class="fa fa-building"></i> <span class="nav-label"><?php __('menuCompanies');?></span></a>
    </li>
<?php endif; ?>

<?php if ($hasAccessScriptSubscriptions):
    // Subscriptions: company subscriptions and billing history; Plans and Coupons & Discounts are their own items below
    $subAction = $controller->_get->toString('action');
    $subPages = array(
        'sub_tab_companies' => array('pjActionIndex', array('pjActionIndex', 'pjActionManage')),
        'sub_tab_billing'   => array('pjActionBilling', array('pjActionBilling')),
    );
    $isScriptSubPlans  = $isScriptSubscriptions && in_array($subAction, array('pjActionPlans', 'pjActionPlan'), true);
    $isScriptSubPromos = $isScriptSubscriptions && in_array($subAction, array('pjActionPromos', 'pjActionPromo'), true);
    $isScriptSubGroup  = $isScriptSubscriptions && !$isScriptSubPlans && !$isScriptSubPromos;
    ?>
    <li<?php echo $isScriptSubGroup ? ' class="active"' : NULL; ?>>
        <a href="#"><i class="fa fa-credit-card"></i> <span class="nav-label"><?php __('sub_menu');?></span><span class="fa arrow"></span></a>
        <ul class="nav nav-second-level collapse<?php echo $isScriptSubGroup ? ' in' : NULL; ?>">
            <?php foreach ($subPages as $subLabel => $subPage): ?>
                <li<?php echo $isScriptSubGroup && in_array($subAction, $subPage[1], true) ? ' class="active"' : NULL; ?>><a href="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminSubscriptions&amp;action=<?php echo $subPage[0]; ?>"><?php __($subLabel);?></a></li>
            <?php endforeach; ?>
        </ul>
    </li>
    <li<?php echo $isScriptSubPlans ? ' class="active"' : NULL; ?>>
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminSubscriptions&amp;action=pjActionPlans"><i class="fa fa-cubes"></i> <span class="nav-label"><?php __('sub_tab_plans');?></span></a>
    </li>
    <li<?php echo $isScriptSubPromos ? ' class="active"' : NULL; ?>>
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminSubscriptions&amp;action=pjActionPromos"><i class="fa fa-ticket"></i> <span class="nav-label"><?php __('sub_tab_promos');?></span></a>
    </li>
<?php endif; ?>


<?php if ($hasAccessScriptMyPlan): ?>
    <li<?php echo $isScriptSubscriptions ? ' class="active"' : NULL; ?>>
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminSubscriptions&amp;action=pjActionMyPlan"><i class="fa fa-star"></i> <span class="nav-label"><?php __('sub_myplan_title');?></span></a>
    </li>
<?php endif; ?>

<?php if ($hasAccessScriptMyCompany): ?>
    <li<?php echo $isScriptMyCompany ? ' class="active"' : NULL; ?>>
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminCompanies&amp;action=pjActionProfile"><i class="fa fa-building-o"></i> <span class="nav-label"><?php __('menuMyCompany');?></span></a>
    </li>
<?php endif; ?>

<?php
// Coupons & Discounts of the active company (bookings): inside Company options. The administrator's
// coupons & discounts on plan prices are under Subscriptions.
$hasCouponsAccess = $hasAccessScriptVouchers || $hasAccessScriptDiscounts;
$isScriptCoupons = $isScriptVouchers || $isScriptDiscounts;
?>
<?php if ($hasAccessScriptExtras || $hasAccessScriptFeatures || $hasAccessScriptCompanyGeneral || $hasCouponsAccess): ?>
    <li<?php echo $isScriptExtras || $isScriptFeatures || $isScriptCompanyGeneral || $isScriptCoupons ? ' class="active"' : NULL; ?>>
        <a href="#"><i class="fa fa-sliders"></i> <span class="nav-label"><?php __('menuCompanyOptions');?></span><span class="fa arrow"></span></a>
        <ul class="nav nav-second-level collapse<?php echo $isScriptExtras || $isScriptFeatures || $isScriptCompanyGeneral || $isScriptCoupons ? ' in' : NULL; ?>">
            <?php if ($hasAccessScriptExtras): ?>
                <li<?php echo $isScriptExtras ? ' class="active"' : NULL; ?>><a href="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminExtras&amp;action=pjActionIndex"><?php __('menuExtras');?></a></li>
            <?php endif; ?>
            <?php if ($hasAccessScriptFeatures): ?>
                <li<?php echo $isScriptFeatures ? ' class="active"' : NULL; ?>><a href="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminFeatures&amp;action=pjActionIndex"><?php __('menuFeatures');?></a></li>
            <?php endif; ?>
            <?php if ($hasAccessScriptCompanyGeneral): ?>
                <li<?php echo $isScriptCompanyGeneral ? ' class="active"' : NULL; ?>><a href="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjBaseOptions&amp;action=pjActionIndex"><?php __('plugin_base_menu_general');?></a></li>
            <?php endif; ?>
            <?php if ($hasCouponsAccess): ?>
                <li<?php echo $isScriptCoupons ? ' class="active"' : NULL; ?>><a href="<?php echo $_SERVER['PHP_SELF']; ?>?controller=<?php echo $hasAccessScriptDiscounts ? 'pjAdminDiscounts' : 'pjAdminVouchers'; ?>&amp;action=pjActionIndex"><?php __('menuCouponsDiscounts');?></a></li>
            <?php endif; ?>
        </ul>
    </li>
<?php endif; ?>

<?php if ($hasAccessScriptOptionsNotifications || $hasAccessScriptSubscriptions || $hasAccessScriptOptionsAi): ?>
    <li<?php echo $isScriptOptionsNotifications || $isScriptSubPayments || $isScriptOptionsAi ? ' class="active"' : NULL; ?>>
        <a href="#"><i class="fa fa-cogs"></i> <span class="nav-label"><?php __('menuOptions');?></span><span class="fa arrow"></span></a>
        <ul class="nav nav-second-level collapse<?php echo $isScriptOptionsNotifications || $isScriptSubPayments || $isScriptOptionsAi ? ' in' : NULL; ?>">
            <?php if ($hasAccessScriptOptionsNotifications): ?>
                <li<?php echo $isScriptOptionsNotifications ? ' class="active"' : NULL; ?>><a href="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminOptions&amp;action=pjActionNotifications"><?php __('menuNotifications');?></a></li>
            <?php endif; ?>
            <?php if ($hasAccessScriptSubscriptions): ?>
                <li<?php echo $isScriptSubPayments ? ' class="active"' : NULL; ?>><a href="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminSubscriptions&amp;action=pjActionGateways"><?php __('sub_menu_payments');?></a></li>
            <?php endif; ?>
            <?php if ($hasAccessScriptOptionsAi): ?>
                <li<?php echo $isScriptOptionsAi ? ' class="active"' : NULL; ?>><a href="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminOptions&amp;action=pjActionAi"><?php __('ai_menu');?></a></li>
            <?php endif; ?>

        </ul>
    </li>
<?php endif; ?>
