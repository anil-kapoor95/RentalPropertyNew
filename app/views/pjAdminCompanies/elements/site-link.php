<?php
/**
 * "Website address" box (My company and Companies > Edit): the company's links on the website and its own domain.
 * Data: $tpl['site_link'] from pjAdminCompanies::setSiteLink().
 */
if (empty($tpl['site_link']))
{
	return;
}
$sl = $tpl['site_link'];
$sl_status = in_array($sl['status'], array('active', 'pending', 'error'), true) ? $sl['status'] : 'pending';
$sl_badge = array('active' => 'label-primary', 'pending' => 'label-warning', 'error' => 'label-danger');
?>
<div class="ibox float-e-margins pj-site-link" id="pjSiteLink" data-id="<?php echo pjSanitize::html($sl['id']); ?>">
	<div class="ibox-title"><h5><i class="fa fa-globe m-r-xs"></i><?php __('lblSiteLinkTitle'); ?></h5></div>
	<div class="ibox-content">
		<p class="text-muted"><?php __('lblSiteLinkDesc'); ?></p>

		<div class="form-group">
			<label class="control-label"><?php __('lblSiteLinkDefault'); ?></label>
			<ul class="list-unstyled pj-site-link-list">
				<?php foreach ($sl['properties'] as $p) { ?>
				<li>
					<i class="fa fa-home text-muted m-r-xs"></i>
					<?php if ($p['name'] !== '') { ?><span class="m-r-xs"><?php echo pjSanitize::html($p['name']); ?>:</span><?php } ?>
					<a href="<?php echo pjSanitize::html($p['url']); ?>" target="_blank" rel="noopener"><?php echo pjSanitize::html(preg_replace('~^https?://~', '', $p['url'])); ?></a>
				</li>
				<?php } ?>
				<?php if (count($sl['properties']) > 1) { ?>
				<li>
					<i class="fa fa-building-o text-muted m-r-xs"></i>
					<a href="<?php echo pjSanitize::html($sl['company_url']); ?>" target="_blank" rel="noopener"><?php echo pjSanitize::html(preg_replace('~^https?://~', '', $sl['company_url'])); ?></a>
				</li>
				<?php } ?>
			</ul>
		</div>

		<div class="hr-line-dashed"></div>

		<div class="form-group m-b-none">
			<label class="control-label" for="pjSiteDomain"><?php __('lblSiteLinkDomain'); ?></label>
			<?php if (!$sl['allowed'] && $sl['domain'] === '') { ?>
				<p class="alert alert-info m-b-none"><i class="fa fa-lock m-r-xs"></i><?php __('lblSiteLinkLocked'); ?></p>
			<?php } else { ?>
				<div class="input-group" style="max-width: 520px;">
					<span class="input-group-addon">https://</span>
					<input type="text" class="form-control" id="pjSiteDomain" value="<?php echo pjSanitize::html($sl['domain']); ?>" placeholder="www.myvilla.com" autocomplete="off" spellcheck="false">
					<span class="input-group-btn">
						<button type="button" class="btn btn-primary pj-site-domain-save"><?php __('btnSave'); ?></button>
					</span>
				</div>
				<p class="help-block"><?php __('lblSiteLinkDomainHint'); ?></p>
				<p class="text-danger pj-site-domain-error" style="display: none;"></p>

				<?php if ($sl['domain'] !== '') { ?>
				<div class="pj-site-domain-state m-t-sm">
					<p>
						<span class="label <?php echo $sl_badge[$sl_status]; ?>"><?php __('lblSiteLinkStatus_' . $sl_status); ?></span>
						<a href="https://<?php echo pjSanitize::html($sl['domain']); ?>" target="_blank" rel="noopener" class="m-l-xs"><?php echo pjSanitize::html($sl['domain']); ?></a>
						<button type="button" class="btn btn-white btn-xs m-l-sm pj-site-domain-check"><i class="fa fa-refresh m-r-xs"></i><?php __('lblSiteLinkCheck'); ?></button>
						<?php if ($sl['checked'] !== '') { ?><small class="text-muted m-l-xs"><?php echo pjSanitize::html($sl['checked']); ?></small><?php } ?>
					</p>
					<?php if ($sl_status !== 'active') { ?>
						<?php if ($sl['message'] !== '') { ?><p class="text-muted m-b-xs"><?php echo pjSanitize::html($sl['message']); ?></p><?php } ?>
						<p class="alert alert-info"><i class="fa fa-info-circle m-r-xs"></i><?php __('lblSiteLinkManual'); ?></p>
					<?php } ?>
					<?php if ($sl_status !== 'active' && !empty($sl['dns'])) { ?>
					<p class="m-b-xs"><?php __('lblSiteLinkDns'); ?></p>
					<table class="table table-bordered table-condensed m-b-none" style="max-width: 520px;">
						<thead><tr><th><?php __('lblSiteLinkDnsType'); ?></th><th><?php __('lblSiteLinkDnsName'); ?></th><th><?php __('lblSiteLinkDnsValue'); ?></th></tr></thead>
						<tbody><tr>
							<td><code><?php echo pjSanitize::html($sl['dns']['type']); ?></code></td>
							<td><code><?php echo pjSanitize::html($sl['dns']['name']); ?></code></td>
							<td><code><?php echo pjSanitize::html($sl['dns']['value']); ?></code></td>
						</tr></tbody>
					</table>
					<?php } ?>
				</div>
				<?php } ?>
			<?php } ?>
		</div>
	</div>
</div>
