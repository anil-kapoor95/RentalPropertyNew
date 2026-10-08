<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
class pjRazorpaySDK extends pjPaymentsSDK
{
	protected static $version = "v1";

	protected function getEndPoint($path=null)
	{
		return "https://api.razorpay.com/" . self::$version . "/" . $path;
	}

	protected function request($path=null, $params=null)
	{
		$http = new pjHttp();

		if ($params)
		{
			$http->setMethod("POST");
			if (!is_bool($params))
			{
				# Razorpay's REST API expects a JSON encoded body.
				$http->setData(json_encode($params), false);
			} else {
				$http->setData(json_encode(new stdClass()), false);
			}
		} else {
			$http->setMethod("GET");
		}

		$http
			->addHeader("Authorization: Basic " . base64_encode($this->getPublicKey() . ":" . $this->getPrivateKey()))
			->addHeader("Content-Type: application/json")
			->addHeader("Accept: application/json")
			->curlRequest($this->getEndPoint($path));

		$error = $http->getError();
		if ($error)
		{
			return array('status' => 'ERR', 'code' => 100, 'text' => $error['text']);
		}

		$response = $http->getResponse();
		if (!$response)
		{
			return array('status' => 'ERR', 'code' => 102, 'text' => 'Empty response');
		}

		$result = json_decode($response, true);

		if (method_exists($http, 'getHttpCode') && !in_array((int) $http->getHttpCode(), array(200, 201)))
		{
			$text = 'HTTP Code: ' . $http->getHttpCode();
			if (isset($result['error']['description']))
			{
				$text .= ' - ' . $result['error']['description'];
			}
			return array('status' => 'ERR', 'code' => 101, 'text' => $text);
		}

		return array('status' => 'OK', 'code' => 200, 'text' => 'Success', 'result' => $result);
	}

/**
 * Executes a request and validates the Razorpay response.
 *
 * @param string $path
 * @param mixed $params
 * @throws Exception
 * @return array
 */
	protected function call($path, $params=null)
	{
		$data = $this->request($path, $params);
		if ($data['status'] != 'OK')
		{
			throw new Exception($data['text']);
		}

		$response = new pjRazorpaySDKResponse($data['result']);
		if (!$response->isOK())
		{
			$error = $response->getError();
			throw new Exception(@$error['description']);
		}

		return $data['result'];
	}

/**
 * Creates an Order object.
 *
 * @param array $params
 * @throws Exception
 * @return array
 */
	public function createOrder($params)
	{
		return $this->call('orders', $params);
	}

/**
 * Fetches an Order object.
 *
 * @param string $id For example: order_DaZlswtdcn9UNV
 * @throws Exception
 * @return array
 */
	public function getOrder($id)
	{
		return $this->call("orders/$id");
	}

/**
 * Fetches a Payment object.
 *
 * @param string $id For example: pay_29QQoUBi66xm2f
 * @throws Exception
 * @return array
 */
	public function getPayment($id)
	{
		return $this->call("payments/$id");
	}

/**
 * Captures an authorized payment.
 *
 * @param string $id For example: pay_29QQoUBi66xm2f
 * @param array $params Requires 'amount' (in the smallest currency unit) and 'currency'
 * @throws Exception
 * @return array
 */
	public function capture($id, $params)
	{
		return $this->call("payments/$id/capture", $params);
	}

/**
 * Refunds a captured payment.
 *
 * @param string $id For example: pay_29QQoUBi66xm2f
 * @param array $params
 * @throws Exception
 * @return array
 */
	public function refund($id, $params=array())
	{
		return $this->call("payments/$id/refund", empty($params) ? true : $params);
	}

/**
 * Creates a Plan object.
 *
 * @param array $params
 * @throws Exception
 * @return array
 */
	public function createPlan($params)
	{
		return $this->call('plans', $params);
	}

/**
 * Creates a Subscription object.
 *
 * @param array $params
 * @throws Exception
 * @return array
 */
	public function createSubscription($params)
	{
		return $this->call('subscriptions', $params);
	}

/**
 * Cancels a subscription.
 *
 * @param string $id For example: sub_00000000000001
 * @throws Exception
 * @return array
 */
	public function cancelSubscription($id)
	{
		return $this->call("subscriptions/$id/cancel", true);
	}

/**
 * Verifies the signature returned by Razorpay Checkout.
 *
 * @param string $payload The signed payload, e.g. "{order_id}|{payment_id}"
 * @param string $signature The razorpay_signature value
 * @return bool
 */
	public function verifySignature($payload, $signature)
	{
		if (empty($signature) || empty($this->getPrivateKey()))
		{
			return false;
		}

		$expected = hash_hmac('sha256', $payload, $this->getPrivateKey());

		if (function_exists('hash_equals'))
		{
			return hash_equals($expected, $signature);
		}

		# Constant time fallback for PHP < 5.6
		if (strlen($expected) !== strlen($signature))
		{
			return false;
		}
		$result = 0;
		for ($i = 0; $i < strlen($expected); $i++)
		{
			$result |= ord($expected[$i]) ^ ord($signature[$i]);
		}

		return $result === 0;
	}
}
