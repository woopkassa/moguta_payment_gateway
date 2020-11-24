<?php
/*
  Plugin Name: Платежная система Wooppay
  Description: Плагин для оплаты через платежную систему Wooppay.
  Author: Wooppay
  Version: 1.0.0
 */
new WooppayPayment;

class WooppayPayment
{
	const PLUGIN_HASH = 'ff7e64a5484726688a66bcb852a2dfb3'; // md5('wooppay')

	private static $pluginName = 'wooppay-payment';
	private static $lang = array(); // массив с переводом плагина
	private static $path = '';
	private static $options = '';

	public function __construct()
	{
		mgActivateThisPlugin(__FILE__, array(__CLASS__, 'activate'));
		mgDeactivateThisPlugin(__FILE__, array(__CLASS__, 'deactivate'));
		mgAddAction(__FILE__, array(__CLASS__, 'pageSettingsPlugin'));

//		self::$pluginName = PM::getFolderPlugin(__FILE__);
		self::$path = PLUGIN_DIR . self::$pluginName;
		self::$lang = PM::plugLocales(self::$pluginName);
		self::$options = unserialize(stripcslashes(MG::getSetting('wooppay-payment-option')));

		$wooppayPluginId = self::getPaymentForPlugin();

		if (URL::isSection('order')) {
			if($_POST['payment'] || $_POST['paymentId'] || $_GET['addOrderOk'] || $_GET['orderID']) {
				if($_GET['orderID']) {
					$_SESSION['orderID'] = $_GET['orderID'];
					$_SESSION['paymentID'] = $_GET['paymentId'];
				}
				if(!empty($_POST)) {
					$_SESSION['paymentID'] = $_POST['payment'] ?? $_POST['paymentId'];
					$_SESSION['orderID'] = $_POST['orderID'];
				}
				echo '<div class="payment-data" style="display: none"><span id="payment-id">' . $_SESSION['paymentID'] . '</span><span id="order-id">' . $_SESSION['orderID'] . '</span><span id="wooppay_id">' . $wooppayPluginId . '</span></div>';
				mgAddMeta('<script src="' . SITE . '/' . self::$path . '/js/script.js"></script>');
			}
		}
		if (URL::isSection('payment')) {
			$order_id = $_GET['orderID'];
			$order = new Models_Order();


			if ($_GET['pay'] == 'success') {
				$orderInfo = $order->getOrder(' id = ' . $order_id);
				if($orderInfo[$order_id]['paided'] == 1) {
					$msg = '<strong>Вы успешно оплатили заказ № ' . $orderInfo[$order_id]['number'] . '</strong>';
					mgAddMeta('<script>$(\'.c-alert\').html(\''. $msg .'\');</script>');
				}
			}
		}

	}


	static function activate()
	{
		USER::AccessOnly('1,4', 'exit()');
		self::setDefultPluginOption();
	}


	static function deactivate(){
		USER::AccessOnly('1,4','exit()');
		self::removePluginOption();
	}


	static function pageSettingsPlugin()
	{
		USER::AccessOnly('1,4', 'exit()');
		unset($_SESSION['payment']);
		echo '
          <link rel="stylesheet" href="' . SITE . '/' . self::$path . '/css/style.css" type="text/css" />
          <script type="text/javascript">
            includeJS("' . SITE . '/' . self::$path . '/js/script.js");          
          </script> ';

		$lang = self::$lang;
		$pluginName = self::$pluginName;
		$options = self::$options;
		$data['propList'] = self::getPropList();

		include 'pageplugin.php';
	}

	private static function getPropList()
	{
		$arResult = array();
		$sql = '
            SELECT `id`, `name` 
            FROM `' . PREFIX . 'property` 
            WHERE `activity` = 1 AND `type` = \'string\'';

		if ($dbRes = DB::query($sql)) {
			while ($result = DB::fetchAssoc($dbRes)) {
				$arResult[$result['id']] = $result['name'];
			}
		}

		return $arResult;
	}

	private static function setDefultPluginOption()
	{
		USER::AccessOnly('1,4', 'exit()');
		$paymentId = self::getPaymentForPlugin();

		if(empty($paymentId)){
			$paymentId = self::setPaymentForPlugin();
		}
	}

	private static function removePluginOption(){
		USER::AccessOnly('1,4','exit()');
		$paymentId = self::getPaymentForPlugin();
		if($paymentId){
			$paymentId = self::removePaymentForPlugin();
		}
	}

	static function removePaymentForPlugin(){
		USER::AccessOnly('1,4','exit()');

		$dropTable = 'DROP TABLE `' . PREFIX . 'transaction`';

		DB::query($dropTable);

		$sql = '
			  DELETE FROM `' . PREFIX . 'payment`
			  WHERE `add_security` = ' . DB::quote(self::PLUGIN_HASH);

		DB::query($sql);
	}

	static function getPaymentForPlugin()
	{
		$result = array();
		$dbRes = DB::query('
          SELECT id
          FROM `' . PREFIX . 'payment`
          WHERE `add_security` = \''. self::PLUGIN_HASH .'\'');

		if ($result = DB::fetchAssoc($dbRes)) {
			$sql = '
            UPDATE `' . PREFIX . 'payment` 
            SET `activity` = 1 
            WHERE `add_security` = \''. self::PLUGIN_HASH . '\'';
			DB::query($sql);

			return $result['id'];
		}
	}

	static function setPaymentForPlugin()
	{
		USER::AccessOnly('1,4', 'exit()');

		$tableSql = 'CREATE TABLE ' . PREFIX . 'transaction (id INT NOT NULL, transaction_id VARCHAR(40) NOT NULL, date TIMESTAMP DEFAULT CURRENT_TIMESTAMP);';
		DB::query($tableSql);

		$options = '{"API URL":"",' .
			'"Логин API":"",' .
			'"Пароль API":"",' .
			'"Префикс для ваших заказов":"",' .
			'"Имя вашего сервиса в системе платежей Wooppay":""}';

		$sql = '
            INSERT INTO ' . PREFIX . 'payment (`name`, `activity`,`paramArray`, `urlArray`, `add_security`) VALUES
            (\'Wooppay\', 1, \'' . $options . '\', \'{}\', \''. self::PLUGIN_HASH .'\')';

		if (DB::query($sql)) {

			$thisId = DB::insertId();
			$sql = '
                UPDATE `' . PREFIX . 'payment` 
                SET `urlArray` = \'{"result URL:":"/payment?id=' . $thisId . '&pay=result","success URL:":"/payment?id=' . $thisId . '&pay=success","fail URL:":"/payment?id=' . $thisId . '&pay=fail"}\'
                WHERE `id` = \'' . $thisId . '\'';
			DB::query($sql);

			return $thisId;
		}
	}


}

?>