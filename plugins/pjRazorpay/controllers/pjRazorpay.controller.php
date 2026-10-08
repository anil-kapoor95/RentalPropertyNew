<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
class pjRazorpay extends pjRazorpayAppController
{
    protected static $logPrefix = "Payments | pjRazorpay plugin<br>";

    protected static $paymentMethod = 'razorpay';

    /*
     * Currencies which are NOT expressed in a 1/100 subunit. Razorpay expects the amount
     * in the smallest unit of the currency, which for these is the currency unit itself.
     */
    protected static $zeroDecimalCurrencies = array('JPY', 'KRW', 'VND', 'CLP', 'ISK');

    /*
     * Columns allowed in the ORDER BY clause of pjActionGetRazorpay.
     */
    protected static $sortableColumns = array('id', 'foreign_id', 'razorpay_id', 'order_id', 'amount', 'currency', 'description', 'created');

    public function pjActionOptions()
    {
        $this->checkLogin();

        $this->setLayout('pjActionEmpty');

        if (!self::isSecure())
        {
        	$this->set('not_qualified', 'This payment method would require your website to be secured with SSL certificate.');
        }

        $params = $this->getParams();

        $this->set('arr', pjPaymentOptionModel::factory()->getOptions($params['foreign_id'], self::$paymentMethod));

        $i18n = pjMultiLangModel::factory()->getMultiLang($params['fid'], 'pjPayment');
        $this->set('i18n', $i18n);
        $locale_arr = pjLocaleModel::factory()
        	->select('t1.*, t2.file')
	        ->join('pjLocaleLanguage', 't2.iso=t1.language_iso', 'left')
	        ->where('t2.file IS NOT NULL')
	        ->orderBy('t1.sort ASC')
        	->findAll()
        	->getData();

        $lp_arr = array();
        $default_locale_id = NULL;
        foreach ($locale_arr as $item)
        {
        	$lp_arr[$item['id']."_"] = $item['file'];
        	if ($item['is_default'])
        	{
        		$default_locale_id = $item['id'];
        	}
        }
        $this->set('lp_arr', $locale_arr);
        $this->set('locale_str', pjAppController::jsonEncode($lp_arr));
        $this->set('is_flag_ready', $this->requestAction(array('controller' => 'pjLocale', 'action' => 'pjActionIsFlagReady'), array('return')));

        $this->set('locale_id', isset($params['locale_id']) ? $params['locale_id'] : $default_locale_id);
    }

    public function pjActionSaveOptions()
    {
        $this->checkLogin();

        return true;
    }

    public function pjActionCopyOptions()
    {
        $this->checkLogin();

        return true;
    }

    public function pjActionDeleteOptions()
    {
        $this->checkLogin();

        return true;
    }

    /*
     * Converts an amount to the smallest unit of the given currency, as expected by Razorpay.
     */
    public static function toSubUnits($amount, $currency_code)
    {
        if (in_array(strtoupper((string) $currency_code), self::$zeroDecimalCurrencies))
        {
            return (int) round($amount);
        }

        return (int) round($amount * 100);
    }

    public static function captureFunds($params)
    {
        $sdk = new pjRazorpaySDK(null, $params['public_key'], $params['private_key']);

        $data = array(
            'amount' => self::toSubUnits($params['amount'], @$params['currency']),
            'currency' => @$params['currency'],
        );

        try {
            $result = $sdk->capture($params['txn_id'], $data);

            $retval = array('status' => 'OK', 'code' => 200, 'text' => 'Capture funds succeed.', 'result' => $result);
        } catch (Exception $e) {
            $retval = array('status' => 'ERR', 'code' => 100, 'text' => 'Capture funds failed: ' . $e->getMessage());
        }

        return $retval;
    }

