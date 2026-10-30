<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
/**
 * AI text generation for the public sign-up (property description).
 * Service, models and API keys: Options > AI settings (administrators only).
 * Stored as shared system options (plugin_base_options, foreign_id 1, tab 99):
 *   o_ai_provider, o_ai_model_<service>, o_<service>_api_key
 *
 * Optional, config.inc.php: PJ_AI_ENDPOINT_GEMINI / PJ_AI_ENDPOINT_OPENAI / PJ_AI_ENDPOINT_ANTHROPIC
 * (another base URL, e.g. a proxy).
 */
class pjAi
{
	/** Seconds one AI request may take (the sign-up action and api.php allow more). */
	const TIMEOUT = 120;

	/** Duration of the last request, in seconds (shown by Test). */
	public static $lastSeconds = 0;

	/**
	 * Least "thinking" the model allows: a short description needs no reasoning, and thinking
	 * is what makes newer models slow (Gemini 3.x thinks at medium/high by default).
	 * Returns the extra request fields, or array() when the model family is not known.
	 */
	private static function fastOptions($service, $model)
	{
		$m = strtolower($model);
		if ($service === 'gemini')
		{
			if (preg_match('/^gemini-2\.5-pro/', $m))
			{
				return array('thinkingConfig' => array('thinkingBudget' => 128)); // 2.5 Pro cannot turn thinking off
			}
			if (preg_match('/^gemini-2\.5/', $m))
			{
				return array('thinkingConfig' => array('thinkingBudget' => 0));   // 2.5 Flash / Flash-Lite: off
			}
			if (preg_match('/^gemini-([3-9]|\d{2,})/', $m))
			{
				return array('thinkingConfig' => array('thinkingLevel' => 'low')); // Gemini 3 and later: lowest level all of them accept
			}
			return array();
		}
		if ($service === 'openai' && preg_match('/^(gpt-5|gpt-[6-9]|o\d)/', $m))
		{
			return array('reasoning' => array('effort' => 'low'));
		}
		return array();
	}

	public static $services = array(
		'gemini'    => array('name' => 'Google Gemini',    'endpoint' => 'https://generativelanguage.googleapis.com/v1beta', 'default_model' => 'gemini-2.5-flash'),
		'openai'    => array('name' => 'OpenAI',           'endpoint' => 'https://api.openai.com/v1',                     'default_model' => ''),
		'anthropic' => array('name' => 'Anthropic Claude', 'endpoint' => 'https://api.anthropic.com/v1',                  'default_model' => ''),
	);

	/** Option keys this component uses (all shared, see pjCompanyContext::$globalOptionKeys). */
	public static function optionKeys()
	{
		$keys = array('o_ai_provider');
		foreach (array_keys(self::$services) as $s)
		{
			$keys[] = 'o_ai_model_' . $s;
			$keys[] = 'o_' . $s . '_api_key';
			$keys[] = 'o_ai_models_' . $s;        // last list loaded from the service (JSON)
			$keys[] = 'o_ai_custom_models_' . $s; // models added by hand (JSON)
		}
		return $keys;
	}

	/* ------------------------------------------------------------------
	 * Settings
	 * ------------------------------------------------------------------ */

	private static function option($key)
	{
		$row = pjBaseOptionModel::factory()->where('t1.foreign_id', 1)->where('t1.key', $key)->limit(1)->findAll()->getDataIndex(0);
		return !empty($row) ? trim((string) $row['value']) : '';
	}

	/** Save a shared AI option. Returns true only when the value read back from the database matches. */
	public static function saveOption($key, $value)
	{
		if (!in_array($key, self::optionKeys(), true))
		{
			return false;
		}
		$value = (string) $value;
		$m = pjBaseOptionModel::factory();
		if ((int) $m->where('t1.foreign_id', 1)->where('t1.key', $key)->findCount()->getData() > 0)
		{
			$m->reset()->where('foreign_id', 1)->where('`key`', $key)->limit(1)->modifyAll(array('value' => $value === '' ? ':NULL' : $value));
		} else {
			$m->reset()->setAttributes(array('foreign_id' => 1, 'key' => $key, 'tab_id' => 99, 'value' => $value, 'type' => strpos($key, 'models_') !== false ? 'text' : 'string', 'order' => 1, 'is_visible' => 0))->insert();
		}
		return self::option($key) === trim($value);
	}

