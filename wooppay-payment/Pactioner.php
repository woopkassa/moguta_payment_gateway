<?php

if (!class_exists('WooppaySoapClient')) {
	require('lib/WooppaySoapClient.php');
}

class Pactioner extends Actioner
{
	private static $pluginName = 'wooppay-payment';

	/**
	 * Сохраняет  опции плагина
	 * @return boolean
	 */
	public function saveBaseOption()
	{
		USER::AccessOnly('1,4', 'exit()');
		$this->messageSucces = $this->lang['SAVE_BASE'];
		$this->messageError = $this->lang['NOT_SAVE_BASE'];
		unset($_SESSION['wooppay-paymentAdmin']);
		unset($_SESSION['wooppay-payment']);

		if (!empty($_POST['data'])) {
			MG::setOption(array('option' => self::$pluginName . '-option', 'value' => addslashes(serialize($_POST['data']))));
		}

		return true;
	}

	public function test()
	{
		$this->data["message"] = "ok";
		header('location:http://test.moguta.loc/payment?id=19&pay=success');
		return true;
	}

	public function notification()
	{
		$payment_id = $_GET['payment'];
		$order_id = $_GET['orderID'];
		$this->data["result"] = $order_id;
		if($_GET['key'] == md5($order_id)) {
			$result_payment = $this->getPaymentParams($payment_id);
			$paymentParams = $this->getPaymentParamsDecoded($result_payment);
			$order = $this->getOrder($order_id);
			try {
				$client = new WooppaySoapClient($paymentParams['api_url']);
			} catch (WooppaySoapException $e) {
			}
			$login_request = new CoreLoginRequest();
			$login_request->username = $paymentParams['login_api'];
			$login_request->password = $paymentParams['password_api'];
			try {
				if ($client->login($login_request)) {
					$operationId = $this->getOperationId($order_id);
					if ($operationId) {
						$operationdata_request = new CashGetOperationDataRequest();
						$operationdata_request->operationId = array($operationId);
						$operation_data = $client->getOperationData($operationdata_request);
						$transaction = $operation_data->response->records[0];
						if (!isset($transaction->status) || empty($transaction->status)) {
							exit;
						}

						if ($transaction->status == WooppayOperationStatus::OPERATION_STATUS_DONE) {
							$paymentResult = [
								'paymentOrderId' => $order_id,
								'paymentAmount' => $transaction->sum,
								'paymentID' => $payment_id,
							];

							$payment = new Controllers_Payment();
							$payment->actionWhenPayment($paymentResult);
						} else
							$this->log->write(sprintf('Wooppay callback : счет не оплачен (%s) order id (%s)', $operation_data->response->records[0]->status, $this->request->get['order']));
					} else
						$this->log->write(sprintf('Wooppay order not found : %s order id (%s)', $this->request->get['order'], $this->request->get['order']));
				}
			} catch (Exception $e) {
				$this->log->write(sprintf('Wooppay exception : %s order id (%s)', $e->getMessage(), $this->request->get['order']));
			}
		} else {
			$this->log->write('Wooppay callback : неверный key или order : ' . print_r($_REQUEST, true));
		}
		echo json_encode(['data' => 1]);
		return true;
	}