    public static function getFormParams($post, $order_arr)
    {
        $params = parent::getFormParams($post, $order_arr);

        $params['cancel_url'] = "{$params['notify_url']}&cancel_hash={$params['cancel_hash']}";
        $params['amount'] = self::toSubUnits($params['amount'], $params['currency_code']);

        return $params;
    }

    public function pjActionGetCustom()
    {
        $request = $this->getParams();
        $custom = isset($request['id']) ? $request['id'] : null;

        if (!empty($custom))
        {
            $this->log(self::$logPrefix . "Start confirmation process for: {$custom}<br>Request Data:<br>" . print_r($request, true));
        } else {
            $this->log(self::$logPrefix . "Missing parameters. Cannot start confirmation process.<br>Request Data:<br>" . print_r($request, true));
        }

        return $custom;
    }

	public function pjActionForm()
	{
		$this->setLayout('pjActionEmpty');

		$params = $this->getParams();

		$data = array(
			'amount' => (int) $params['amount'],
			'currency' => strtoupper($params['currency_code']),
			'receipt' => substr((string) @$params['custom'], 0, 40),
			'payment_capture' => 1,
			'notes' => array(
				'uuid' => (string) @$params['custom'],
			),
		);

		$sdk = new pjRazorpaySDK(null, $params['public_key'], $params['private_key']);

		try {
			$result = $sdk->createOrder($data);

			if (isset($result['id']))
			{
				$this->set('order_id', $result['id']);
			}
		} catch (Exception $e) {
			$this->log(self::$logPrefix . "Order could not be created. " . $e->getMessage());
		}

		$this->set('arr', $params);
	}

	public function pjActionSubscribe()
	{
		$this->setLayout('pjActionEmpty');

		$params = $this->getParams();

		$interval = isset($params['recurring_interval']) && in_array($params['recurring_interval'], array('daily', 'weekly', 'monthly', 'yearly'))
			? $params['recurring_interval']
			: 'monthly';

		$interval_count = isset($params['recurring_interval_count']) && is_numeric($params['recurring_interval_count'])
			? (int) $params['recurring_interval_count']
			: 1;

		$total_count = isset($params['recurring_total_count']) && is_numeric($params['recurring_total_count'])
			? (int) $params['recurring_total_count']
			: 12;

		$data_plan = array(
			'period' => $interval,
			'interval' => $interval_count,
			'item' => array(
				'name' => @$params['item_name'],
				'amount' => (int) $params['amount'],
				'currency' => strtoupper($params['currency_code']),
			),
			'notes' => array(
				'uuid' => (string) @$params['custom'],
			),
		);

		$sdk = new pjRazorpaySDK(null, $params['public_key'], $params['private_key']);

		try {
			$result = $sdk->createPlan($data_plan);
			if (isset($result['id']))
			{
				$data_subscription = array(
					'plan_id' => $result['id'],
					'total_count' => $total_count,
					'quantity' => 1,
					'customer_notify' => 1,
					'notes' => array(
						'uuid' => (string) @$params['custom'],
					),
				);

				$result = $sdk->createSubscription($data_subscription);

				if (isset($result['id']))
				{
					$this->set('subscription_id', $result['id']);
				}
			}
		} catch (Exception $e) {
			$this->log(self::$logPrefix . "Subscription could not be created. " . $e->getMessage());
		}

		$this->set('arr', $params);
	}