	/** A model list option (JSON array of ids). */
	public static function modelList($key)
	{
		$list = json_decode(self::option($key), true);
		return is_array($list) ? array_values(array_filter($list, array(__CLASS__, 'validModel'))) : array();
	}

	/** Saved key shown as AIza…x7Q2 (never the whole key). '' when none. */
	public static function maskedKey($service)
	{
		$k = self::apiKey($service);
		if ($k === '')
		{
			return '';
		}
		return strlen($k) <= 10 ? str_repeat('•', strlen($k)) : substr($k, 0, 4) . '…' . substr($k, -4);
	}

	/** The selected service ('gemini' when not set). */
	public static function provider()
	{
		$p = self::option('o_ai_provider');
		return isset(self::$services[$p]) ? $p : 'gemini';
	}

	public static function apiKey($service)
	{
		return isset(self::$services[$service]) ? self::option('o_' . $service . '_api_key') : '';
	}

	public static function model($service)
	{
		if (!isset(self::$services[$service]))
		{
			return '';
		}
		$m = self::option('o_ai_model_' . $service);
		return $m !== '' ? $m : self::$services[$service]['default_model'];
	}

	/** Settings for the admin page (administrators only): the saved key is shown in its field, hidden until "Show". */
	public static function settings()
	{
		$out = array('provider' => self::provider(), 'services' => array());
		foreach (self::$services as $s => $info)
		{
			$loaded = self::modelList('o_ai_models_' . $s);
			$custom = self::modelList('o_ai_custom_models_' . $s);
			$out['services'][$s] = array(
				'name'    => $info['name'],
				'model'   => self::model($s),
				'has_key' => self::apiKey($s) !== '',
				'key_hint'=> self::maskedKey($s),
				'key'     => self::apiKey($s),
				'loaded'  => $loaded,
				'custom'  => $custom,
			);
		}
		return $out;
	}

	/** The selected service has a key and a model. */
	public static function isConfigured()
	{
		$p = self::provider();
		return self::apiKey($p) !== '' && self::model($p) !== '';
	}

	public static function validModel($model)
	{
		return (bool) preg_match('~^[A-Za-z0-9][A-Za-z0-9._:/\-]{0,99}$~', (string) $model);
	}

	/* ------------------------------------------------------------------
	 * Requests
	 * ------------------------------------------------------------------ */

	private static function endpoint($service)
	{
		$const = 'PJ_AI_ENDPOINT_' . strtoupper($service);
		return rtrim(defined($const) ? constant($const) : self::$services[$service]['endpoint'], '/');
	}

	private static function headers($service, $key)
	{
		switch ($service)
		{
			case 'gemini':
				return array('Content-Type: application/json', 'x-goog-api-key: ' . $key);
			case 'openai':
				return array('Content-Type: application/json', 'Authorization: Bearer ' . $key);
			case 'anthropic':
				return array('Content-Type: application/json', 'x-api-key: ' . $key, 'anthropic-version: 2023-06-01');
		}
		return array();
	}

