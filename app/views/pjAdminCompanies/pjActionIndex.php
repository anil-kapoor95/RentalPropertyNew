<?php
$filter = __('filter', true);
$heading_title = 'infoCompaniesTitle';
$heading_desc = 'infoCompaniesDesc';
include dirname(__FILE__) . '/elements/heading.php';
?>
<div class="row wrapper wrapper-content animated fadeInRight">
	<div class="col-lg-12">
		<?php include dirname(__FILE__) . '/elements/messages.php'; ?>
		<div class="ibox float-e-margins">
			<div class="ibox-content">
				<div class="row">
					<?php if ($tpl['has_create']) { ?>
					<div class="col-lg-4 col-md-3 col-sm-3">
						<div class="form-group">
							<a href="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminCompanies&amp;action=pjActionCreate" class="btn btn-primary m-r-xs"><i class="fa fa-plus m-r-xs"></i><?php __('btnAddCompany'); ?></a>
						</div>
					</div>
					<?php } ?>
					<div class="col-lg-4 col-md-5 col-sm-4">
						<form action="" method="get" class="form-horizontal frm-filter">
							<div class="input-group">
								<input type="text" name="q" placeholder="<?php __('btnSearch', false, true); ?>" class="form-control">
								<div class="input-group-btn">
									<button class="btn btn-primary" type="submit"><i class="fa fa-search"></i></button>
								</div>
							</div>
						</form>
					</div>
					<div class="col-lg-4 col-md-4 col-sm-5 text-right">
						<div class="m-b-md m-t-xs">
							<button class="btn btn-primary btn-all active" type="button"><span class="p-w-xs"><?php __('lblAll'); ?></span></button>
							<button class="btn btn-default btn-filter" type="button" data-column="status" data-value="T"><i class="fa fa-check"></i> <?php echo $filter['active']; ?></button>
							<button class="btn btn-default btn-filter" type="button" data-column="status" data-value="F"><i class="fa fa-times"></i> <?php echo $filter['inactive']; ?></button>
						</div>
					</div>
				</div>
				<div id="grid"></div>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
var pjGrid = pjGrid || {};
pjGrid.queryString = "";
var myLabel = myLabel || {};
myLabel.name = <?php x__encode('lblCompanyName'); ?>;
myLabel.email = <?php x__encode('lblCompanyEmail'); ?>;
myLabel.phone = <?php x__encode('lblCompanyPhone'); ?>;
myLabel.city = <?php x__encode('lblCompanyCity'); ?>;
myLabel.user = <?php x__encode('lblCompanyUser'); ?>;
myLabel.status = <?php x__encode('lblStatus'); ?>;
myLabel.active = <?php x__encode('filter_ARRAY_active'); ?>;
myLabel.inactive = <?php x__encode('filter_ARRAY_inactive'); ?>;
myLabel.delete_selected = <?php x__encode('plugin_base_delete_selected'); ?>;
myLabel.delete_confirmation = <?php x__encode('plugin_base_delete_confirmation'); ?>;
myLabel.has_update = <?php echo (int) $tpl['has_update']; ?>;
myLabel.has_delete = <?php echo (int) $tpl['has_delete']; ?>;
myLabel.has_delete_bulk = <?php echo (int) $tpl['has_delete_bulk']; ?>;
</script>
