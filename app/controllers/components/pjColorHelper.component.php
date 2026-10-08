<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}

/**
 * Colour helper for the dynamic theme system (ported from PHP Shopping Cart v6.1).
 *
 * From a handful of base colours it derives a full set of CSS custom properties
 * (:root variables) that a new front-end page / embed can consume. The generated
 * variables are scoped to :root so any markup can use them, e.g.
 *
 *     color: var(--brandColor);
 *     background: var(--pageBackground);
 *
 * Set the base colours on the System Options and load the generated stylesheet with:
 *     <link rel="stylesheet" href="index.php?controller=pjFront&action=pjActionThemeCss">
 */
class pjColorHelper
{
	public static function hexToRgb($hex)
	{
		$hex = ltrim((string) $hex, '#');
		if (strlen($hex) === 3)
		{
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		return array(
			'r' => hexdec(substr($hex, 0, 2)),
			'g' => hexdec(substr($hex, 2, 2)),
			'b' => hexdec(substr($hex, 4, 2))
		);
	}

	public static function rgbToHex($r, $g, $b)
	{
		return sprintf('#%02x%02x%02x',
			max(0, min(255, (int) round($r))),
			max(0, min(255, (int) round($g))),
			max(0, min(255, (int) round($b)))
		);
	}

	public static function darken($hex, $percent = 15)
	{
		$rgb = self::hexToRgb($hex);
		$factor = 1 - ($percent / 100);
		return self::rgbToHex($rgb['r'] * $factor, $rgb['g'] * $factor, $rgb['b'] * $factor);
	}

	public static function lighten($hex, $percent = 30)
	{
		$rgb = self::hexToRgb($hex);
		$factor = $percent / 100;
		return self::rgbToHex(
			$rgb['r'] + (255 - $rgb['r']) * $factor,
			$rgb['g'] + (255 - $rgb['g']) * $factor,
			$rgb['b'] + (255 - $rgb['b']) * $factor
		);
	}

	public static function ultraLight($hex)
	{
		return self::mixWithWhite($hex, 92);
	}

	public static function mixWithWhite($hex, $whitePercent = 90)
	{
		$rgb = self::hexToRgb($hex);
		$factor = $whitePercent / 100;
		return self::rgbToHex(
			$rgb['r'] + (255 - $rgb['r']) * $factor,
			$rgb['g'] + (255 - $rgb['g']) * $factor,
			$rgb['b'] + (255 - $rgb['b']) * $factor
		);
	}

	public static function rgba($hex, $alpha)
	{
		$rgb = self::hexToRgb($hex);
		return "rgba({$rgb['r']}, {$rgb['g']}, {$rgb['b']}, {$alpha})";
	}

	public static function isDark($hex)
	{
		$rgb = self::hexToRgb($hex);
		$luminance = (0.299 * $rgb['r'] + 0.587 * $rgb['g'] + 0.114 * $rgb['b']) / 255;
		return $luminance < 0.62;
	}

	public static function contrastColor($hex)
	{
		return self::isDark($hex) ? '#ffffff' : '#000000';
	}

	public static function isLightAccent($hex)
	{
		if (!self::isValidHex($hex))
		{
			return false;
		}
		$rgb = self::hexToRgb($hex);
		$luminance = (0.299 * $rgb['r'] + 0.587 * $rgb['g'] + 0.114 * $rgb['b']) / 255;
		return $luminance > 0.82;
	}

	public static function isValidHex($hex)
	{
		return is_string($hex) && preg_match('/^#[0-9a-fA-F]{6}$/', $hex);
	}

	/**
	 * The 10 preset palettes (same set as the Shopping Cart Preview page).
	 * Keyed by palette id; each has brand / accent / text / bg / price / pageBg.
	 *
	 * @return array
	 */
	public static function getPalettes()
	{
		return array(
			'0' => array('name' => 'Pine',       'brand' => '#1f5f4e', 'accent' => '#e0872c', 'text' => '#1b2621', 'bg' => '#ffffff', 'price' => '#1f5f4e', 'pageBg' => '#f6f4ef'),
			'1' => array('name' => 'Harbor',     'brand' => '#155e75', 'accent' => '#e08a3c', 'text' => '#16242b', 'bg' => '#ffffff', 'price' => '#155e75', 'pageBg' => '#eef4f6'),
			'2' => array('name' => 'Terracotta', 'brand' => '#b0542a', 'accent' => '#2f7d64', 'text' => '#241611', 'bg' => '#ffffff', 'price' => '#b0542a', 'pageBg' => '#f8f2ec'),
			'3' => array('name' => 'Charcoal',   'brand' => '#2b3440', 'accent' => '#c99a3f', 'text' => '#171b20', 'bg' => '#ffffff', 'price' => '#2b3440', 'pageBg' => '#f2f4f6'),
			'4' => array('name' => 'Plum',       'brand' => '#6d3f7c', 'accent' => '#d89a3e', 'text' => '#221a28', 'bg' => '#ffffff', 'price' => '#6d3f7c', 'pageBg' => '#f5f0f7'),
			'5' => array('name' => 'Forest',     'brand' => '#2c6e49', 'accent' => '#d98a3d', 'text' => '#14201a', 'bg' => '#ffffff', 'price' => '#2c6e49', 'pageBg' => '#eef5f0'),
			'6' => array('name' => 'Slate Blue', 'brand' => '#33506e', 'accent' => '#c96b4a', 'text' => '#172230', 'bg' => '#ffffff', 'price' => '#33506e', 'pageBg' => '#eef2f6'),
			'7' => array('name' => 'Rose',       'brand' => '#a63d63', 'accent' => '#d99a4a', 'text' => '#26141c', 'bg' => '#ffffff', 'price' => '#a63d63', 'pageBg' => '#faf1f4'),
			'8' => array('name' => 'Sand',       'brand' => '#8a6d3b', 'accent' => '#3f7d8a', 'text' => '#241f14', 'bg' => '#ffffff', 'price' => '#8a6d3b', 'pageBg' => '#f8f4ea'),
			'9' => array('name' => 'Ink',        'brand' => '#1f2937', 'accent' => '#4f46e5', 'text' => '#111827', 'bg' => '#ffffff', 'price' => '#1f2937', 'pageBg' => '#f5f6f8')
		);
	}

	/**
	 * Build the :root custom-property block from the base colours.
	 * Returns just the reusable design tokens (no widget-specific rules), so any
	 * new RPBC page / embed can style itself against them.
	 *
	 * @param string $brand
	 * @param string $accent
	 * @param string $text
	 * @param string $bg
	 * @param string $price
	 * @param string $pageBg
	 * @param string $selector CSS selector the variables are attached to (default :root)
	 * @return string CSS
	 */
	public static function generateCssVariables($brand, $accent, $text, $bg = '#ffffff', $price = '#0a5114', $pageBg = '#ffffff', $selector = ':root')
	{
		if (!self::isValidHex($brand))  { $brand  = '#b45309'; }
		if (!self::isValidHex($accent)) { $accent = '#f3f4f6'; }
		if (!self::isValidHex($text))   { $text   = '#111827'; }
		if (!self::isValidHex($bg))     { $bg     = '#ffffff'; }
		if (!self::isValidHex($price))  { $price  = $brand; }
		if (!self::isValidHex($pageBg)) { $pageBg = '#ffffff'; }

		$brandDark = self::darken($brand, 18);
		$brandLight = self::lighten($brand, 35);
		$brandUltraLight = self::ultraLight($brand);
		$brandContrast = self::contrastColor($brand);
		$accentDark = self::darken($accent, 15);
		$accentLight = self::lighten($accent, 30);
		$textSecondary = self::lighten($text, 25);
		$textMuted = self::lighten($text, 45);
		$surfaceSubtle = ($bg === '#ffffff' || $bg === '#fff') ? self::mixWithWhite($brand, 97) : self::lighten($bg, 3);
		$borderColor = self::mixWithWhite($brand, 85);
		$borderColorHover = self::mixWithWhite($brand, 75);
		$isLightNeutralAccent = self::isLightAccent($accent);
		$actionAccent = $isLightNeutralAccent ? $brand : $accent;
		$actionAccentDark = self::darken($actionAccent, 15);

		$alphas = array(
			'06' => 0.06, '08' => 0.08, '10' => 0.10, '12' => 0.12,
			'14' => 0.14, '15' => 0.15, '18' => 0.18, '20' => 0.20,
			'22' => 0.22, '25' => 0.25, '30' => 0.30, '35' => 0.35
		);

		$css  = "\n/* RPBC dynamic theme — generated from the System Options colours */\n";
		$css .= $selector . " {\n";
		$css .= "  --brandColor: {$brand};\n";
		$css .= "  --brandColorDark: {$brandDark};\n";
		$css .= "  --brandColorLight: {$brandLight};\n";
		$css .= "  --brandColorUltraLight: {$brandUltraLight};\n";
		$css .= "  --brandContrast: {$brandContrast};\n";
		$css .= "  --accentColor: {$accent};\n";
		$css .= "  --accentContrast: " . self::contrastColor($accent) . ";\n";
		$css .= "  --accentColorDark: {$accentDark};\n";
		$css .= "  --accentColorLight: {$accentLight};\n";
		$css .= "  --actionAccent: {$actionAccent};\n";
		$css .= "  --actionAccentDark: {$actionAccentDark};\n";
		$css .= "  --textPrimary: {$text};\n";
		$css .= "  --textSecondary: {$textSecondary};\n";
		$css .= "  --textMuted: {$textMuted};\n";
		$css .= "  --surfaceBase: {$bg};\n";
		$css .= "  --surfaceElevated: {$bg};\n";
		$css .= "  --surfaceSubtle: {$surfaceSubtle};\n";
		$css .= "  --borderColor: {$borderColor};\n";
		$css .= "  --borderColorHover: {$borderColorHover};\n";
		$css .= "  --priceColor: {$price};\n";
		$css .= "  --pageBackground: {$pageBg};\n";
		$css .= "  --panelTint: {$pageBg};\n";
		foreach ($alphas as $key => $alpha)
		{
			$css .= "  --brandAlpha{$key}: " . self::rgba($brand, $alpha) . ";\n";
		}
		$css .= "  --shadowSm: 0 1px 3px " . self::rgba($brand, 0.08) . ";\n";
		$css .= "  --shadowMd: 0 4px 12px " . self::rgba($brand, 0.10) . ";\n";
		$css .= "  --shadowLg: 0 8px 24px " . self::rgba($brand, 0.14) . ";\n";
		$css .= "  --shadowHover: 0 8px 30px " . self::rgba($brand, 0.18) . ";\n";
		$css .= "}\n";

		return $css;
	}
}
?>
