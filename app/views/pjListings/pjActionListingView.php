<?php
if (!defined('ROOT_PATH')) { header('HTTP/1.1 403 Forbidden'); exit; }

if (!empty($tpl['notFound'])) {
	header('HTTP/1.1 404 Not Found');
	echo '<!DOCTYPE html><meta charset="UTF-8"><link rel="stylesheet" href="' . $tpl['cssUrl'] . '"><p class="rpbc-404">' . __('lblNotFound', true, true) . '</p>';
	return;
}

$base = $tpl['base'];
$p    = $tpl['prop'];

$name = $p['name'] !== '' ? pjSanitize::html($p['name']) : (__('lblPropertyFallback', true, true) . ' #' . $p['id']);
$location = pjSanitize::html(trim(implode(', ', array_filter(array($p['city'], $p['state'])))));

/* icons */
$svgBack  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>';
$svgPin   = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-5.2-7-11a7 7 0 1 1 14 0c0 5.8-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/></svg>';
$svgBed   = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 9v11M2 13h20v7M22 13v7M4 9h10a2 2 0 0 1 2 2M8 9V7a2 2 0 0 1 2-2h2"/></svg>';
$svgBath  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12V6a2 2 0 0 1 2-2 2 2 0 0 1 2 2M3 12h18v3a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4v-3ZM6 20l-1 2M18 20l1 2M8 6h.01"/></svg>';
$svgChev  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9l6 6 6-6"/></svg>';
$svgDown  = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M19 12l-7 7-7-7"/></svg>';
$svgCheck = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';

$featBlock = function ($items) use ($svgCheck) {
	$o = '';
	foreach ($items as $it) { $o .= '<span class="rpbc-feat"><i>' . $svgCheck . '</i>' . pjSanitize::html($it) . '</span>'; }
	return $o;
};
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php $seo = isset($tpl['seo']) ? $tpl['seo'] : array(); ?>
<title><?php echo (!empty($seo['title']) ? pjSanitize::html($seo['title']) : $name); ?></title>
<?php if (!empty($seo['description'])): ?><meta name="description" content="<?php echo pjSanitize::html($seo['description']); ?>">
<?php endif; ?><?php if (!empty($seo['keywords'])): ?><meta name="keywords" content="<?php echo pjSanitize::html($seo['keywords']); ?>">
<?php endif; ?>
<link rel="stylesheet" href="<?php echo $tpl['themeCss']; ?>">
<link rel="stylesheet" href="<?php echo $tpl['loadCss']; ?>">
<link rel="stylesheet" href="<?php echo $tpl['cssUrl']; ?>">
</head>
<body class="rpbc">
<div class="rpbc-wrap">
    <a class="rpbc-back" href="<?php echo $tpl['listUrl']; ?>"><?php echo $svgBack . ' ' . __('lblAllProperties', true, true); ?></a>

    <div class="rpbc-dhead">
        <h1><?php echo $name; ?></h1>
        <?php if ($location !== ''): ?><div class="rpbc-loc"><?php echo $svgPin . $location; ?></div><?php endif; ?>
        <?php if ($p['beds'] !== null || $p['baths'] !== null): ?>
        <div class="rpbc-specrow">
            <?php if ($p['beds']  !== null): ?><span class="rpbc-spec"><i><?php echo $svgBed;  ?></i><b><?php echo $p['beds'];  ?></b> <?php echo __('lblBedrooms', true, true); ?></span><?php endif; ?>
            <?php if ($p['baths'] !== null): ?><span class="rpbc-spec"><i><?php echo $svgBath; ?></i><b><?php echo $p['baths']; ?></b> <?php echo __('lblBathrooms', true, true); ?></span><?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="rpbc-split">
        <div class="rpbc-gcol">
            <div class="rpbc-gmain"><?php if (!empty($tpl['gallery'])): ?><img id="pjMainImg" src="<?php echo $base . $tpl['gallery'][0]['large']; ?>" alt="<?php echo $name; ?>"><?php endif; ?></div>
            <?php if (count($tpl['gallery']) > 1): ?>
            <div class="rpbc-thumbs">
                <?php foreach ($tpl['gallery'] as $ph): ?>
                <button type="button" class="rpbc-thumb" onclick="document.getElementById('pjMainImg').src='<?php echo $base . $ph['large']; ?>'"><img src="<?php echo $base . $ph['small']; ?>" alt=""></button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="rpbc-dcol">
            <?php if ($p['desc'] !== ''):
                $descHtml = nl2br(pjSanitize::html(strip_tags($p['desc'])));
                $long = strlen(strip_tags($p['desc'])) > 420;
            ?>
            <details class="rpbc-acc" open>
                <summary><?php echo __('lblAboutStay', true, true); ?> <span class="chev"><?php echo $svgChev; ?></span></summary>
                <div class="rpbc-acc-b">
                    <?php if ($long): ?>
                    <input type="checkbox" id="pjMore" class="rpbc-more-toggle">
                    <div class="rpbc-clamp"><p><?php echo $descHtml; ?></p></div>
                    <label for="pjMore" class="rpbc-more-btn" data-more="<?php echo __('lblReadMore', true, true); ?>" data-less="<?php echo __('lblShowLess', true, true); ?>"></label>
                    <?php else: ?>
                    <p><?php echo $descHtml; ?></p>
                    <?php endif; ?>
                </div>
            </details>
            <?php endif; ?>

            <?php if (!empty($tpl['propFeat'])): ?>
            <details class="rpbc-acc">
                <summary><?php echo __('lblPropertyFeatures', true, true); ?> <span class="chev"><?php echo $svgChev; ?></span></summary>
                <div class="rpbc-acc-b"><div class="rpbc-featgrid"><?php echo $featBlock($tpl['propFeat']); ?></div></div>
            </details>
            <?php endif; ?>

            <?php if (!empty($tpl['commFeat'])): ?>
            <details class="rpbc-acc">
                <summary><?php echo __('lblCommunityFeatures', true, true); ?> <span class="chev"><?php echo $svgChev; ?></span></summary>
                <div class="rpbc-acc-b"><div class="rpbc-featgrid"><?php echo $featBlock($tpl['commFeat']); ?></div></div>
            </details>
            <?php endif; ?>

            <?php if ($p['desc'] === '' && empty($tpl['propFeat']) && empty($tpl['commFeat'])): ?>
            <p class="rpbc-acc-empty"><?php echo __('lblDetailsSoon', true, true); ?></p>
            <?php endif; ?>

            <a href="#book" class="rpbc-cta"><?php echo __('lblCheckAvailability', true, true) . ' ' . $svgDown; ?></a>
        </div>
    </div>
