<?php
/**
 * Social links of the website (administrators only): one link per network, shown wherever the website shows
 * social icons. Included by pjActionVisual.php after the other options.
 */
$social_keys = array('o_social_instagram' => 'fa-instagram', 'o_social_facebook' => 'fa-facebook', 'o_social_linkedin' => 'fa-linkedin',
	'o_social_x' => 'fa-twitter', 'o_social_youtube' => 'fa-youtube-play');
$social_rows = array();
foreach ($tpl['arr'] as $row)
{
	if (isset($social_keys[$row['key']]))
	{
		$social_rows[$row['key']] = $row;
	}
}
if (empty($social_rows) || !(method_exists($controller, 'isAdmin') && $controller->isAdmin()))
{
	return;
}
?>
<div class="hr-line-dashed"></div>
<div class="row">
	<div class="col-lg-9 col-lg-offset-3 col-md-8 col-md-offset-4">
		<h3 class="m-b-xs"><i class="fa fa-share-alt m-r-xs"></i><?php __('lblSocialTitle'); ?></h3>
		<p class="text-muted m-b-md"><?php __('lblSocialDesc'); ?></p>
	</div>
</div>
<?php foreach ($social_keys as $key => $icon) {
	if (!isset($social_rows[$key])) { continue; }
	$row = $social_rows[$key];
	?>
<div class="form-group">
	<label class="col-lg-3 col-md-4 control-label" for="social-<?php echo $key; ?>"><?php __('plugin_base_opt_' . $key); ?></label>
	<div class="col-sm-9 col-md-8">
		<div class="input-group">
			<span class="input-group-addon"><i class="fa <?php echo $icon; ?>"></i></span>
			<input type="url" id="social-<?php echo $key; ?>" name="value-string-<?php echo $key; ?>" class="form-control pj-social-url"
				value="<?php echo pjSanitize::html($row['value']); ?>" placeholder="https://" pattern="https?://.+" title="<?php __('lblSocialInvalid', false, true); ?>">
		</div>
	</div>
</div>
<?php } ?>
