<?php
/**
 * Coupons & Discounts of a company: heading, messages, Discounts / Coupons switch, "Add" button and hint.
 * Same layout as the Super Admin's Coupons & Discounts (views/pjAdminSubscriptions/pjActionPromos.php).
 * Expects $tpl['cd'] (pjAppController::cdPageData) and $cd_add (true: show the Add button).
 */
$cd = $tpl['cd'];
$cd_kind = $cd['active'];
$cd_url = $_SERVER['PHP_SELF'] . '?controller=';
$cd_segments = array(
	'discount' => array('icon' => 'fa-percent', 'label' => 'sub_promo_tab_discounts', 'controller' => 'pjAdminDiscounts'),
	'coupon'   => array('icon' => 'fa-ticket', 'label' => 'sub_promo_tab_coupons', 'controller' => 'pjAdminVouchers'),
);
$cd_add_label = $cd_kind === 'coupon' ? 'sub_btn_add_coupon' : 'sub_btn_add_discount';
$cd_add_url = $cd_url . $cd_segments[$cd_kind]['controller'] . '&amp;action=pjActionCreate';
?>
<div class="row wrapper border-bottom white-bg page-heading">
	<div class="col-sm-12">
		<h2><?php __('sub_tab_promos'); ?></h2>
		<p class="m-b-none"><i class="fa fa-info-circle"></i> <?php __('cd_desc'); ?></p>
	</div>
</div>
<div class="wrapper wrapper-content animated fadeInRight">
	<?php include dirname(__FILE__) . '/coupons-messages.php'; ?>
	<div class="ibox float-e-margins"><div class="ibox-content sub-promos">
		<div class="sub-promo-bar">
			<div class="sub-seg" role="tablist">
				<?php foreach ($cd_segments as $k => $seg) { if (empty($cd['show'][$k])) { continue; } ?>
				<a class="sub-seg-item<?php echo $cd_kind === $k ? ' active' : ''; ?>" role="tab" aria-selected="<?php echo $cd_kind === $k ? 'true' : 'false'; ?>" href="<?php echo $cd_url . $seg['controller']; ?>&amp;action=pjActionIndex">
					<i class="fa <?php echo $seg['icon']; ?>"></i> <?php __($seg['label']); ?>
					<span class="sub-seg-count"><?php echo (int) $cd['counts'][$k]; ?></span>
				</a>
				<?php } ?>
			</div>
			<?php if (!empty($cd_add)) { ?>
			<a class="btn btn-primary" href="<?php echo $cd_add_url; ?>"><i class="fa fa-plus m-r-xs"></i><?php __($cd_add_label); ?></a>
			<?php } ?>
		</div>
		<p class="sub-promo-hint"><i class="fa fa-info-circle"></i> <?php __('cd_hint'); ?></p>
