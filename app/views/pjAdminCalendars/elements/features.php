<div id="features" class="tab-pane<?php echo $active_tab == 'features' ? ' active' : NULL;?>">
	<div class="panel-body form-horizontal">
		<div class="panel-body-inner">

			<div class="alert alert-success"><?php __('infoFeatureTabDesc'); ?></div>

			<form action="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminCalendars&amp;action=pjActionUpdate" method="post" id="frmCalendarFeatures" class="form pj-form">
				<input type="hidden" name="property_update" value="1" />
				<input type="hidden" name="csrf_token" value="<?php echo pjAppController::getCsrfToken(); ?>" />
				<input type="hidden" name="tab" value="features" />
				<input type="hidden" name="tab_id" value="16" />

				<?php
				$feature_groups = array(
					2 => $tpl['property_feature_arr'],
					1 => $tpl['community_feature_arr'],
				);
				foreach ($feature_groups as $cat_id => $group) :
					if (empty($group)) { continue; }
				?>
				<h4 class="m-t-none"><?php echo isset($tpl['feature_categories'][$cat_id]) ? pjSanitize::html($tpl['feature_categories'][$cat_id]) : NULL; ?></h4>
				<div class="row">
					<?php foreach ($group as $v) : ?>
					<div class="col-lg-4 col-md-4 col-sm-6">
						<div>
							<label class="clearfix">
								<input type="checkbox" class="i-checks" name="feature[]" value="<?php echo (int) $v['id']; ?>"<?php echo in_array($v['id'], $tpl['calendar_feature_id_arr']) ? ' checked="checked"' : NULL; ?>>
								<?php echo pjSanitize::html($v['name']); ?>
							</label>
						</div>
					</div>
					<?php endforeach; ?>
				</div>
				<div class="hr-line-dashed"></div>
				<?php endforeach; ?>

				<?php if (empty($tpl['property_feature_arr']) && empty($tpl['community_feature_arr'])) : ?>
				<p class="text-muted"><?php __('infoFeatureTabDesc'); ?></p>
				<?php endif; ?>

				<div class="clearfix">
					<button type="submit" class="ladda-button btn btn-primary btn-lg btn-phpjabbers-loader pull-left" data-style="zoom-in" style="margin-right: 15px;">
						<span class="ladda-label"><?php __('btnSave'); ?></span>
						<?php include $controller->getConstant('pjBase', 'PLUGIN_VIEWS_PATH') . 'pjLayouts/elements/button-animation.php'; ?>
					</button>
					<a type="button" class="btn btn-white btn-lg pull-right" href="<?php echo PJ_INSTALL_URL; ?>index.php?controller=pjAdminCalendars&action=pjActionIndex"><?php __('btnCancel'); ?></a>
				</div>

			</form>

		</div>
	</div>
</div>
