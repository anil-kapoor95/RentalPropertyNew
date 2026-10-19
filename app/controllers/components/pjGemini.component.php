<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
/**
 * Google Gemini (generateContent) - plain text in, plain text out.
 * API key: Options > API Keys (shared option o_gemini_api_key, administrators only).
 * Model: gemini-2.5-flash, or PJ_GEMINI_MODEL when defined in config.inc.php.
 */
class pjGemini
{
	const BASE_URL = 'https://generativelanguage.googleapis.com/v1beta/models/';

	const DEFAULT_MODEL = 'gemini-3.8-flash';

	/**
	 * The administrator's key: the shared options (foreign_id 1, Options > API Keys),
	 * then the Admin workspace options (where an earlier build saved it).
	 */
	public static function apiKey()
	{
		$fids = array(1);
		if (class_exists('pjCompanyContext'))
		{
			$aws = (int) pjCompanyContext::adminWorkspaceId(false);
			if ($aws > 0)
			{
				$fids[] = pjCompanyContext::OPTIONS_OFFSET + $aws;
			}
		}
		foreach ($fids as $fid)
		{
			$row = pjBaseOptionModel::factory()->where('t1.foreign_id', $fid)->where('t1.key', 'o_gemini_api_key')->limit(1)->findAll()->getDataIndex(0);
			if (!empty($row) && trim((string) $row['value']) !== '')
			{
				return trim((string) $row['value']);
			}
		}
		return '';
	}

	/** The administrator has saved an API key. */
	public static function isConfigured()
	{
		return self::apiKey() !== '';
	}

	/** Options > API Keys "Verify key": a tiny request with the key typed in. */
	public static function verify($key)
	{
		$res = self::generate('Reply with the single word OK.', 256, 0, $key);
		return $res['status'] === 'OK' ? true : $res['text'];
	}

	/**
	 * Generate text for $prompt ($key: use this key instead of the saved one).
	 * Returns array('status' => 'OK', 'text' => ...) or array('status' => 'ERR', 'code' => ..., 'text' => reason).
	 */
	public static function generate($prompt, $max_tokens = 2048, $temperature = 0.7, $key = null)
	{
		$key = trim((string) ($key !== null ? $key : self::apiKey()));
		if ($key === '')
		{
			return array('status' => 'ERR', 'code' => 503, 'text' => 'AI is not configured.');
		}
		if (!function_exists('curl_init'))
		{
			return array('status' => 'ERR', 'code' => 503, 'text' => 'cURL is not available on the server.');
		}
		$model = defined('PJ_GEMINI_MODEL') ? trim((string) PJ_GEMINI_MODEL) : self::DEFAULT_MODEL;
		if (!preg_match('/^[a-z0-9][a-z0-9.\-]{1,62}$/', $model))
		{
			$model = self::DEFAULT_MODEL;
		}
		// PJ_GEMINI_BASE_URL (optional, config.inc.php): another endpoint, e.g. a proxy
		$base = defined('PJ_GEMINI_BASE_URL') ? PJ_GEMINI_BASE_URL : self::BASE_URL;
		$url = $base . rawurlencode($model) . ':generateContent';

		$body = json_encode(array(
			'contents' => array(array('role' => 'user', 'parts' => array(array('text' => (string) $prompt)))),
			'generationConfig' => array('temperature' => (float) $temperature, 'maxOutputTokens' => (int) $max_tokens),
		));

		$ch = curl_init($url);
		curl_setopt_array($ch, array(
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => $body,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_TIMEOUT        => 45,
			CURLOPT_HTTPHEADER     => array('Content-Type: application/json', 'x-goog-api-key: ' . $key),
		));
		$raw = curl_exec($ch);
		$http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$err = curl_error($ch);
		curl_close($ch);

		if ($raw === false || $raw === '')
		{
			return array('status' => 'ERR', 'code' => 502, 'text' => 'AI service did not answer' . ($err !== '' ? ' (' . $err . ')' : '') . '.');
		}
		$json = json_decode($raw, true);
		if ($http !== 200 || !is_array($json))
		{
			$reason = is_array($json) && isset($json['error']['message']) ? $json['error']['message'] : 'HTTP ' . $http;
			return array('status' => 'ERR', 'code' => 502, 'text' => 'AI service error: ' . $reason);
		}
		$text = '';
		if (isset($json['candidates'][0]['content']['parts']) && is_array($json['candidates'][0]['content']['parts']))
		{
			foreach ($json['candidates'][0]['content']['parts'] as $part)
			{
				if (isset($part['text']) && empty($part['thought']))
				{
					$text .= $part['text'];
				}
			}
		}
		$text = trim($text);
		if ($text === '')
		{
			$why = isset($json['promptFeedback']['blockReason']) ? $json['promptFeedback']['blockReason']
				: (isset($json['candidates'][0]['finishReason']) ? $json['candidates'][0]['finishReason'] : 'empty answer');
			return array('status' => 'ERR', 'code' => 502, 'text' => 'AI could not write a description (' . $why . ').');
		}
		return array('status' => 'OK', 'text' => $text);
	}
}
?>