    public function pjActionConfirm()
    {
        $params = $this->getParams();
        $request = $params['request'];

        if (!isset($params['key']) || $params['key'] != md5($this->option_arr['private_key'] . PJ_SALT))
        {
            $this->log(self::$logPrefix . "Missing or invalid 'key' parameter.");
            return FALSE;
        }

        $response = array('status' => 'FAIL', 'redirect' => true);
        if (isset($request['cancel_hash']) && $request['cancel_hash'] == $params['cancel_hash'])
        {
            $this->log(self::$logPrefix . "Payment was cancelled.");
            $response['status'] = 'CANCEL';
            return $response;
        }

        $options = pjPaymentOptionModel::factory()->getOptions($params['foreign_id'], self::$paymentMethod);

        $_response = $this->requestAction(array(
            'controller' => 'pjRazorpay',
            'action' => 'pjActionProcess',
            'params' => array(
                'key' => md5($this->option_arr['private_key'] . PJ_SALT),
            	'public_key' => (int) $options['is_test_mode'] === 1 ? $options['test_public_key'] : $options['public_key'],
            	'private_key' => (int) $options['is_test_mode'] === 1 ? $options['test_private_key'] : $options['private_key'],
                'amount' => self::toSubUnits($params['amount'], $this->option_arr['o_currency']),
                'currency' => $this->option_arr['o_currency'],
                'item_name' => pjSanitize::html(@$params['uuid']),
                'foreign_id' => $params['order_id'],
            	'payment_id' => @$request['razorpay_payment_id'],
            	'razorpay_order_id' => @$request['razorpay_order_id'],
            	'subscription_id' => @$request['razorpay_subscription_id'],
            	'signature' => @$request['razorpay_signature'],
            )
        ), array('return'));

        if ($_response['status'] == 'OK')
        {
            $response['status'] = 'OK';
            $response['txn_id'] = @$_response['result']['id'];
        }

        return $response;
    }

	public function pjActionProcess()
	{
		$params = $this->getParams();
		if (!isset($params['key']) || $params['key'] != md5($this->option_arr['private_key'] . PJ_SALT))
		{
            $this->log(self::$logPrefix . "Missing or invalid key parameter.");
            return array('status' => 'ERR', 'code' => 100, 'text' => 'Missing or invalid key parameter');
		}

		if (empty($params['payment_id']) || empty($params['signature']) || !isset($params['private_key'], $params['amount'], $params['currency']))
		{
            $this->log(self::$logPrefix . "Missing, empty or invalid parameters.");
            return array('status' => 'ERR', 'code' => 101, 'text' => 'Missing, empty or invalid parameters.');
		}

		$sdk = new pjRazorpaySDK(null, $params['public_key'], $params['private_key']);

		# Verify the signature returned by Razorpay Checkout before trusting anything else.
		if (!empty($params['subscription_id']))
		{
			$payload = $params['payment_id'] . '|' . $params['subscription_id'];
		} elseif (!empty($params['razorpay_order_id'])) {
			$payload = $params['razorpay_order_id'] . '|' . $params['payment_id'];
		} else {
			$this->log(self::$logPrefix . "Neither order nor subscription reference was returned.");
			return array('status' => 'ERR', 'code' => 103, 'text' => 'Missing order/subscription reference.');
		}

		if (!$sdk->verifySignature($payload, $params['signature']))
		{
			$this->log(self::$logPrefix . "Signature verification failed for payment {$params['payment_id']}.");
			return array('status' => 'ERR', 'code' => 106, 'text' => 'Signature verification failed.');
		}

		try {
			$payment = $sdk->getPayment($params['payment_id']);

		} catch (Exception $e) {

			$this->log(self::$logPrefix . $e->getMessage());
			return array('status' => 'ERR', 'code' => 102, 'text' => $e->getMessage());
		}

		if (!isset($payment['id']))
		{
			$this->log(self::$logPrefix . "Payment not found.");
			return array('status' => 'ERR', 'code' => 104, 'text' => 'Payment still not found.', 'result' => $payment);
		}

		# The payment must belong to the order we created and match its amount and currency.
		if (!empty($params['razorpay_order_id']) && @$payment['order_id'] != $params['razorpay_order_id'])
		{
			$this->log(self::$logPrefix . "Payment {$payment['id']} does not belong to order {$params['razorpay_order_id']}.");
			return array('status' => 'ERR', 'code' => 107, 'text' => 'Order mismatch.', 'result' => $payment);
		}

		if ((int) @$payment['amount'] !== (int) $params['amount'] || strtoupper(@$payment['currency']) !== strtoupper($params['currency']))
		{
			$this->log(self::$logPrefix . "Amount/currency mismatch for payment {$payment['id']}.");
			return array('status' => 'ERR', 'code' => 108, 'text' => 'Amount or currency mismatch.', 'result' => $payment);
		}

		if (!in_array(@$payment['status'], array('captured', 'authorized')))
		{
			$this->log(self::$logPrefix . "Payment failed. Status: " . @$payment['status']);
			return array('status' => 'ERR', 'code' => 105, 'text' => 'Payment failed.', 'result' => $payment);
		}

		if (isset($params['foreign_id']))
		{
			$data = array_merge($params, array(
				'razorpay_id' => $payment['id'],
				'order_id' => @$payment['order_id'],
				'created' => @$payment['created_at'],
			));
			self::pjActionSavePayment($params['foreign_id'], $data);
		}

		$this->log(self::$logPrefix . "Payment completed. Payment ID: {$payment['id']}. Order ID: " . @$payment['order_id']);
		return array('status' => 'OK', 'code' => 200, 'text' => 'Payment completed.', 'result' => $payment);
	}

