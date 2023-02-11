<?php
if (!defined('CLASS_CISAM')) {
	define('CLASS_CISAM', 1); // Prevent multiple loading

	class CISAM_Resultset extends Resultset
	{
		/**
		 * CISAM Interface
		 *
		 * @var CISAM_Interface
		 */
		var $client = null;

		/**
		 * @param $client
		 **/
		function __construct(& $client)
		{
			$this->client = & $client;
            parent::__construct();
		}

		/**
		 * CISAM_Resultset::execute()
		 *
		 * Uebertraegt eine Abfrage zum Java Client
		 *
		 * @access public
		 * @param string $params Parameter
		 * @return boolean Erfolgsstatus
		 **/
		function execute($params, $program)
		{
			$bResult = false;
//			echo $params;
			$result = $this->client->query($params, $program);
			//echo pray($result);
			$cmd = strtolower(substr(ltrim($params), 1, 1)); // fuehrendes "
			if (!is_array($result)) {
				$error_msg = $this -> client -> getErrormsg() . ' program ' . $program . ' failed with params: ' . $params;
		    	$this -> raiseError(__FILE__, __LINE__, $error_msg);
				$this -> errorStack[] = $this -> client -> getError();
			}
			elseif ($cmd == 'r') {
				if (count($result) > 0) {
				    $this -> rowset = $result;
					$this -> index = 0;
				}
			}
			elseif ($cmd == 'c') {
				if (count($result) and isset($result[0]['count']) > 0) {
				    $this -> rowset[0]['count'] = (int)$result[0]['count'];
					$this -> index = 0;
				}
			}
			elseif ($cmd == 'w') {
				$this -> rowset[0]['last_insert_id'] = $result[0]['last_insert_id'];
				$this -> rowset[0]['id'] = $result[0]['last_insert_id'];
				$this -> index = 0;
			}
			$bResult = true;
			return $bResult;
		}
	}
}