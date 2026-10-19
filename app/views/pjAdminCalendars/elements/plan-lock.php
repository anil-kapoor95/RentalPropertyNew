<?php
/**
 * Notice on a property tab the subscription plan does not include.
 * Expects $plan_feature. Saving is refused by the controller as well.
 */
if (class_exists('pjEntitlement') && !pjEntitlement::allows($controller->getCompanyId(), $plan_feature)) { ?>
	<div class="alert alert-warning sub-lock"><i class="fa fa-lock m-r-xs"></i> <?php __('subLockedTab'); ?>
	<?php if ((int) $controller->getRoleId() === 4) { ?> <a href="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminSubscriptions&amp;action=pjActionMyPlan"><?php __('sub_btn_see_plans'); ?></a><?php } ?></div>
<?php }