</div>

<?php if ($p['showMap']):
    // Prefer a real address (so Google Maps shows the named place, not an empty pin);
    // fall back to name + city/state, then to raw coordinates.
    $mapQuery = '';
    if (!empty($p['address'])) {
        $mapQuery = $p['address'];
    } else {
        $mapQuery = trim(implode(', ', array_filter(array($p['name'], $p['city'], $p['state']))));
    }
    if ($mapQuery === '') {
        $mapQuery = $p['lat'] . ',' . $p['lng'];
    }
    $mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($mapQuery);
?>
<div class="rpbc-wrap">
    <div class="rpbc-sec">
        <h2><?php echo __('lblWhereYoullBe', true, true); ?></h2>
        <a class="rpbc-loccard" href="<?php echo $mapsUrl; ?>" target="_blank" rel="noopener">
            <span class="rpbc-locmap" aria-hidden="true"><span class="rpbc-locpin"><?php echo $svgPin; ?></span></span>
            <span class="rpbc-locbody">
                <span class="rpbc-loceyebrow"><?php echo __('lblWhereYoullBe', true, true); ?></span>
                <span class="rpbc-locaddr"><?php echo $location !== '' ? $location : __('lblMapLocation', true, true); ?></span>
                <span class="rpbc-locbtn"><?php echo __('lblViewOnMap', true, true); ?> &#8599;</span>
            </span>
        </a>
    </div>
</div>
<?php endif; ?>

<section id="book" class="rpbc-booksec"><div class="rpbc-booksec-in">
    <h2 class="rpbc-booksec-h"><?php echo __('lblChooseDates', true, true); ?></h2>
    <p class="rpbc-booksec-sub"><?php echo __('lblChooseDatesSub', true, true); ?></p>
    <div class="rpbc-bookcard" id="pjBookMount"><script type="text/javascript" src="<?php echo $tpl['loadJs']; ?>"></script></div>
</div></section>

<div class="rpbc-wrap"><div class="rpbc-foot-space"></div></div>

</body>
</html>