	private static function pjActionSavePayment($foreign_id, $data)
	{
		return pjRazorpayModel::factory()
			->setAttributes(array(
				'foreign_id' => $foreign_id,
				'razorpay_id' => @$data['razorpay_id'],
				'order_id' => @$data['order_id'],
				'amount' => self::fromSubUnits(@$data['amount'], @$data['currency']),
				'currency' => @$data['currency'],
				'description' => @$data['item_name'],
				'created' => preg_match('|^\d{10}$|', @$data['created']) ? sprintf(':FROM_UNIXTIME(%u)', @$data['created']) : @$data['created']
			))
			->insert()
			->getInsertId();
	}

	/*
	 * Converts an amount expressed in the smallest unit of the currency back to a decimal amount.
	 */
	protected static function fromSubUnits($amount, $currency_code)
	{
		if (in_array(strtoupper((string) $currency_code), self::$zeroDecimalCurrencies))
		{
			return $amount;
		}

		return $amount / 100;
	}

	public function pjActionGetDetails()
	{
		$this->setAjax(true);

		if ($this->isXHR() && $this->isLoged())
		{
		    $id = null;
		    if (class_exists('pjInput'))
            {
                $id = $this->_get->toInt('id');
            } elseif (isset($_GET['id']) && (int) $_GET['id'] > 0) {
                $id = (int) $_GET['id'];
            }

			if ($id)
			{
				$this->set('arr', pjRazorpayModel::factory()->find($id)->getData());
			}
		}
	}

