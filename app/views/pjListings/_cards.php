<?php
if (!defined('ROOT_PATH')) { header('HTTP/1.1 403 Forbidden'); exit; }
/**
 * Renders the property cards for $items. Shared by the listing page and the
 * JS embed so the card markup lives in exactly one place.
 * Expects: $items (array), $tpl['base'], $controller.
 */
$base    = $tpl['base'];
$icoBed  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9v11M2 13h20v7M22 13v7M4 9h10a2 2 0 0 1 2 2M8 9V7a2 2 0 0 1 2-2h2"/></svg>';
$icoBath = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12V6a2 2 0 0 1 2-2 2 2 0 0 1 2 2M3 12h18v3a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4v-3ZM6 20l-1 2M18 20l1 2M8 6h.01"/></svg>';
$icoArrow= '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';
$icoPin  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-5.2-7-11a7 7 0 1 1 14 0c0 5.8-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>';

if (empty($items)):
?>
<p class="rpbc-empty"><?php echo __('lblNoProperties', true, true); ?></p>
<?php
else: foreach ($items as $it):
	$url  = $base . 'index.php?controller=pjListings&action=pjActionListingView&cid=' . $it['id'];
	$name = pjSanitize::html($it['name'] !== '' ? $it['name'] : (__('lblPropertyFallback', true, true) . ' #' . $it['id']));
	$location = pjSanitize::html(trim(implode(', ', array_filter(array($it['city'], $it['state'])))));
?>
<article class="rpbc-card">
	<a class="rpbc-ph" href="<?php echo $url; ?>"><?php if ($it['img'] !== ''): ?><img src="<?php echo $base . $it['img']; ?>" alt="<?php echo $name; ?>" loading="lazy"><?php endif; ?></a>
	<div class="rpbc-body">
		<h3><a href="<?php echo $url; ?>"><?php echo $name; ?></a></h3>
		<?php if ($location !== ''): ?><div class="rpbc-cloc"><?php echo $icoPin . $location; ?></div><?php endif; ?>
		<?php if ($it['beds'] !== null || $it['baths'] !== null): ?>
		<div class="rpbc-specs">
			<?php if ($it['beds']  !== null): ?><span class="rpbc-spec"><?php echo $icoBed  . $it['beds']; ?></span><?php endif; ?>
			<?php if ($it['baths'] !== null): ?><span class="rpbc-spec"><?php echo $icoBath . $it['baths']; ?></span><?php endif; ?>
		</div>
		<?php endif; ?>
		<div class="rpbc-foot"><a class="rpbc-viewbtn" href="<?php echo $url; ?>"><?php echo __('lblViewDetails', true, true) . ' ' . $icoArrow; ?></a></div>
	</div>
</article>
<?php endforeach; endif; ?>
