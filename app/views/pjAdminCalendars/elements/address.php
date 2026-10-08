<div id="address" class="tab-pane<?php echo $active_tab == 'address' ? ' active' : NULL;?>">
	<div class="panel-body form-horizontal">
		<div class="panel-body-inner">

			<div class="alert alert-success"><?php __('lblAddressInfo'); ?></div>

			<form action="<?php echo $_SERVER['PHP_SELF']; ?>?controller=pjAdminCalendars&amp;action=pjActionUpdate" method="post" id="frmCalendarAddress" class="form pj-form">
				<input type="hidden" name="property_update" value="1" />
				<input type="hidden" name="csrf_token" value="<?php echo pjAppController::getCsrfToken(); ?>" />
				<input type="hidden" name="tab" value="address" />
				<input type="hidden" name="tab_id" value="14" />

				<div class="form-group">
					<label class="col-sm-3 control-label" for="show_googlemap"><?php __('lblShowOnMap'); ?>:</label>
					<div class="col-lg-5 col-sm-7">
						<div class="clearfix">
							<div class="switch onoffswitch-data pull-left">
								<div class="onoffswitch">
									<input class="onoffswitch-checkbox" id="show_googlemap" type="checkbox" name="show_googlemap" value="T"<?php echo (!isset($tpl['arr']['show_googlemap']) || $tpl['arr']['show_googlemap'] !== 'F') ? ' checked="checked"' : NULL; ?>>
									<label class="onoffswitch-label" for="show_googlemap">
									<span class="onoffswitch-inner" data-on="<?php __('plugin_base_yesno_ARRAY_T', false, true); ?>" data-off="<?php __('plugin_base_yesno_ARRAY_F', false, true); ?>"></span>
									<span class="onoffswitch-switch"></span>
									</label>
								</div>
							</div>
						</div>
					</div>
				</div>

				<div class="form-group">
					<label class="col-sm-3 control-label"><?php __('lblGoogleMapAddress'); ?>:</label>
					<div class="col-lg-5 col-sm-7">
						<div class="row">
							<div class="col-sm-10">
								<?php
								foreach ($tpl['lp_arr'] as $v)
								{
									?>
									<div class="<?php echo $tpl['is_flag_ready'] ? 'input-group ' : NULL;?>pj-multilang-wrap" data-index="<?php echo $v['id']; ?>" style="display: <?php echo (int) $v['is_default'] === 1 ? NULL : 'none'; ?>">
										<input type="text" class="form-control pjGoogleAddress" id="pjGoogleAddress_<?php echo $v['id']; ?>" data-default="<?php echo (int) $v['is_default']; ?>" name="i18n[<?php echo $v['id']; ?>][google_address]" value="<?php echo pjSanitize::html(@$tpl['arr']['i18n'][$v['id']]['google_address']); ?>" autocomplete="off">
										<?php if ($tpl['is_flag_ready']) : ?>
										<span class="input-group-addon pj-multilang-input"><img src="<?php echo PJ_INSTALL_URL . PJ_FRAMEWORK_LIBS_PATH . 'pj/img/flags/' . $v['file']; ?>" alt="<?php echo pjSanitize::html($v['name']); ?>"></span>
										<?php endif; ?>
									</div>
									<?php
								}
								?>
							</div>
						</div>
					</div>
				</div>

				<div class="form-group">
					<label class="col-sm-3 control-label"><?php __('lblAddress'); ?>:</label>
					<div class="col-lg-5 col-sm-7">
						<div class="row">
							<div class="col-sm-10">
								<?php
								foreach ($tpl['lp_arr'] as $v)
								{
									?>
									<div class="<?php echo $tpl['is_flag_ready'] ? 'input-group ' : NULL;?>pj-multilang-wrap" data-index="<?php echo $v['id']; ?>" style="display: <?php echo (int) $v['is_default'] === 1 ? NULL : 'none'; ?>">
										<input type="text" class="form-control" name="i18n[<?php echo $v['id']; ?>][address_1]" value="<?php echo pjSanitize::html(@$tpl['arr']['i18n'][$v['id']]['address_1']); ?>">
										<?php if ($tpl['is_flag_ready']) : ?>
										<span class="input-group-addon pj-multilang-input"><img src="<?php echo PJ_INSTALL_URL . PJ_FRAMEWORK_LIBS_PATH . 'pj/img/flags/' . $v['file']; ?>" alt="<?php echo pjSanitize::html($v['name']); ?>"></span>
										<?php endif; ?>
									</div>
									<?php
								}
								?>
							</div>
						</div>
					</div>
				</div>

				<div class="form-group">
					<label class="col-sm-3 control-label"><?php __('lblCity'); ?>:</label>
					<div class="col-lg-5 col-sm-7">
						<div class="row">
							<div class="col-sm-10">
								<?php
								foreach ($tpl['lp_arr'] as $v)
								{
									?>
									<div class="<?php echo $tpl['is_flag_ready'] ? 'input-group ' : NULL;?>pj-multilang-wrap" data-index="<?php echo $v['id']; ?>" style="display: <?php echo (int) $v['is_default'] === 1 ? NULL : 'none'; ?>">
										<input type="text" class="form-control pjAddressCity" id="pjAddressCity_<?php echo $v['id']; ?>" data-default="<?php echo (int) $v['is_default']; ?>" name="i18n[<?php echo $v['id']; ?>][address_city]" value="<?php echo pjSanitize::html(@$tpl['arr']['i18n'][$v['id']]['address_city']); ?>">
										<?php if ($tpl['is_flag_ready']) : ?>
										<span class="input-group-addon pj-multilang-input"><img src="<?php echo PJ_INSTALL_URL . PJ_FRAMEWORK_LIBS_PATH . 'pj/img/flags/' . $v['file']; ?>" alt="<?php echo pjSanitize::html($v['name']); ?>"></span>
										<?php endif; ?>
									</div>
									<?php
								}
								?>
							</div>
						</div>
					</div>
				</div>

				<div class="form-group">
					<label class="col-sm-3 control-label" for="address_state"><?php __('lblState'); ?>:</label>
					<div class="col-lg-5 col-sm-7">
						<div class="row">
							<div class="col-sm-10">
								<input type="text" name="address_state" id="address_state" class="form-control" value="<?php echo pjSanitize::html(@$tpl['arr']['address_state']); ?>">
							</div>
						</div>
					</div>
				</div>

				<div class="form-group">
					<label class="col-sm-3 control-label" for="address_zip"><?php __('lblZip'); ?>:</label>
					<div class="col-lg-5 col-sm-7">
						<div class="row">
							<div class="col-sm-10">
								<input type="text" name="address_zip" id="address_zip" class="form-control" value="<?php echo pjSanitize::html(@$tpl['arr']['address_zip']); ?>">
							</div>
						</div>
					</div>
				</div>

				<div class="form-group">
					<label class="col-sm-3 control-label" for="address_country"><?php __('lblCountry'); ?>:</label>
					<div class="col-lg-5 col-sm-7">
						<div class="row">
							<div class="col-sm-10">
								<select name="address_country" id="address_country" class="form-control select-item" data-placeholder="-- <?php __('lblChoose'); ?> --">
									<option value="">-- <?php __('lblChoose'); ?> --</option>
									<?php
									foreach ($tpl['country_arr'] as $v)
									{
										?><option value="<?php echo $v['id']; ?>"<?php echo (int) @$tpl['arr']['address_country'] === (int) $v['id'] ? ' selected="selected"' : NULL; ?>><?php echo pjSanitize::html($v['name']); ?></option><?php
									}
									?>
								</select>
							</div>
						</div>
					</div>
				</div>

				<div class="hr-line-dashed"></div>

				<div class="form-group">
					<label class="col-sm-3 control-label" for="lat"><?php __('lblLatitude'); ?>:</label>
					<div class="col-lg-5 col-sm-7">
						<div class="row">
							<div class="col-sm-10">
								<input type="text" name="lat" id="lat" class="form-control" value="<?php echo pjSanitize::html(@$tpl['arr']['lat']); ?>">
							</div>
						</div>
					</div>
				</div>

				<div class="form-group">
					<label class="col-sm-3 control-label" for="lng"><?php __('lblLongitude'); ?>:</label>
					<div class="col-lg-5 col-sm-7">
						<div class="row">
							<div class="col-sm-10">
								<input type="text" name="lng" id="lng" class="form-control" value="<?php echo pjSanitize::html(@$tpl['arr']['lng']); ?>">
								<small class="help-block m-b-none"><?php __('lblGMapNote'); ?></small>
							</div>
						</div>
					</div>
				</div>

				<div class="hr-line-dashed"></div>

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

	<?php $pj_gmap_key = isset($tpl['option_arr']['o_google_maps_api_key']) ? trim($tpl['option_arr']['o_google_maps_api_key']) : ''; ?>
	<?php if ($pj_gmap_key !== '') : ?>
	<script type="text/javascript">
	function pjInitAddressAutocomplete() {
		if (!(window.google && google.maps && google.maps.places)) { return; }

		var inputs = document.querySelectorAll('.pjGoogleAddress');
		for (var i = 0; i < inputs.length; i++) {
			(function (input) {
				var ac = new google.maps.places.Autocomplete(input, { types: ['geocode'] });

				// Stop Enter (used to pick a suggestion) from submitting the form and reloading the page.
				// Capture phase so this runs before Google's handler and the form's implicit submit.
				input.addEventListener('keydown', function (e) {
					if (e.key === 'Enter' || e.keyCode === 13) {
						e.preventDefault();
						e.stopPropagation();
						if (e.stopImmediatePropagation) { e.stopImmediatePropagation(); }
						return false;
					}
				}, true);
				// Extra guard: block a form submit that originates while this address input is focused.
				if (input.form) {
					input.form.addEventListener('submit', function (e) {
						if (document.activeElement === input) { e.preventDefault(); }
					}, true);
				}

				ac.addListener('place_changed', function () {
					var place = ac.getPlace();
					if (!place) { return; }

					// Coordinates
					if (place.geometry && place.geometry.location) {
						var lat = document.getElementById('lat');
						var lng = document.getElementById('lng');
						if (lat) { lat.value = place.geometry.location.lat(); }
						if (lng) { lng.value = place.geometry.location.lng(); }
					}

					// Break the address into parts
					var parts = { locality: '', admin1: '', postal: '', country: '' };
					var comps = place.address_components || [];
					for (var c = 0; c < comps.length; c++) {
						var t = comps[c].types || [];
						if (t.indexOf('locality') !== -1 || t.indexOf('postal_town') !== -1) { parts.locality = comps[c].long_name; }
						if (t.indexOf('administrative_area_level_1') !== -1) { parts.admin1 = comps[c].long_name; }
						if (t.indexOf('postal_code') !== -1) { parts.postal = comps[c].long_name; }
						if (t.indexOf('country') !== -1) { parts.country = comps[c].long_name; }
					}

					// Always reflect the newly selected address (clear stale values when a part is missing).
					var z = document.getElementById('address_zip'); if (z) { z.value = parts.postal; }
					var st = document.getElementById('address_state'); if (st) { st.value = parts.admin1; }
					var city = document.querySelector('.pjAddressCity[data-default="1"]'); if (city) { city.value = parts.locality; }
					// Match the country <select> by visible name (best effort)
					if (parts.country) {
						var sel = document.getElementById('address_country');
						if (sel) {
							for (var o = 0; o < sel.options.length; o++) {
								if (sel.options[o].text.trim().toLowerCase() === parts.country.trim().toLowerCase()) {
									sel.value = sel.options[o].value;
									if (window.jQuery && jQuery(sel).hasClass('select-item')) { try { jQuery(sel).trigger('change'); } catch (e) {} }
									break;
								}
							}
						}
					}
				});
			})(inputs[i]);
		}
	}
	</script>
	<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=<?php echo urlencode($pj_gmap_key); ?>&libraries=places&callback=pjInitAddressAutocomplete" async defer></script>
	<?php endif; ?>

</div>
