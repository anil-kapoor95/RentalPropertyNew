<div class="row wrapper border-bottom white-bg page-heading">
	<div class="col-sm-12">
		<h2><?php __($heading_title); ?></h2>
		<p class="m-b-none"><i class="fa fa-info-circle"></i> <?php __($heading_desc); ?></p>
	</div>
</div>
<?php if (!empty($heading_tabs)) { ?>
<div class="sub-tabs">
	<?php foreach ($heading_tabs as $act => $lbl) { ?>
	<a class="<?php echo $controller->_get->toString('action') === $act || (isset($heading_active) && $heading_active === $act) ? 'active' : ''; ?>" href="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminSubscriptions&amp;action=<?php echo $act; ?>"><?php __($lbl); ?></a>
	<?php } ?>
</div>
<?php } ?>