	public function getPayLink()
	{
		$payment_id = $_POST['paymentId'];
		$mgBaseDir = $_POST['mgBaseDir'];
		$order_id = $_POST['orderId'];

		if($_POST['number'] && !$order_id) {
			$number = $_POST['number'];
			$number = substr($number, 4);
			$order = $this->getOrderByNumber($number);
			$order_id = $order['id'];
			$payment_id = $order['payment_id'];
		}

		$result_payment = $this->getPaymentParams($payment_id);
		$result_order = $this->getOrder($order_id);

		$summ = $this->getOrderTotal($result_order);

		$paymentParams = $this->getPaymentParamsDecoded($result_payment);
		$urlList = $this->getDecodedUrlList($result_payment[4]);

		if ($urlList['success_url'] == '')
			$successURL = $mgBaseDir;

		$notificationURL = "/ajaxrequest?mguniqueurl=action/notification&pluginHandler=wooppay-payment&orderID=" . $order_id . "&payment=" . $payment_id . "&key=" . md5($order_id);

		try {
			$client = new WooppaySoapClient($paymentParams['api_url']);
		} catch (WooppaySoapException $e) {
		}
		$login_request = new CoreLoginRequest();
		$login_request->username = $paymentParams['login_api'];
		$login_request->password = $paymentParams['password_api'];
		try {
			if ($client->login($login_request)) {
				$invoice_request = new CashCreateInvoiceByServiceRequest();
				$invoice_request->referenceId = $paymentParams['prefix'] . $order_id;
				$invoice_request->orderNumber = $order_id;
				$invoice_request->serviceName = $paymentParams['service'];
				$invoice_request->backUrl = $mgBaseDir . $urlList['success_url'] . '&orderID='.$order_id	;
				$invoice_request->requestUrl = $mgBaseDir . $notificationURL ;
				$invoice_request->addInfo = 'Оплата заказа #' . $order_id;
				$invoice_request->amount = $summ;
				$invoice_request->userEmail = $result_order['user_email'];
				$invoice_request->userPhone = $result_order['phone'];
				$invoice_request->deathDate = '';
				$invoice_request->description = '';
				$invoice_request->serviceType = 4;
				$invoice_data = $client->createInvoice($invoice_request);
			} else {
				MG::loger('Не удалось авторизоваться в системе Wooppay');
				return;
			}
		} catch (Exception $exception) {
			MG::loger('Произошла ошибка при создание инвойса');
		}
		$this->addTransaction($order_id, $invoice_data->response->operationId);

		return $this->data["result"] = $invoice_data->response->operationUrl;
	}

	private function getPaymentParams($payment_id)
	{
		$result_payment = array();
		$dbRes = DB::query('
            SELECT *
            FROM `' . PREFIX . 'payment`
            WHERE `id` = \'' . $payment_id . '\'');
		$result_payment = DB::fetchArray($dbRes);

		return $result_payment;
	}

	private function getOrder($order_id)
	{
		$result_order = array();
		$dbRes = DB::query('
            SELECT *
            FROM `' . PREFIX . 'order`
            WHERE `id`=\'' . $order_id . '\'
        ');
		$result_order = DB::fetchAssoc($dbRes);

		return $result_order;
	}

	private function getOrderByNumber($number)
	{
		$result_order = array();
		$dbRes = DB::query('
            SELECT *
            FROM `' . PREFIX . 'order`
            WHERE `number`=\'' . $number . '\'
        ');
		$result_order = DB::fetchAssoc($dbRes);

		return $result_order;
	}

	private function getPaymentParamsDecoded($params)
	{
		$paymentParamDecoded = json_decode($params['paramArray']);
		$result = [];

		foreach ($paymentParamDecoded as $key => $value) {
			if ($key == 'API URL') {
				$result['api_url'] = CRYPT::mgDecrypt($value);
			} elseif ($key == 'Логин API') {
				$result['login_api'] = CRYPT::mgDecrypt($value);
			} elseif ($key == 'Пароль API') {
				$result['password_api'] = CRYPT::mgDecrypt($value);
			} elseif ($key == 'Префикс для ваших заказов') {
				$result['prefix'] = CRYPT::mgDecrypt($value);
			} elseif ($key == 'Имя вашего сервиса в системе платежей Wooppay') {
				$result['service'] = CRYPT::mgDecrypt($value);
			}
		}

		return $result;
	}

	private function getDecodedUrlList($encodedUrlList)
	{
		$urlDecoded = json_decode($encodedUrlList);
		$result = [];

		foreach ($urlDecoded as $key => $value) {
			if ($key == "result URL:") {
				$result['result_url'] = $value;
			} elseif ($key == "success URL:") {
				$result['success_url'] = $value;
			} elseif ($key == "fail URL:") {
				$result['fail_url'] = $value;
			}
		}

		return $result;
	}

	private function getOrderTotal($result_order)
	{
		if (isset($result_order['delivery_cost']) and $result_order['delivery_cost'] > 0) {
			$summ = $result_order['summ'] + $result_order['delivery_cost'];
		} else {
			$summ = $result_order['summ'];
		}

		return $summ;
	}

	private function addTransaction($order_id, $operation_id)
	{
		$sql = "INSERT INTO `" . PREFIX . "transaction` VALUES ('" . $order_id . "', '" . $operation_id . "', CURRENT_TIMESTAMP);";
		DB::query($sql);
	}

	private function getOperationId($order_id)
	{
		$result = array();
		$dbRes = DB::query('
            SELECT *
            FROM `' . PREFIX . 'transaction`
            WHERE `id`=\'' . $order_id . '\'
        ');
		$result = DB::fetchAssoc($dbRes);

		return $result['transaction_id'];
	}
}