	/** HTTP call. Returns array(http_code, decoded json or null, error text). */
	private static function http($method, $url, $headers, $body = null, $timeout = self::TIMEOUT)
	{
		if (!function_exists('curl_init'))
		{
			return array(0, null, 'cURL is not available on the server.');
		}
		$ch = curl_init($url);
		$opt = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CONNECTTIMEOUT => 10,
			CURLOPT_TIMEOUT        => (int) $timeout,
			CURLOPT_HTTPHEADER     => $headers,
		);
		if ($method === 'POST')
		{
			$opt[CURLOPT_POST] = true;
			$opt[CURLOPT_POSTFIELDS] = json_encode($body);
		}
		curl_setopt_array($ch, $opt);
		$raw = curl_exec($ch);
		$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$connected = (float) curl_getinfo($ch, CURLINFO_CONNECT_TIME) > 0;
		self::$lastSeconds = round((float) curl_getinfo($ch, CURLINFO_TOTAL_TIME), 1);
		$errno = curl_errno($ch);
		$err = curl_error($ch);
		curl_close($ch);
		if ($raw === false || $raw === '')
		{
			if ($errno === 28 && !$connected)
			{
				// never reached the service: network, DNS, firewall or proxy of the server
				return array(0, null, 'could not connect to the AI service within 10 seconds. Check the server internet connection / firewall (' . $err . ').');
			}
			if ($errno === 28)
			{
				return array(0, null, sprintf('the AI service took longer than %u seconds to answer. Choose a faster model (for example a "flash" or "mini" model).', (int) $timeout));
			}
			return array(0, null, 'the AI service did not answer' . ($err !== '' ? ' (' . $err . ')' : '') . '.');
		}
		return array($code, json_decode($raw, true), '');
	}

	private static function apiError($json, $code)
	{
		if (is_array($json) && isset($json['error']['message']))
		{
			return $json['error']['message'];
		}
		if (is_array($json) && isset($json['error']) && is_string($json['error']))
		{
			return $json['error'];
		}
		return 'HTTP ' . $code;
	}

	/**
	 * Generate text. $service / $key / $model default to the saved settings.
	 * Returns array('status' => 'OK', 'text' => ...) or array('status' => 'ERR', 'code' => ..., 'text' => reason).
	 */
	public static function generate($prompt, $max_tokens = 4096, $service = null, $key = null, $model = null)
	{
		$service = $service !== null ? $service : self::provider();
		if (!isset(self::$services[$service]))
		{
			return array('status' => 'ERR', 'code' => 400, 'text' => 'Unknown AI service.');
		}
		$key = trim((string) ($key !== null && $key !== '' ? $key : self::apiKey($service)));
		$model = trim((string) ($model !== null && $model !== '' ? $model : self::model($service)));
		if ($key === '')
		{
			return array('status' => 'ERR', 'code' => 503, 'text' => 'No API key is saved for ' . self::$services[$service]['name'] . '.');
		}
		if (!self::validModel($model))
		{
			return array('status' => 'ERR', 'code' => 503, 'text' => 'No valid model is chosen for ' . self::$services[$service]['name'] . '.');
		}

		$base = self::endpoint($service);
		$fast = self::fastOptions($service, $model);
		switch ($service)
		{
			case 'gemini':
				$url = $base . '/models/' . rawurlencode($model) . ':generateContent';
				$body = array(
					'contents' => array(array('role' => 'user', 'parts' => array(array('text' => (string) $prompt)))),
					'generationConfig' => array_merge(array('maxOutputTokens' => (int) $max_tokens), $fast),
				);
				break;
			case 'openai':
				$url = $base . '/responses';
				$body = array_merge(array('model' => $model, 'input' => (string) $prompt, 'max_output_tokens' => (int) $max_tokens), $fast);
				break;
			default: // anthropic (no extended thinking unless asked for)
				$url = $base . '/messages';
				$body = array('model' => $model, 'max_tokens' => (int) $max_tokens, 'messages' => array(array('role' => 'user', 'content' => (string) $prompt)));
		}

		list($code, $json, $err) = self::http('POST', $url, self::headers($service, $key), $body);
		// A model that does not accept the "less thinking" setting: ask again without it.
		if ($err === '' && $code === 400 && !empty($fast) && preg_match('/think|reasoning|budget|effort|level/i', self::apiError($json, $code)))
		{
			if ($service === 'gemini')
			{
				unset($body['generationConfig']['thinkingConfig']);
			} else {
				unset($body['reasoning']);
			}
			list($code, $json, $err) = self::http('POST', $url, self::headers($service, $key), $body);
		}
		$label = self::$services[$service]['name'];
		if ($err !== '')
		{
			return array('status' => 'ERR', 'code' => 502, 'text' => $label . ': ' . $err);
		}
		if ($code !== 200 || !is_array($json))
		{
			return array('status' => 'ERR', 'code' => 502, 'text' => $label . ' error: ' . self::apiError($json, $code));
		}

		$text = '';
		$why = '';
		switch ($service)
		{
			case 'gemini':
				if (isset($json['candidates'][0]['content']['parts']) && is_array($json['candidates'][0]['content']['parts']))
				{
					foreach ($json['candidates'][0]['content']['parts'] as $part)
					{
						if (isset($part['text']) && empty($part['thought'])) { $text .= $part['text']; }
					}
				}
				$why = isset($json['promptFeedback']['blockReason']) ? $json['promptFeedback']['blockReason']
					: (isset($json['candidates'][0]['finishReason']) ? $json['candidates'][0]['finishReason'] : '');
				break;
			case 'openai':
				if (isset($json['output']) && is_array($json['output']))
				{
					foreach ($json['output'] as $item)
					{
						if (isset($item['type'], $item['content']) && $item['type'] === 'message' && is_array($item['content']))
						{
							foreach ($item['content'] as $c)
							{
								if (isset($c['type'], $c['text']) && $c['type'] === 'output_text') { $text .= $c['text']; }
							}
						}
					}
				}
				$why = isset($json['incomplete_details']['reason']) ? $json['incomplete_details']['reason'] : (isset($json['status']) ? $json['status'] : '');
				break;
			default:
				if (isset($json['content']) && is_array($json['content']))
				{
					foreach ($json['content'] as $c)
					{
						if (isset($c['type'], $c['text']) && $c['type'] === 'text') { $text .= $c['text']; }
					}
				}
				$why = isset($json['stop_reason']) ? $json['stop_reason'] : '';
		}
		$text = trim($text);
		if ($text === '')
		{
			return array('status' => 'ERR', 'code' => 502, 'text' => $label . ' returned no text' . ($why !== '' ? ' (' . $why . ')' : '') . '.');
		}
		return array('status' => 'OK', 'text' => $text);
	}

	/** Models the key can use for text. Returns array('status' => 'OK', 'models' => [ids]) or an error. */
	public static function listModels($service, $key = '')
	{
		if (!isset(self::$services[$service]))
		{
			return array('status' => 'ERR', 'text' => 'Unknown AI service.');
		}
		$key = trim((string) ($key !== '' ? $key : self::apiKey($service)));
		if ($key === '')
		{
			return array('status' => 'ERR', 'text' => 'Enter the API key first.');
		}
		$base = self::endpoint($service);
		$url = $service === 'gemini' ? $base . '/models?pageSize=1000' : $base . '/models' . ($service === 'anthropic' ? '?limit=1000' : '');
		list($code, $json, $err) = self::http('GET', $url, self::headers($service, $key), null, 30);
		$label = self::$services[$service]['name'];
		if ($err !== '')
		{
			return array('status' => 'ERR', 'text' => $label . ': ' . $err);
		}
		if ($code !== 200 || !is_array($json))
		{
			return array('status' => 'ERR', 'text' => $label . ' error: ' . self::apiError($json, $code));
		}
		$ids = array();
		if ($service === 'gemini')
		{
			foreach ((isset($json['models']) ? $json['models'] : array()) as $m)
			{
				$id = isset($m['name']) ? preg_replace('~^models/~', '', $m['name']) : '';
				$methods = isset($m['supportedGenerationMethods']) ? (array) $m['supportedGenerationMethods'] : array();
				if ($id !== '' && in_array('generateContent', $methods, true) && !preg_match('/(image|tts|audio|live|embed|veo|imagen|robotics|computer-use|nano-banana|lyria|transcribe|deep-research|antigravity)/i', $id))
				{
					$ids[] = $id;
				}
			}
		} else {
			foreach ((isset($json['data']) ? $json['data'] : array()) as $m)
			{
				$id = isset($m['id']) ? (string) $m['id'] : '';
				if ($id === '')
				{
					continue;
				}
				if ($service === 'openai' && (!preg_match('/^(gpt|o\d|chatgpt)/i', $id)
					|| preg_match('/(image|audio|realtime|transcribe|tts|search|embed|moderation|live|dall|whisper)/i', $id)))
				{
					continue;
				}
				$ids[] = $id;
			}
		}
		$ids = array_values(array_unique($ids));
		rsort($ids, SORT_NATURAL);
		// keep the list so the page shows it next time without loading again
		self::saveOption('o_ai_models_' . $service, json_encode($ids));
		return array('status' => 'OK', 'models' => $ids);
	}

	/** Test a service / key / model with a tiny request. Returns true or the reason. */
	public static function test($service, $key, $model)
	{
		$res = self::generate('Reply with the single word OK.', 256, $service, $key, $model);
		return $res['status'] === 'OK' ? true : $res['text'];
	}
}
?>
