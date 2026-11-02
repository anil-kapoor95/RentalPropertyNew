<?php
$cfg = $tpl['cfg'];
$err = $controller->_get->toString('err');
$bad = array_filter(explode(',', (string) $controller->_get->toString('bad')));
$cats = is_array($tpl['feature_categories']) ? $tpl['feature_categories'] : array();
?>
<div class="row wrapper border-bottom white-bg page-heading">
	<div class="col-sm-12">
		<h2><?php __('signup_cfg_title'); ?></h2>
		<p class="m-b-none"><i class="fa fa-info-circle"></i> <?php __('signup_cfg_desc'); ?></p>
	</div>
</div>

<div class="wrapper wrapper-content animated fadeInRight">
	<?php if ($err === 'SUS01') { ?>
		<div class="alert alert-success"><i class="fa fa-check m-r-xs"></i> <?php __('signup_cfg_saved'); ?></div>
	<?php } elseif ($err === 'SUS02') { ?>
		<div class="alert alert-danger"><i class="fa fa-exclamation-triangle m-r-xs"></i>
		<?php foreach ($bad as $name) { if (isset($cfg['numbers'][$name])) { $n = $cfg['numbers'][$name]; ?>
			<strong><?php __('signup_cfg_' . $name); ?>:</strong> <?php echo pjSanitize::html(sprintf(__('signup_cfg_range', true), $n['min'], $n['max'])); ?><br>
		<?php } } ?>
		</div>
	<?php } ?>

	<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminOptions&amp;action=pjActionSignupSettings" class="ibox float-e-margins" autocomplete="off">
		<div class="ibox-content">
			<input type="hidden" name="signup_save" value="1">
			<input type="hidden" name="csrf_token" value="<?php echo pjAppController::getCsrfToken(); ?>">
			<h3 class="m-t-none"><?php __('signup_cfg_limits'); ?></h3>
			<div class="row">
				<?php foreach ($cfg['numbers'] as $name => $n) { ?>
				<div class="col-md-4 col-sm-6 form-group<?php echo in_array($name, $bad, true) ? ' has-error' : ''; ?>">
					<label class="control-label" for="sus_<?php echo $name; ?>"><?php __('signup_cfg_' . $name); ?></label>
					<?php if ($name === 'amenity_category' && !empty($cats)) { ?>
						<select class="form-control" id="sus_<?php echo $name; ?>" name="<?php echo $name; ?>">
							<?php foreach ($cats as $id => $label) { ?>
							<option value="<?php echo (int) $id; ?>"<?php echo (int) $id === (int) $n['value'] ? ' selected' : ''; ?>><?php echo pjSanitize::html($label); ?></option>
							<?php } ?>
						</select>
					<?php } else { ?>
						<input class="form-control" type="number" id="sus_<?php echo $name; ?>" name="<?php echo $name; ?>" min="<?php echo (int) $n['min']; ?>" max="<?php echo (int) $n['max']; ?>" value="<?php echo (int) $n['value']; ?>" placeholder="<?php echo (int) $n['default']; ?>">
						<small class="text-muted"><?php echo pjSanitize::html(sprintf(__('signup_cfg_default', true), $n['default'])); ?> · <?php echo pjSanitize::html(sprintf(__('signup_cfg_range', true), $n['min'], $n['max'])); ?></small>
					<?php } ?>
				</div>
				<?php } ?>
			</div>

			<div class="hr-line-dashed"></div>
			<h3><?php __('signup_cfg_tones'); ?></h3>
			<p class="text-muted small"><?php __('signup_cfg_tones_hint'); ?></p>
			<?php foreach ($cfg['tones'] as $tone => $t) { ?>
			<div class="form-group">
				<label class="control-label" for="sus_tone_<?php echo $tone; ?>"><?php __('signup_cfg_tone_' . $tone); ?></label>
				<textarea class="form-control" id="sus_tone_<?php echo $tone; ?>" name="tone_<?php echo $tone; ?>" rows="2" maxlength="500" placeholder="<?php echo pjSanitize::html($t['default']); ?>"><?php echo pjSanitize::html($t['value']); ?></textarea>
			</div>
			<?php } ?>

			<p class="text-muted small"><i class="fa fa-info-circle"></i> <?php __('signup_cfg_fixed'); ?></p>
			<div class="hr-line-dashed"></div>
			<button class="btn btn-primary btn-lg" type="submit"><?php __('plugin_base_btn_save'); ?></button>
		</div>
	</form>
</div>
