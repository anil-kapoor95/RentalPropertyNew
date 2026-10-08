<?php
if (!defined('ROOT_PATH')) { header('HTTP/1.1 403 Forbidden'); exit; }

$items = $tpl['items'];
ob_start();
include PJ_INSTALL_PATH . 'app/views/pjListings/_cards.php';
$cards = ob_get_clean();

$html = '<div class="rpbc"><div class="rpbc-head">'
      . '<span class="rpbc-eyebrow">' . __('lblAvailableNow', true, true) . '</span>'
      . '<h1>' . pjSanitize::html($tpl['company']) . '</h1>'
      . '<p>' . __('lblBrowseProperties', true, true) . '</p></div>'
      . '<div class="rpbc-grid">' . $cards . '</div></div>';

$sheets = array($tpl['themeCss'], $tpl['listCss']);
?>
(function () {
	var html = <?php echo json_encode($html); ?>;
	var sheets = <?php echo json_encode($sheets); ?>;
	function addCss(href) {
		for (var i = 0; i < document.styleSheets.length; i++) { if (document.styleSheets[i].href === href) { return; } }
		var l = document.createElement('link');
		l.rel = 'stylesheet'; l.type = 'text/css'; l.href = href;
		(document.head || document.getElementsByTagName('head')[0]).appendChild(l);
	}
	for (var i = 0; i < sheets.length; i++) { addCss(sheets[i]); }
	var host = document.getElementById('rpbcProperties');
	if (host) {
		host.innerHTML = html;
	} else {
		var cs = document.currentScript;
		if (!cs) { var ss = document.getElementsByTagName('script'); cs = ss[ss.length - 1]; }
		var wrap = document.createElement('div');
		wrap.innerHTML = html;
		if (cs && cs.parentNode) { cs.parentNode.insertBefore(wrap.firstChild, cs.nextSibling); }
		else { (document.body || document.documentElement).appendChild(wrap.firstChild); }
	}
})();