	public function pjActionGetRazorpay()
	{
		$this->setAjax(true);

		if ($this->isXHR() && $this->isLoged())
		{
			$pjRazorpayModel = pjRazorpayModel::factory();

			$q = null;
			if (class_exists('pjInput'))
            {
                $q = $this->_get->toString('q');
            } elseif (isset($_GET['q']) && !empty($_GET['q'])) {
                $q = $pjRazorpayModel->escapeStr($_GET['q']);
            }
			if ($q)
			{
				$q = str_replace(array('%', '_'), array('\%', '\_'), $q);
				$pjRazorpayModel
					->where('t1.razorpay_id LIKE', "%$q%")
					->orWhere('t1.order_id LIKE', "%$q%")
					->orWhere('t1.amount LIKE', "%$q%")
					->orWhere('t1.currency LIKE', "%$q%")
					->orWhere('t1.description LIKE', "%$q%")
					->orWhere('t1.created LIKE', "%$q%");
			}

			$column = 'created';
			$direction = 'DESC';
			if (class_exists('pjInput'))
            {
                if (in_array(strtoupper($this->_get->toString('direction')), array('ASC', 'DESC')) && in_array($this->_get->toString('column'), self::$sortableColumns))
                {
                    $column = $this->_get->toString('column');
                    $direction = strtoupper($this->_get->toString('direction'));
                }
            } elseif (isset($_GET['direction'], $_GET['column']) && in_array(strtoupper($_GET['direction']), array('ASC', 'DESC')) && in_array($_GET['column'], self::$sortableColumns)) {
                $column = $_GET['column'];
                $direction = strtoupper($_GET['direction']);
            }

			$total = $pjRazorpayModel->findCount()->getData();
			if (class_exists('pjInput'))
            {
                $rowCount = $this->_get->toInt('rowCount') ?: 10;
    			$page = $this->_get->toInt('page') ?: 1;
            } else {
                $rowCount = isset($_GET['rowCount']) && (int) $_GET['rowCount'] > 0 ? (int) $_GET['rowCount'] : 10;
                $page = isset($_GET['page']) && (int) $_GET['page'] > 0 ? intval($_GET['page']) : 1;
            }
            $pages = ceil($total / $rowCount);
            $offset = ((int) $page - 1) * $rowCount;
			if ($page > $pages)
			{
				$page = $pages;
			}

			$data = $pjRazorpayModel
			    ->orderBy("`$column` $direction")
				->limit($rowCount, $offset)
				->findAll()
				->getData();

			pjAppController::jsonResponse(compact('data', 'total', 'pages', 'page', 'rowCount', 'column', 'direction'));
		}
		exit;
	}

	public function pjActionIndex()
	{
	    if (!$this->isLoged())
        {
            $this->sendForbidden();
            return;
        }

        $this->appendJs('pjRazorpay.js', $this->getConst('PLUGIN_JS_PATH'));
        if (pjObject::getPlugin('pjCms') !== null)
        {
            $this->appendJs('jquery.datagrid.js', $this->getConstant('pjCms', 'PLUGIN_JS_PATH'), false, false);
            $this->appendJs('index.php?controller=pjCms&action=pjActionMessages', PJ_INSTALL_URL, true);
        } else {
            $this->appendJs('jquery.datagrid.js', PJ_FRAMEWORK_LIBS_PATH . 'pj/js/');
            $this->appendJs('index.php?controller=pjAdmin&action=pjActionMessages', PJ_INSTALL_URL, true);
        }
	}

	public function pjActionTest()
	{
		$this->setLayout('pjActionEmpty');

		$data = self::generateTestData(array('amount' => '1.00'));

		$post = array(
			'payment_method' => self::$paymentMethod,
		);

		$order = array(
			'locale_id'     => $this->getLocaleId(),
			'return_url'    => PJ_INSTALL_URL . (class_exists('pjUtil') && method_exists('pjUtil', 'getWebsiteUrl') ? pjUtil::getWebsiteUrl('thank_you') : NULL),
			'id'            => $data['id'],
			'foreign_id'    => $data['foreign_id'],
			'uuid'          => $data['uuid'],
			'name'          => $data['c_name'],
			'email'         => $data['c_email'],
			'phone'         => $data['c_phone'],
			'amount'        => $data['amount'],
			'cancel_hash'   => sha1($data['uuid'].strtotime($data['created']).PJ_SALT),
			'currency_code' => isset($this->option_arr['o_currency']) ? $this->option_arr['o_currency'] : 'USD',
		);

		# Override parameters from query string, e.g. &foreign_id=2
		$qs = array();
		foreach (array_keys($order) as $key)
		{
			if (class_exists('pjInput'))
			{
				if ($this->_get->has($key))
				{
					$order[$key] = $this->_get->raw($key);
					$qs[$key] = $order[$key];
				}
			} else {
				if (array_key_exists($key, $_GET))
				{
					$order[$key] = $_GET[$key];
					$qs[$key] = $order[$key];
				}
			}
		}
		$this->set('qs', $qs);

		$params = self::getFormParams($post, $order);

		$params['recurring_interval'] = 'monthly';

		$this->set('params', $params);
	}
}
