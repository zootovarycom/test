<?php
    class osC_onePageCheckout {
		var $show_choose_audience, $title, $content, $destination, $auth, $stockValue;

		function __construct() {
			$this->buildSession();
			$this->show_choose_audience = false;
			$this->title = '';
			$this->content = '';
			$this->destination = [];
			$this->auth = false;
			$this->stockValue = [];
		}

        function reset() {
            $this->buildSession(true);
        }

        function buildSession($forceReset = false) {
            global $onepage, $payment, $shipping, $customer_id, $sendto, $billto;
            
            if (!tep_session_is_registered('onepage') || $forceReset === true) {
                if (tep_session_is_registered('onepage')) {
                    tep_session_unregister('onepage');
                }
                if (tep_session_is_registered('add_bonus')) {
                    tep_session_unregister('add_bonus');
                }
                if (tep_session_is_registered('customer_shopping_points_spending')) {
                    tep_session_unregister('customer_shopping_points_spending');
                }
                if (tep_session_is_registered('payment')) {
                    tep_session_unregister('payment');
                }
                if (tep_session_is_registered('shipping')) {
                    tep_session_unregister('shipping');
                }
                if (tep_session_is_registered('billto')) {
                    tep_session_unregister('billto');
                }
                if (tep_session_is_registered('sendto')) {
                    tep_session_unregister('sendto');
                }
                if (tep_session_is_registered('coupon')) {
                    tep_session_unregister('coupon');
                }
                if (tep_session_is_registered('customer_shopping_points_spending')) {
                    tep_session_unregister('customer_shopping_points_spending');
                }

                $onepage = array(
                    'info'            => array(
                        'payment_method' => '', 'shipping_method' => '', 'send_sms' => '', 'send_email' => '', 'paid_delivery' => '', 'give_card' => '',
                        'comments'       => '', 'call_before_shipping' => '', 'dont_call_before_shipping' => '', 'failed_to_contact' => '', 'concierge' => '', 'is_lift' => '', 'coupon' => '',
                        'schedule_value' => '', 'shipping_date' => '', 'shipping_time' => '', 'address_id' => '', 'mid'            => '',
                    ),
                    'customer'        => array(
                        'firstname' => '', 'lastname' => '', 'company' => '', 'street_address' => '', 'district' => '',
                        'house'     => '', 'flat' => '', 'floor' => '', 'entrance' => '', 'suburb'    => '', 'city' => '', 'postcode' => '', 'state' => '',
                        'zone_id'   => '', 'country' => array('id' => '', 'title' => '', 'iso_code_2' => '', 'iso_code_3' => ''), 'format_id' => '',
                        'telephone' => '', 'additional_phone' => '', 'email_address' => '', 'password' => '', 'newsletter' => '',
                    ),
                    'delivery'        => array(
                        'firstname'  => '', 'lastname' => '', 'company' => '', 'street_address' => '', 'district' => '',
                        'house'      => '', 'flat' => '', 'floor' => '', 'entrance' => '',
                        'suburb'     => '', 'city' => '', 'postcode' => '', 'state' => '',
                        'zone_id'    => '', 'country' => array('id' => '', 'title' => '', 'iso_code_2' => '', 'iso_code_3' => ''),
                        'country_id' => '', 'format_id' => '', 'shipping_module' => '',
                    ),
                    'billing'         => array(
                        'firstname'  => '', 'lastname' => '', 'company' => '', 'street_address' => '', 'district' => '',
                        'house'      => '', 'flat' => '', 'floor' => '', 'entrance' => '',
                        'suburb'     => '', 'city' => '', 'postcode' => '', 'state' => '',
                        'zone_id'    => '', 'country' => array('id' => '', 'title' => '', 'iso_code_2' => '', 'iso_code_3' => ''),
                        'country_id' => '', 'format_id' => '',
                    ),
                    'create_account'  => true,
                    'shippingEnabled' => true,
                );
                $payment = null;
                $shipping = null;
                $sendto = null;
                $billto = null;
                tep_session_register('onepage');
                tep_session_register('payment');
                tep_session_register('shipping');
                tep_session_register('billto');
                tep_session_register('sendto');
                
//                if (isset($_SESSION['customer_id']) && $_SESSION['customer_id'] == 172441) {
//                    error_log('[checkout log] buildSession called: ' . tep_session_id() . "\n", 3, DIR_FS_CATALOG . 'includes/checkout-error-sG7HsYf5dJr47NbG.log');
//                    error_log('[checkout log] onepage registered: ' . tep_session_is_registered('onepage') . "\n", 3, DIR_FS_CATALOG . 'includes/checkout-error-sG7HsYf5dJr47NbG.log');
//                    error_log('[checkout log] $forceReset: ' . $forceReset . "\n", 3, DIR_FS_CATALOG . 'includes/checkout-error-sG7HsYf5dJr47NbG.log');
//                    error_log('[checkout log] ================================================' . "\n", 3, DIR_FS_CATALOG . 'includes/checkout-error-sG7HsYf5dJr47NbG.log');
//                }
            }


            if (empty($onepage['customer']['postcode'])) {
                $onepage['customer']['postcode'] = null;
            }
            if (empty($onepage['billing']['postcode'])) {
                $onepage['billing']['postcode'] = null;
            }
            if (empty($onepage['delivery']['postcode'])) {
                $onepage['delivery']['postcode'] = null;
            }

            if (tep_session_is_registered('customer_id') && is_numeric($customer_id)) {
                $onepage['create_account'] = false;

                $QcustomerEmail = tep_db_query("select customers_firstname, customers_email_address, customers_telephone, customers_fax, customers_default_address_id from " . TABLE_CUSTOMERS . " where customers_id = '" . (int)$customer_id . "'");
                $customerEmail = tep_db_fetch_array($QcustomerEmail);
                $onepage['customer']['email_address'] = $customerEmail['customers_email_address'];
                $onepage['customer']['telephone'] = $customerEmail['customers_telephone'];
                $onepage['customer']['additional_phone'] = $customerEmail['customers_fax'];
                $onepage['customer']['firstname'] = $customerEmail['customers_firstname'];
            }
            
            $onepage = $_SESSION['onepage'];
        }

        function fixZoneName($zone_id, $country, &$state) {
            if ($zone_id > 0 && $country > 0) {
                $zone_query = tep_db_query("select distinct zone_name from " . TABLE_ZONES . " where zone_country_id = '" . (int)$country . "' and zone_id = '" . tep_db_input($zone_id) . "' ");
                if (tep_db_num_rows($zone_query) == 1) {
                    $zone = tep_db_fetch_array($zone_query);
                    $state = $zone['zone_name'];
                }
            }
        }

		/**
		 * Копирует заданные поля из $onepage['info'] в $order->info с проверкой на tep_not_null().
		 * По флагу $registerSession регистрирует переменные в сессии.
		 *
		 * @param array $onepage
		 * @param object $order
		 * @param array<string,bool> $fields Массив ['field_name' => registerSessionFlag]
		 */
		function copyCheckoutInfoFields(array $onepage, object $order, array $fields): void {
			foreach ($fields as $field => $registerSession) {
				$value = $onepage['info'][$field] ?? null;

				if (tep_not_null($value)) {
					if ($registerSession && !tep_session_is_registered($field)) {
						tep_session_register($field);
					}

					$order->info[$field] = $value;
				}
			}
		}

		function loadSessionVars($type = 'checkout') {
			global $order,
				   $onepage,
				   $payment,
				   $shipping,
				   $coupon,
				   $comments,
				   $call_before_shipping,
				   $failed_to_contact,
				   $concierge,
				   $is_lift,
				   $send_sms,
				   $send_email,
				   $paid_delivery,
				   $give_card,
				   $mid;

			if (empty($onepage['info'])) {
				return;
			}

			// Платёжный метод
			if (tep_not_null($onepage['info']['payment_method'] ?? null)) {
				$payment = $onepage['info']['payment_method'];

				if (isset($GLOBALS[$payment])) {
					$pModule = $GLOBALS[$payment];
					$order->info['payment_method'] 			= $pModule->public_title ?? $pModule->title;
					$order->info['payment_method_code'] 	= $pModule->code;
					$order->info['payment_method_merchant'] = method_exists($pModule, 'getMerchantId') ? $pModule->getMerchantId() : null;
					$order->info['legal_entities_bank_id'] 	= $pModule->payment_bank_id ?? null;

					if (!empty($pModule->order_status)) {
						$order->info['order_status'] = (int)$pModule->order_status;
					}
				}
			}

			// Метод доставки
			if (tep_not_null($onepage['info']['shipping_method'] ?? null)) {
				$shipping = $onepage['info']['shipping_method'];
				$order->info['shipping_method'] = $shipping['title'];
				$order->info['shipping_cost'] 	= $shipping['cost'];
			}

			// Копируем значения из $onepage['info'] в $order->info
			$fields = [
				'schedule_value'            => false,
				'shipping_date'             => false,
				'shipping_time'             => false,
				'address_id'                => false,
				'comments'                  => true,
				'dont_call_before_shipping' => true,
				'call_before_shipping'      => true,
				'failed_to_contact'         => true,
				'concierge'                 => true,
				'is_lift'                   => true,
				'send_sms'                  => true,
				'send_email'                => true,
				'paid_delivery'             => true,
				'give_card'                 => true,
				'mid'                       => true,
			];

			$this->copyCheckoutInfoFields($onepage, $order, $fields);

			// Сохраняем переменные в глобальные переменные
			$globalFields = array_filter($fields, fn($r) => $r === true);

			foreach (array_keys($globalFields) as $field) {
				$$field = $onepage['info'][$field] ?? '';
			}

			//BOF KGT
			if (MODULE_ORDER_TOTAL_DISCOUNT_COUPON_STATUS == 'true') {
				if (!empty($onepage['info']['coupon']) && tep_not_null($onepage['info']['coupon'])) {
					$order->info['coupon'] = $onepage['info']['coupon'];
					if (!tep_session_is_registered('coupon')) {
						tep_session_register('coupon');
					}
				}
			}
			//EOF KGT

			// customer и delivery fallback
			if ($onepage['customer']['firstname'] == '' && is_array($onepage['customer']) && is_array($onepage['billing'] ?? [])) {
				$onepage['customer'] = array_merge($onepage['customer'], $onepage['billing'] ?? []);
			}
			if ($onepage['delivery']['firstname'] == '' && is_array($onepage['delivery']) && is_array($onepage['billing'] ?? [])) {
				$onepage['delivery'] = array_merge($onepage['delivery'], $onepage['billing'] ?? []);
			}

			// Поправка зоны
			if (ACCOUNT_STATE == 'true') {
				$this->fixZoneName($onepage['customer']['zone_id'], $onepage['customer']['country']['id'], $onepage['customer']['state']);
				$this->fixZoneName($onepage['billing']['zone_id'], $onepage['billing']['country']['id'], $onepage['billing']['state']);
				$this->fixZoneName($onepage['delivery']['zone_id'], $onepage['delivery']['country']['id'], $onepage['delivery']['state']);
			}
            
			// Передаём итог в $order
			$order->customer = $onepage['customer'];
			$order->billing = $onepage['billing'];
			$order->delivery = $_SESSION['onepage']['delivery'];

		}

        function init() {
            global $admins_ip;
            
            $this->verifyContents();
            
//            $requiredFields = [
//                'billing',
//                'delivery',
//                'info',
//                'customer'
//            ];
//            $isEmpty = array_filter($requiredFields, fn($field) => empty($_SESSION['onepage'][$field]));
//
//            if (tep_session_is_registered('onepage') && count($isEmpty)) {
//                error_log('[checkout log] call reset!' . "\n", 3, DIR_FS_CATALOG . 'includes/checkout-error-sG7HsYf5dJr47NbG.log');
//                error_log('[checkout log] shipping: ' . var_export($_SESSION['shipping'] ?? null, true) . "\n", 3, DIR_FS_CATALOG . 'includes/checkout-error-sG7HsYf5dJr47NbG.log');
//                error_log('[checkout log] onepage: ' . var_export($_SESSION['onepage'], true) . "\n", 3, DIR_FS_CATALOG . 'includes/checkout-error-sG7HsYf5dJr47NbG.log');
//                error_log('[checkout log] ================================================' . "\n", 3, DIR_FS_CATALOG . 'includes/checkout-error-sG7HsYf5dJr47NbG.log');
//                $this->reset();
//            }
            
//            if (in_array($_SERVER['REMOTE_ADDR'], $admins_ip)) {
//                $requiredFields = [
//                    'delivery',
//                    'info',
//                    'customer'
//                ];
//                $isEmpty = array_filter($requiredFields, fn($field) => empty($_SESSION['onepage'][$field]));
//
//                if (tep_session_is_registered('onepage') && count($isEmpty)) {
//                    error_log('[checkout log] call reset!' . "\n", 3, DIR_FS_CATALOG . 'includes/checkout-error-sG7HsYf5dJr47NbG.log');
//                    error_log('[checkout log] shipping: ' . var_export($_SESSION['shipping'] ?? null, true) . "\n", 3, DIR_FS_CATALOG . 'includes/checkout-error-sG7HsYf5dJr47NbG.log');
//                    error_log('[checkout log] onepage: ' . var_export($_SESSION['onepage'], true) . "\n", 3, DIR_FS_CATALOG . 'includes/checkout-error-sG7HsYf5dJr47NbG.log');
//                    error_log('[checkout log] ================================================' . "\n", 3, DIR_FS_CATALOG . 'includes/checkout-error-sG7HsYf5dJr47NbG.log');
//                    $this->reset();
//                }
//            } else {
                if (!isset($_GET['payment_error'])) {
                    $this->reset();
                }
//            }

            $this->setDefaultSendTo();
            $this->setDefaultBillTo();

            $this->removeCCGV();
        }

        function checkDuplicatePhoneRegistration($phone, $need_authorisation) {
			global $customer_id;

			$success = 'true';
			$errMsg = '';

			$phone = str_replace(array(' ', '-', '(', ')'), "", tep_db_input(format_phone($phone)));
			$sql = "SELECT customers_id FROM " . TABLE_CUSTOMERS . " WHERE (INSTR(clean_customers_telephone, '{$phone}') > 0 OR INSTR(clean_customers_fax, '{$phone}') > 0)";

			if ($need_authorisation == 'false' && tep_not_null($customer_id, true)) {
				$sql .= " and customers_id != " . $customer_id;
			}

			$Qcheck = tep_db_query($sql);
			if (tep_db_num_rows($Qcheck)) {
				$success = 'false';
				$errMsg = ($need_authorisation == 'true' ? TEXT_PHONE_EXISTS . ' ' . TEXT_PHONE_EXISTS2 . ' ' . TEXT_PHONE_EXISTS3 : TEXT_PHONE_EXISTS4);
			}

			return '{
                    "success": "' . $success . '",
                    "errMsg": "' . $errMsg . '"
                }';
		}

		function checkEmailAddress($emailAddress, $ajax = true) {
			$success = 'true';
			$errMsg = '';
			$need_authorisation = 'false';

			require_once('includes/functions/validations.php');
			if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL) || tep_validate_email($emailAddress) === false) {
				$success = 'false';
				$errMsg = TEXT_EMAIL_WRONG;
			} else {
				$Qcheck = tep_db_query("select customers_id from " . TABLE_CUSTOMERS . " where customers_email_address = '" . tep_db_input($emailAddress) . "'");
				if (tep_db_num_rows($Qcheck)) {
					$success = 'false';
					$errMsg = TEXT_EMAIL_EXISTS . ' ' . TEXT_EMAIL_EXISTS2 . ' ' . TEXT_EMAIL_EXISTS3;
					$need_authorisation = 'true';
				}
			}

			if ($ajax == true) {
				return '{
                    "success": "' . $success . '",
                    "errMsg": "' . $errMsg . '",
                    "need_authorisation": "' . $need_authorisation . '"
                }';
			} else {
				return $success;
			}
		}

        function checkPhone($phone_number) {
            $phone_number = str_replace('+38', '', $phone_number);
            $success = boolval(phone_number_check($phone_number));
            return json_encode($success);
        }

        function getAjaxStateFieldAddress($manualCid = false, $zone_id = 0, $state = '') {
            global $onepage;
            $country = $manualCid;
            $name = 'state';
            $key = '';
            $html = '';
            $check_query = tep_db_query("select count(*) as total from " . TABLE_ZONES . " where zone_country_id = '" . (int)$country . "'");
            $check = tep_db_fetch_array($check_query);
            if ($check['total'] > 0) {
                $zones_array = array(
                    array('id' => '', 'text' => TEXT_PLEASE_SELECT),
                );
                $zones_query = tep_db_query("select zone_id, zone_code, zone_name from " . TABLE_ZONES . " where zone_country_id = '" . (int)$country . "' order by zone_name");
                $selected = '';
                while ($zones_values = tep_db_fetch_array($zones_query)) {
                    if ($zone_id > 0 || !empty($state)) {
                        if ($zone_id == $zones_values['zone_id']) {
                            $selected = $zones_values['zone_name'];
                        } elseif (!empty($state) && $state == $zones_values['zone_name']) {
                            $selected = $zones_values['zone_name'];
                        } elseif (isset($_POST['curValue']) && $_POST['curValue'] == $zones_values['zone_name']) {
                            $selected = $zones_values['zone_name'];
                        }
                    }
                    $zones_array[] = array('id' => $zones_values['zone_name'], 'text' => $zones_values['zone_name']);
                }
                $html .= tep_draw_pull_down_menu($name, $zones_array, $selected, 'class="required" style="width:70%;float:left;"');
            } else {
                $html .= tep_draw_input_field($name, (!empty($state) ? $state : ''), 'class="required" style="width:70%;float:left;"');
            }
            return $html;
        }

        function setPaymentMethod($method) {
            global $payment_modules, $language, $order, $cart, $payment, $onepage, $customer_shopping_points_spending;

            $onepage = $_SESSION['onepage'];
            $payment = $method;

            if (!tep_session_is_registered('payment')) {
                tep_session_register('payment');
            }
            $onepage['info']['payment_method'] = $method;
            $order->info['payment_method'] = $GLOBALS[$payment]->title;
            $confirmation = $GLOBALS[$payment]->selection();

            $inputFields = '';
            if ($confirmation !== false) {
                if (isset($confirmation['fields']) && tep_not_null($confirmation['fields']) && is_array($confirmation['fields'])) {
                    for ($i = 0, $n = sizeof($confirmation['fields']); $i < $n; $i++) {
                        $inputFields .= '<tr>' .
                            '<td width="10"></td>' .
                            '<td class="main" width="150px">' . $confirmation['fields'][$i]['title'] . '</td>' .
                            '<td></td>' .
                            '<td class="main" width="350px">' . $confirmation['fields'][$i]['field'] . '</td>' .
                            '<td width="10"></td>' .
                            '</tr>';
                    }
                }
                if ($inputFields != '') {
                    $inputFields = '<tr class="paymentFields">' .
                        '<td width="10"></td>' .
                        '<td colspan="2"><table border="0" cellspacing="0" cellpadding="2">' .
                        $inputFields .
                        '</table></td>' .
                        '<td width="10"></td>' .
                        '</tr>';
                }
            }

            $_SESSION['payment'] = $payment;
            $_SESSION['onepage'] = $onepage;

            $input_fields = array($inputFields);
            return '{
                "success": "true",
                "inputFields": ' . json_encode($input_fields) . '
            }';
        }

        function setShippingMethod($method = '') {
            global $shipping_modules, $language, $order, $cart, $shipping, $onepage, $order_total_modules, $ot_total, $admins_ip;
            $onepage = $_SESSION['onepage'];
            if (defined('MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING') && MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING == 'true') {
                $pass = false;

                switch (MODULE_ORDER_TOTAL_SHIPPING_DESTINATION) {
                    case 'national':
                        if ($order->delivery['country_id'] == STORE_COUNTRY) {
                            $pass = true;
                        }
                        break;
                    case 'international':
                        if ($order->delivery['country_id'] != STORE_COUNTRY) {
                            $pass = true;
                        }
                        break;
                    case 'both':
                        $pass = true;
                        break;
                }

                // disable free shipping for Alaska and Hawaii
                $zone_code = tep_get_zone_code($order->delivery['country']['id'], $order->delivery['zone_id'], '');
                if (in_array($zone_code, array('AK', 'HI'))) {
                    $pass = false;
                }

                $free_shipping = false;
                if ($pass == true && $order->info['total'] >= MODULE_ORDER_TOTAL_SHIPPING_FREE_SHIPPING_OVER) {
                    $free_shipping = true;
                    include(DIR_WS_LANGUAGES . $language . '/modules/order_total/ot_shipping.php');
                }
            } else {
                $free_shipping = false;
            }

            if (!tep_session_is_registered('shipping')) {
                tep_session_register('shipping');
            }

            //            $shipping = false;
            //            $onepage['info']['shipping_method'] = false;

            if (tep_count_shipping_modules() > 0 || $free_shipping == true) {
                if (strpos($method, '_')) {
                    $shipping_name = $method;

                    [$module, $method] = explode('_', $shipping_name);
                    global $$module;
                    if (is_object($$module) || $shipping == 'free_free') {
                        $quote = $shipping_modules->quote($method, $module);
                        if (isset($quote['error'])) {
                            unset($shipping);
                            error_log('[checkout] unset $shipping: ' . var_export($quote, true), 3, DIR_FS_CATALOG . 'includes/checkout-error-sG7HsYf5dJr47NbG.log');
                        } else {
                            if (isset($quote[0]['methods'][0]['title']) && isset($quote[0]['methods'][0]['cost']) || $shipping_name == 'free_free') {
                                $shipping = array(
                                    'id'      => $shipping_name,
                                    'title'   => (($shipping == 'free_free') ? FREE_SHIPPING_TITLE : $quote[0]['module'] . ''),
                                    'cost'    => (($shipping == 'free_free') ? '0' : $quote[0]['methods'][0]['cost']),
                                    'zone_id' => isset($quote[0]['field']) ? $quote[0]['field'][0]['zone_id'] ?? null : ONEPAGE_DEFAULT_COUNTRY,
                                );
                                $onepage['info']['shipping_method'] = $shipping;
                                $onepage['customer']['shipping_module'] = $method;
								$onepage['delivery']['shipping_module'] = $method;
								$onepage['delivery']['departments_id'] = tep_not_null($order->delivery['district']) ? explode(':', $order->delivery['district'])[1] ?? '' : '';

                                $order->customer['shipping_module'] = $method;
                                $order->delivery['shipping_module'] = $method;
                                $order->delivery['departments_id'] = $onepage['delivery']['departments_id'];

								$order->info['shipping_cost'] = $shipping['cost'];
								$order->info['shipping_method'] = $shipping['title'];
								$order->info['shipping_method_code'] = $method;
                            } else {
                                error_log('[checkout]  $shipping problem: ' . var_export($quote, true), 3, DIR_FS_CATALOG . 'includes/checkout-error-sG7HsYf5dJr47NbG.log');
                            }
                        }
                    } else {
                        unset($shipping);
                        error_log('[checkout] is_object($$module) problem: ' . var_export($$module, true), 3, DIR_FS_CATALOG . 'includes/checkout-error-sG7HsYf5dJr47NbG.log');
                    }
                }
            }

            if (!empty($_POST['address_id'])) {
                $order->info['address_id'] = $_POST['address_id'];
                $onepage['info']['address_id'] = $order->info['address_id'];
            }

            $_SESSION['shipping'] = $shipping;
            $_SESSION['onepage'] = $onepage;

            $responce = [
				'success'  => true,
				'shipMsg'  => $this->checkFreeShipForKiev(),
				'minOrder' => $this->checkMinOrderForKiev(),
			];
            
            if (in_array($_SERVER['REMOTE_ADDR'], $admins_ip)) {
				$responce['debug'] = [
					'onepage'  => $onepage,
					'shipping' => $shipping,
					'order'    => $order,
				];
            }

            return json_encode($responce);
        }

        function setCheckoutAddress($action) {
            global $order, $onepage, $customer_id, $languages_id, $admins_ip;

            $onepage = $_SESSION['onepage'];
            
            if (!empty($_POST['address_id'])) {
                $order->info['address_id'] = (int)$_POST['address_id'];
                $onepage['info']['address_id'] = $order->info['address_id'];
				$_SESSION['sendto'] = $onepage['info']['address_id'];
                $address_book_query = tep_db_query("select entry_firstname, entry_lastname, entry_street_address, entry_zone_id, entry_house, entry_flat, entry_floor, entry_entrance, entry_shipping_method
                                                    from address_book
                                                    where address_book_id = '" . (int)$_POST['address_id'] . "'");
                $address_book_arr = tep_db_fetch_array($address_book_query);

                $_SESSION['onepage']['delivery']['firstname'] = $address_book_arr['entry_firstname'];
                $_SESSION['onepage']['delivery']['lastname'] = $address_book_arr['entry_lastname'];
                $_SESSION['onepage']['delivery']['street_address'] = $address_book_arr['entry_street_address'];
                $_SESSION['onepage']['delivery']['country_id'] = $address_book_arr['entry_country_id'] ?? null;
                $_SESSION['onepage']['delivery']['district'] = $address_book_arr['entry_zone_id'];
                $_SESSION['onepage']['delivery']['house'] = $address_book_arr['entry_house'];
                $_SESSION['onepage']['delivery']['flat'] = $address_book_arr['entry_flat'];
                $_SESSION['onepage']['delivery']['floor'] = $address_book_arr['entry_floor'];
                $_SESSION['onepage']['delivery']['entrance'] = $address_book_arr['entry_entrance'];
                $_SESSION['onepage']['delivery']['shipping_module'] = $address_book_arr['entry_shipping_method'];

                $department = explode(':', $address_book_arr['entry_zone_id'])[1] ?? false;
                if (!empty($department)) {
                    $order->delivery['shipping_module'] = $department;
                }

            } elseif (isset($_POST['billing_country']) && empty($_POST['address_id'])) {
                $_SESSION['onepage']['delivery']['district'] = '';
                $_SESSION['onepage']['delivery']['house'] = '';
                $_SESSION['onepage']['delivery']['flat'] = '';
                $_SESSION['onepage']['delivery']['floor'] = '';
                $_SESSION['onepage']['delivery']['entrance'] = '';
                $_SESSION['onepage']['delivery']['country_id'] = (int)$_POST['billing_country'];
                $_SESSION['shipping'] = false;
                $_SESSION['info']['payment_method'] = false;
                $_SESSION['onepage']['delivery']['address_id'] = $order->info['address_id'] ?? null;
                $order->info['address_id'] = '';
                $onepage['info']['address_id'] = '';

            }

            if ($action == 'setSendTo' && (!isset($_POST['shipping_country']) || !isset($_POST['departments_id']) || !tep_not_null($_POST['shipping_country']) || !tep_not_null($_POST['departments_id']))) {
                $prefix = 'billing_';
            } else {
                $prefix = ($action == 'setSendTo' ? 'shipping_' : 'billing_');
            }

            if (ACCOUNT_COMPANY == 'true')
                $company = tep_db_prepare_input($_POST[$prefix . 'company']);
            if (ACCOUNT_SUBURB == 'true')
                $suburb = tep_db_prepare_input($_POST[$prefix . 'suburb']);

            if (!isset($_POST[$prefix . 'zipcode'])) {
                if (ONEPAGE_AUTO_SHOW_BILLING_SHIPPING == 'True') {
                    $zip_code = tep_db_prepare_input(ONEPAGE_AUTO_SHOW_DEFAULT_ZIP);
                }
            } else {
                $zip_code = tep_db_prepare_input($_POST[$prefix . 'zipcode']);
            }
            if (empty($_POST[$prefix . 'country']) && empty($_SESSION['onepage']['delivery']['country']['id'])) {
                if (ONEPAGE_AUTO_SHOW_BILLING_SHIPPING == 'True') {
                    $country = tep_db_prepare_input(ONEPAGE_AUTO_SHOW_DEFAULT_COUNTRY);
                }
            } else {
                $country = isset($_POST[$prefix . 'country']) ? (int)tep_db_prepare_input($_POST[$prefix . 'country']) : (int)$_SESSION['onepage']['delivery']['country']['id'];
                $country = tep_db_prepare_input($country);
            }

			$check_query = tep_db_query("select count(*) as total from " . TABLE_ZONES . " where zone_country_id = '" . (int)$country . "'");
			$check = tep_db_fetch_array($check_query);
			$entry_state_has_zones = $check['total'] > 0;

			if (!$entry_state_has_zones) {
				tep_mail_html('', 'debug@zootovary.ua', 'Not found zones! country_id = ' . $country, json_encode($onepage, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 'DEBUG ZOOTOVARY.UA', 'debug@zootovary.ua');
			}

            if (ACCOUNT_STATE == 'true') {
                if (isset($_POST[$prefix . 'zone_id'])) {
                    $zone_id = tep_db_prepare_input($_POST[$prefix . 'zone_id']);
                } else {
                    if (!isset($_POST[$prefix . 'zone_id'])) {
                        if (ONEPAGE_AUTO_SHOW_BILLING_SHIPPING == 'True') {
                            if ($country == ONEPAGE_AUTO_SHOW_DEFAULT_COUNTRY)
                                $zone_id = tep_db_prepare_input(ONEPAGE_AUTO_SHOW_DEFAULT_STATE);
                        }
                    } else {
                        $zone_id = false;
                    }
                }
                if ($prefix == 'shipping_') {
                    $state = tep_db_prepare_input($_POST['delivery_state']);
                } else {
                    $state = tep_db_prepare_input($_POST[$prefix . 'state']);
                }
                $zone_name = '';

                if ($entry_state_has_zones == true) {
                    $zone_query = tep_db_query("select distinct zone_id, zone_name, zone_index from " . TABLE_ZONES . " where zone_country_id = '" . (int)$country . "' and (zone_name = '" . tep_db_input($state) . "' or zone_id = '" . tep_db_input($zone_id) . "')");
                    if (tep_db_num_rows($zone_query) == 1) {
                        $zone = tep_db_fetch_array($zone_query);
                        $zone_id = $zone['zone_id'];
                        $zone_name = $zone['zone_name'];
                        $zone_index = $zone['zone_index'];
                    }
                }
            } else {
				// Получение zone_id
				$zone_id = $onepage['info']['shipping_method']['zone_id'] ?? '';

				// Получение zone_index
				if (empty($_POST['address_id']) && isset($onepage['info']['shipping_method']['id'])) {
					$zone_index = explode('_', $onepage['info']['shipping_method']['id'])[0];
				} else {
					$zone_index = $_SESSION['onepage']['delivery']['shipping_module'] ?? '';
				}

				// Проверка условий и установка zone_name
				if (!empty($zone_index) && $zone_index === 'novapochta' && !empty($zone_id) &&
					array_key_exists('departments_id', $onepage['delivery']) && !empty($onepage['delivery']['departments_id'])) {
					$zone_name = $zone_id . ':' . $onepage['delivery']['departments_id'];
				}
            }

            $QcInfo = tep_db_query('select * from ' . TABLE_COUNTRIES . ' where countries_id = "' . (int)$country . '" and language_id = "' . (int)$languages_id . '"');
            $cInfo = tep_db_fetch_array($QcInfo);

            if ($action == 'setBillTo') {
                $varName = 'billing';
                if (ACCOUNT_DOB == 'true' && tep_not_null($_POST[$prefix . 'dob']))
                    $dob = $_POST[$prefix . 'dob'];
            } else {
                $varName = 'delivery';
            }
            if ($action == 'setBillTo') {
                if (ACCOUNT_DOB == 'true') {
                    $dob = tep_db_prepare_input($_POST[$prefix . 'dob']);
                    $order->customer['dob'] = $dob;
                    $onepage['customer']['dob'] = $dob;
                }
                if (isset($_POST['billing_email_address']) && tep_not_null($_POST['billing_email_address'])) {
                    $order->customer['email_address'] = tep_db_prepare_input($_POST['billing_email_address']);
                    $onepage['customer']['email_address'] = $order->customer['email_address'];
                }

                if (isset($_POST['billing_telephone']) && tep_not_null($_POST['billing_telephone'])) {
                    $order->customer['telephone'] = tep_db_prepare_input($_POST['billing_telephone']);
                    $onepage['customer']['telephone'] = $order->customer['telephone'];
                }

                if (isset($_POST['billing_additional_telephone']) && tep_not_null($_POST['billing_additional_telephone'])) {
                    $onepage['customer']['additional_phone'] = tep_db_prepare_input($_POST['billing_additional_telephone']);
                }

                if (isset($_POST['password']) && tep_not_null($_POST['password'])) {
                    $onepage['customer']['password'] = tep_encrypt_password($_POST['password']);
                }
            }

            $order->{$varName}['firstname'] = $_SESSION['onepage']['delivery']['firstname'] ?: ($_POST[$prefix . 'firstname'] ?? null);

            if (isset($_SESSION['onepage']['delivery']['middlename']) && tep_not_null($_SESSION['onepage']['delivery']['middlename'])) {
                $firstname_temp = explode(' ', $order->{$varName}['firstname']);
                if (is_array($firstname_temp) && count($firstname_temp) == 1) {
                    $order->{$varName}['firstname'] = trim($order->{$varName}['firstname']) . ' ' . trim($_SESSION['onepage']['delivery']['middlename']);
                }
            }

            $order->{$varName}['lastname'] = $_SESSION['onepage']['delivery']['lastname'] ?: $_POST[$prefix . 'lastname'] ?? null;
            $order->{$varName}['company'] = $_SESSION['onepage']['delivery']['company'] ?? $_POST[$prefix . 'company'] ?? null;
            $order->{$varName}['street_address'] = $_SESSION['onepage']['delivery']['street_address'] ?? ($_POST[$prefix . 'street_address'] ?? null);
            $order->{$varName}['district'] = $_SESSION['onepage']['delivery']['district'] ?? $_POST[$prefix . 'district'] ?? null;
            $order->{$varName}['house'] = $_SESSION['onepage']['delivery']['house'] ?? $_POST[$prefix . 'house'] ?? null;
            $order->{$varName}['flat'] = $_SESSION['onepage']['delivery']['flat'] ?? $_POST[$prefix . 'flat'] ?? null;
            $order->{$varName}['floor'] = $_SESSION['onepage']['delivery']['floor'] ?? $_POST[$prefix . 'floor'] ?? null;
            $order->{$varName}['entrance'] = $_SESSION['onepage']['delivery']['entrance'] ?? $_POST[$prefix . 'entrance'] ?? null;
            $order->{$varName}['suburb'] = $suburb ?? null;
            $order->{$varName}['city'] = $_SESSION['onepage']['delivery']['city'] ?? $_POST[$prefix . 'city'] ?? null;
            $order->{$varName}['postcode'] = null;
            $order->{$varName}['state'] = ((isset($zone_name) && tep_not_null($zone_name)) ? $zone_name : $state ?? null);
            $order->{$varName}['zone_id'] = $zone_id;
            $order->{$varName}['country'] = [
                'id'         => $cInfo['countries_id'],
                'title'      => $cInfo['countries_name'],
                'iso_code_2' => $cInfo['countries_iso_code_2'],
                'iso_code_3' => $cInfo['countries_iso_code_3'],
            ];
            $order->{$varName}['country_id'] = $cInfo['countries_id'];
            $order->{$varName}['format_id'] = $cInfo['address_format_id'];
            $order->{$varName}['shipping_module'] = $zone_index;

            if ($action == 'setSendTo' && !isset($_POST['shipping_firstname'])) {
				$onepage['customer'] = array_merge(
					is_array($onepage['customer'] ?? null) ? $onepage['customer'] : [],
					is_array($order->billing ?? null) ? $order->billing : [],
				);
            }

			$onepage['customer'] = array_merge(
				is_array($onepage['customer'] ?? null) ? $onepage['customer'] : [],
				['customer_id' => $customer_id],
			);

			$onepage[$varName] = array_merge(
				is_array($onepage[$varName] ?? null) ? $onepage[$varName] : [],
				is_array($order->{$varName} ?? null) ? $order->{$varName} : [],
			);
            
            //error_log("[onepage setCheckoutAddress] info: " . var_export($onepage['info'] ?? null, true));

            $_SESSION['onepage'] = $onepage;

			$response['success'] = 'true';

			if (in_array($_SERVER['REMOTE_ADDR'], $admins_ip)) {
				$response['debug']['onepage']['delivery'] = $onepage['delivery'];
				$response['debug']['session']['onepage'] = $_SESSION['onepage'];
				$response['debug']['session']['sendto'] = $_SESSION['sendto'];
			}

            return json_encode($response);
        }

        function setDepartments() {
            global $order, $onepage, $shipping_modules, $customer_id;

            $onepage = $_SESSION['onepage'];

            if (!empty($_POST['departments_id'])) {

				[$module, $method] = explode('_', $_SESSION['shipping']['id']);
				global $$module;
				if (is_object($$module)) {
					$quote = $shipping_modules->quote($method, $module);
				}

                $order->delivery['departments_id'] = $_POST['departments_id'];
                
                $addresses = isset($_POST['warehouse_type']) && $_POST['warehouse_type'] == 'poshtomate' ? $quote[0]['field'][0]['poshtomats'][0]['value'] : $quote[0]['field'][0]['value'];
                $key = array_search($_POST['departments_id'], array_column($addresses ?? [], 'id'));
                
                $order->delivery['street_address'] = !empty($key) ? $addresses[$key]['text'] : '';
				$order->delivery['poshtomate_delivery'] = isset($_POST['warehouse_type']) && $_POST['warehouse_type'] == 'poshtomate' ? 1 : 0;
			}

            $onepage['delivery'] = array_merge($onepage['delivery'], $order->{'delivery'});

            $_SESSION['onepage'] = $onepage;

            return '{"success": "true"}';
        }

		function renderAddress() {
			global $order, $onepage, $customer_id, $language, $languages_id;

			$content = '';

			$address_id = (int)($_POST['address_id'] ?? 0);

			if ($address_id < 1) {
				error_log("[renderAddress] Неверный или отсутствует address_id в POST: " . var_export($_POST ?? null, true));
				return $content;
			}

			include(DIR_WS_INCLUDES . 'checkout/customer_addresses.php');

			if (empty($addresses)) {
				error_log("[renderAddress] Массив addresses пуст. " . var_export($_POST ?? null, true));
				return $content;
			}

			$default_address = [];
			foreach ($addresses as $addr) {
				if ((int)$addr['address_book_id'] == $address_id) {
					$default_address = $addr;
					break;
				}
			}

			if (empty($default_address)) {
				error_log("[renderAddress] Адрес с ID $address_id не найден в массиве addresses. " . var_export($_POST ?? null, true));
				return $content;
			}

			$address = $default_address;

			// Защита от недоинициализированных глобальных массивов
			$delivery_methods = $delivery_methods ?? [];
			$shipping_methods = $shipping_methods ?? [];
			$shipping_default = $shipping_default ?? [];

			$full_address = $address['street_address']
				. ($address['house'] ? 		' ' . TEXT_HOUSE . ' ' . $address['house'] 			: '')
				. ($address['flat'] ? 		' ' . TEXT_FLAT . ' ' . $address['flat'] 			: '')
				. ($address['floor'] ? 		' ' . TEXT_FLOOR . ' ' . $address['floor'] 			: '')
				. ($address['entrance'] ? 	' ' . TEXT_ENTRANCE . ' ' . $address['entrance'] 	: '');

			$_SESSION['address_delivery'] = $address['address_delivery'] ?? '';
			$_SESSION['poshtomate_delivery'] = $address['poshtomate'] ?? '';
            $_SESSION['onepage']['info']['address_id'] = $address['address_book_id'];

			ob_start(); ?>
			<input id="address<?= $address['address_book_id'] ?>"
				   data-address_delivery="<?= htmlspecialchars($address['address_delivery']) ?>"
				   data-poshtomate_delivery="<?= htmlspecialchars($address['poshtomate']) ?>"
				   value="<?= htmlspecialchars($address['shipping_method']) . '_' . htmlspecialchars($address['shipping_method']) ?>"
				   name="address_item"
				   data-id="<?= $address['address_book_id'] ?>"
				   data-shipping_method="<?= htmlspecialchars($address['shipping_method']) ?>"
				   data-country_id="<?= (int)$address['country_id'] ?>"
				   type="radio"
				   checked
			>
			<label for="address<?= $address['address_book_id'] ?>" class="c_v_item_label">
				<span class="checkmark"></span>
				<span
					class="c_v_item_label_title"><?= $address['countries_name'] . ($address['default_address'] ? ' (' . TEXT_DEFAULT_ADDRESS . ')' : '') ?></span>
				<div class="c_v_item_content">
					<ul>
						<?php if ($address['district']): ?>
							<li class="address_book_district">
								<p><?= $address['district'] ?></p>
							</li>
						<?php endif; ?>
						<?php if (in_array($address['shipping_method'], $delivery_methods)): ?>
							<li class="address_book_shipping_service"
								data-shipping="<?= $address['shipping_method'] ?>">
								<p><?= $address['address_delivery'] ? TEXT_ADDRESS_DELIVERY : ($address['poshtomate'] == 0 ? $shipping_default[$address['shipping_method']] : SHIPPING_METHOD_POSHTOMATE) ?></p>
							</li>
						<?php endif; ?>
						<?php if ($address['shipping_method']): ?>
							<li class="address_book_shipping" data-shipping="<?= $address['shipping_method'] ?>">
								<p><?= $shipping_methods[$address['shipping_method']] ?></p>
							</li>
						<?php endif; ?>
						<li class="address_book_address">
							<?php
								if ($address['address_is_normal']) {
									echo '<p>' . $full_address . '</p>';
								} else {
									echo '<p class="not_setted">' . TEXT_SHIPPING_METHOD_NOT_SETTED . '</p>';
								}
							?>
						</li>
						<li class="address_book_name">
							<p><?= $address['firstname'] ?> <?= $address['lastname'] ?></p>
						</li>
						<li class="address_book_phone">
							<p><?= formatKievPhone($address['phone']) ?></p>
						</li>
						<?php if (!empty($address['add_phone'])) { ?>
							<li class="address_book_phone">
								<p><?= formatKievPhone($address['add_phone']) ?></p>
							</li>
						<?php } ?>
					</ul>
				</div>
			</label>

			<?php if (!$address['shipping_method'] || $address['zone_id'] == '') { ?>
				<label for="complete_address" class="c_v_item_label">
					<span class="c_v_item_label_title"><?= TEXT_ADD_ADDRESS_INFORMATION ?></span>
					<?php include(DIR_WS_MODULES . 'shipping_method_details.php'); ?>
				</label>
			<?php }

			$content = ob_get_contents();
			ob_end_clean();

			return $content;
		}

        function setLocalAddress() {
            global $order, $onepage, $customer_id;
            $onepage = $_SESSION['onepage'];
            if (!empty($_POST['billing_street'])) {
                $order->delivery['street_address'] = $_POST['billing_street'];
                $order->delivery['district'] = $_POST['district'] ?? null;
                $order->delivery['house'] = $_POST['billing_house'] ?? null;
                $order->delivery['flat'] = $_POST['billing_flat'] ?? null;
                $order->delivery['floor'] = $_POST['billing_floor'] ?? null;
                $order->delivery['entrance'] = $_POST['billing_entrances'] ?? null;
                $order->delivery['address_delivery'] = $_POST['address_delivery'] == 'true' ? 1 : 0;
                $order->info['comments'] = $_POST['comment'];
            }
            $onepage['delivery'] = array_merge($onepage['delivery'], $order->{'delivery'});
            $onepage['info']['shipping_date'] = $order->info['shipping_date'] ?? null;
            $onepage['info']['shipping_time'] = $order->info['shipping_time'] ?? null;
            $onepage['info']['comments'] = $order->info['comments'];

            if (!empty($_POST['billing_middlename'])) {
                $onepage['customer']['middlename'] = tep_db_input($_POST['billing_middlename']);
                $onepage['delivery']['middlename'] = tep_db_input($_POST['billing_middlename']);
                $onepage['billing']['middlename'] = tep_db_input($_POST['billing_middlename']);
            }
            $_SESSION['onepage'] = $onepage;

            return '{"success": "true"}';
        }

        function saveAddAddressInfo() {
            global $order;

            $order->delivery['country']['id'] = $order->delivery['country_id'];

            $NP = new NP;

            $shipping_modules = new shipping;

            $shipping_module_code = tep_db_prepare_input($_POST['shipping_add_info'] ? explode('_', $_POST['shipping_add_info'])[0] : '');

            $quotes = $GLOBALS[$shipping_module_code]->quote();
            $entry_address_delivery = 0;
            if (!empty($quotes['field'][0]['value']) && (tep_not_null($_POST['departments']) || tep_not_null($_POST['shop_item']))) {
                $id_key = array_search($_POST['departments'], array_column($quotes['field'][0]['value'], 'id'));
                $street_address = $_POST['departments'] ? $quotes['field'][0]['value'][$id_key]['text'] : ($_POST['shop_item'] ? $quotes['field'][0]['value'][$_POST['shop_item'] - 1]['text'] : '');
            } elseif ($_POST['billing_street']) {
                $street_address = tep_db_prepare_input($_POST['billing_street']);
            } else {
                $street_address = '';
            }

            if ($shipping_module_code == 'selfdelivery') {
                $zone_id = $_POST['shop_item'];
            } elseif ($order->delivery['country']['id'] == ONEPAGE_DEFAULT_COUNTRY) {
                if ($_POST['district']) {
                    $zone_id = tep_db_prepare_input($_POST['district']);
                } else {
                    $zone_id = 0;
                }
            } elseif ($_POST['nova_flat'] == 'on') {
                $zone_id = 0;
                $entry_address_delivery = 1;
            } else {
                $query = tep_db_query("select zone_id from zones where zone_country_id = " . (int)$order->delivery['country']['id'] . " and zone_index = '" . $shipping_module_code . "'");
                $zone_id = tep_db_fetch_array($query)['zone_id'] . ':' . tep_db_prepare_input($_POST['departments']);
            }


            $sql_data_array = array(
                'entry_shipping_method'  => tep_db_prepare_input($shipping_module_code),
                'entry_street_address'   => $street_address,
                'entry_house'            => tep_db_prepare_input($_POST['billing_house']) ?: '',
                'entry_flat'             => tep_db_prepare_input($_POST['billing_flat']) ?: '',
                'entry_floor'            => tep_db_prepare_input($_POST['billing_floor']) ?: '',
                'entry_entrance'         => tep_db_prepare_input($_POST['billing_entrance']) ?: '',
                'entry_zone_id'          => $zone_id,
                'entry_address_delivery' => $entry_address_delivery,
            );

            echo tep_db_perform(TABLE_ADDRESS_BOOK, $sql_data_array, 'update', 'address_book_id = "' . (int)$_POST['ab_id'] . '"');
        }

        function setAddress($addressType, $addressID) {
            global $billto, $sendto, $customer_id, $onepage;
            switch ($addressType) {
                case 'billing':
                    $billto = $addressID;
                    if (!tep_session_is_registered('billto'))
                        tep_session_register('billto');
                    $sessVar = 'billing';
                    break;
                case 'shipping':
                    $sendto = $addressID;
                    if (!tep_session_is_registered('sendto'))
                        tep_session_register('sendto');
                    $sessVar = 'delivery';
                    break;
            }

            $Qaddress = tep_db_query('select ab.entry_firstname, ab.entry_lastname, ab.entry_company, ab.entry_suburb,
                                    ab.entry_street_address, ab.entry_house, ab.entry_flat, ab.entry_floor, ab.entry_entrance,
                                    ab.entry_postcode, ab.entry_city, ab.entry_zone_id, ab.entry_shipping_method, z.zone_name, ab.entry_country_id, c.countries_id, c.countries_name,
                                    c.countries_iso_code_2, c.countries_iso_code_3, c.address_format_id, ab.entry_state
                                    from ' . TABLE_ADDRESS_BOOK . ' ab
                                    left join ' . TABLE_ZONES . ' z on (ab.entry_zone_id = z.zone_id)
                                    left join ' . TABLE_COUNTRIES . ' c on (ab.entry_country_id = c.countries_id)
                                    where ab.customers_id = "' . (int)$customer_id . '" and ab.address_book_id = "' . (int)$addressID . '"');
            $address = tep_db_fetch_array($Qaddress);

			$onepage[$sessVar] = array_merge(
                ($onepage[$sessVar] ?? []),
				[
					'firstname'       => $address['entry_firstname'] ?? null,
					'lastname'        => $address['entry_lastname'] ?? null,
					'company'         => $address['entry_company'] ?? null,
					'street_address'  => $address['entry_street_address'] ?? null,
					'district'        => $address['entry_zone_id'] ?? null,
					'house'           => $address['entry_house'] ?? null,
					'flat'            => $address['entry_flat'] ?? null,
					'floor'           => $address['entry_floor'] ?? null,
					'entrance'        => $address['entry_entrance'] ?? null,
					'suburb'          => $address['entry_suburb'] ?? null,
					'city'            => $address['entry_city'] ?? null,
					'postcode'        => $address['entry_postcode'] ?? null,
					'state'           => $address['entry_state'] ?? null,
					'zone_id'         => $address['entry_zone_id'] ?? null,
					'country'         =>
						[
							'id'         => $address['countries_id'] ?? null,
							'title'      => $address['countries_name'] ?? null,
							'iso_code_2' => $address['countries_iso_code_2'] ?? null,
							'iso_code_3' => $address['countries_iso_code_3'] ?? null,
						],
					'country_id'      => $address['entry_country_id'] ?? null,
					'format_id'       => $address['address_format_id'] ?? null,
					'shipping_module' => $address['entry_shipping_method'] ?? null,
				],
			);

            if (ACCOUNT_STATE == 'true') {
                $this->fixZoneName($onepage[$sessVar]['zone_id'], $onepage[$sessVar]['country']['id'], $onepage[$sessVar]['state']);
            }

            return '{"success": "true"}';
        }

        function saveNewAddress($action) {

            global $customer_id, $onepage, $language, $shipping_modules, $payment_modules, $order_total_modules, $languages_id;

            if (empty($_POST['shipping_method']) || $_POST['shipping_method'] == '') {
                $shipping_method = explode('_', $onepage['info']['shipping_method']['id'])[0];
            } else {
                $shipping_method = explode('_', $_POST['shipping_method'])[0];
            }
            if ($customer_id) {
                if (empty($_POST['firstname']) && empty($_POST['lastname'])) {
                    $_POST['firstname'] = $_SESSION['customer_first_name'];
                    $_POST['lastname'] = $_SESSION['customer_last_name'];
                }

                if (tep_not_null($_POST['middlename'])) {
                    $_POST['firstname'] = $_POST['firstname'] . ' ' . $_POST['middlename'];
                }

                if (empty($_POST['phone'])) {
                    $_POST['phone'] = $onepage['customer']['telephone'];
                    if (empty($_POST['fax']))
                        $_POST['fax'] = $onepage['customer']['additional_phone'];
                }
            }

			$sql_data_array = [
                'customers_id'           => (int)$customer_id,
                'entry_firstname'        => tep_db_prepare_input($_POST['firstname']),
                'entry_lastname'         => tep_db_prepare_input($_POST['lastname']),
                'entry_street_address'   => tep_db_prepare_input($_POST['street']),
                'entry_phone'            => tep_db_prepare_input(format_phone($_POST['phone'])),
                'entry_fax'              => (tep_not_null($_POST['fax']) ? tep_db_prepare_input(format_phone($_POST['fax'])) : 'null'),
                'entry_country_id'       => (int)$_POST['country'],
                'entry_house'            => (tep_not_null($_POST['house']) ? tep_db_prepare_input($_POST['house']) : 'null'),
                'entry_flat'             => (tep_not_null($_POST['flat']) ? tep_db_prepare_input($_POST['flat']) : 'null'),
                'entry_floor'            => (tep_not_null($_POST['floor']) ? tep_db_prepare_input($_POST['floor']) : 'null'),
                'entry_entrance'         => (tep_not_null($_POST['entrance']) ? tep_db_prepare_input($_POST['entrance']) : 'null'),
                'entry_shipping_method'  => tep_db_prepare_input($shipping_method),
                'entry_address_delivery' => (int)$_POST['address_delivery'],
                'entry_poshtomate' 		 => isset($_POST['poshtomate']) ? (int)$_POST['poshtomate'] : 0,
                'entry_zone_id'          => tep_db_prepare_input($_POST['zone']),
                'created_at'             => 'now()',
                'source'                 => 'checkout.php - saveNewAddress()',
            ];

			$address_book_records = tep_db_query("select address_book_id from address_book where customers_id = '" . (int)$customer_id . "' limit 1");

			tep_db_perform(TABLE_ADDRESS_BOOK, $sql_data_array);
            $address_id = tep_db_insert_id();

			if (!$address_book_records->num_rows) {
                tep_db_query("update customers set customers_default_address_id = '" . (int)$address_id . "' WHERE customers_id = '" . (int)$customer_id . "'");
            }

			// fix for update customers_lastname, customers_telephone, customers_additional_telephone
			$checkPhoneFilledQuery = tep_db_query("select customers_lastname, customers_telephone, customers_additional_telephone from customers where customers_id = '" . (int)$customer_id . "' limit 1");
			$checkPhoneFilled = tep_db_fetch_array($checkPhoneFilledQuery);
			if (!tep_not_null($checkPhoneFilled['customers_lastname']) && tep_not_null($_POST['lastname'])) {
				tep_db_query("update customers set customers_lastname = '" . tep_db_prepare_input($_POST['lastname']) . "' WHERE customers_id = '" . (int)$customer_id . "'");
			}
			if (!tep_not_null($checkPhoneFilled['customers_telephone']) && tep_not_null($_POST['phone'])) {
				tep_db_query("update customers set customers_telephone = '" . tep_db_prepare_input(format_phone($_POST['phone'])) . "' WHERE customers_id = '" . (int)$customer_id . "'");
			}
			if (!tep_not_null($checkPhoneFilled['customers_additional_telephone']) && tep_not_null($_POST['fax'])) {
				tep_db_query("update customers set customers_additional_telephone = '" . tep_db_prepare_input(format_phone($_POST['fax'])) . "' WHERE customers_id = '" . (int)$customer_id . "'");
			}

            $suburban = checkSuburban(tep_db_prepare_input($_POST['street']));
            $ob_start_check = true;

            require(DIR_WS_CONTENT . 'checkout.tpl.php');
            echo json_encode(['html' => $content, 'id' => $address_id, 'suburban' => $suburban]);
        }

        function saveAddress($action) {
            global $customer_id;
            if (ACCOUNT_COMPANY == 'true')
                $company = tep_db_prepare_input($_POST['company']);
            $firstname = tep_db_prepare_input($_POST['firstname']);
            $lastname = tep_db_prepare_input($_POST['lastname']);
            $street_address = tep_db_prepare_input($_POST['street_address']);
            if (ACCOUNT_SUBURB == 'true')
                $suburb = tep_db_prepare_input($_POST['suburb']);
            $postcode = tep_db_prepare_input($_POST['postcode']);
            $city = tep_db_prepare_input($_POST['city']);
            $country = tep_db_prepare_input($_POST['country']);
            if (ACCOUNT_STATE == 'true') {
                if (isset($_POST['zone_id'])) {
                    $zone_id = tep_db_prepare_input($_POST['zone_id']);
                } elseif (!empty($_SESSION['onepage']['info']['shipping_method']['zone_id'])) {
                    $zone_id = $_SESSION['onepage']['info']['shipping_method']['zone_id'];
                } else {
                    $zone_id = false;
                }
                $state = tep_db_prepare_input($_POST['state']);

                //$zone_id = 0;
                $check_query = tep_db_query("select count(*) as total from " . TABLE_ZONES . " where zone_country_id = '" . (int)$country . "'");
                $check = tep_db_fetch_array($check_query);
                $entry_state_has_zones = ($check['total'] > 0);
                if ($entry_state_has_zones == true) {
                    $zone_query = tep_db_query("select distinct zone_id from " . TABLE_ZONES . " where zone_country_id = '" . (int)$country . "' and (zone_name = '" . tep_db_input($state) . "' or zone_code = '" . tep_db_input($state) . "')");
                    if (tep_db_num_rows($zone_query) == 1) {
                        $zone = tep_db_fetch_array($zone_query);
                        $zone_id = $zone['zone_id'];
                    }
                }
            }

            $sql_data_array = array(
                'customers_id'         => $customer_id,
                'entry_firstname'      => $firstname,
                'entry_lastname'       => $lastname,
                'entry_street_address' => $street_address,
                'entry_postcode'       => $postcode,
                'entry_city'           => $city,
                'entry_country_id'     => $country,
				'source'               => 'checkout.php - saveAddress()',
            );

            if (ACCOUNT_COMPANY == 'true')
                $sql_data_array['entry_company'] = $company;
            if (ACCOUNT_SUBURB == 'true')
                $sql_data_array['entry_suburb'] = $suburb;
            if (ACCOUNT_STATE == 'true') {
                if ($zone_id > 0) {
                    $sql_data_array['entry_zone_id'] = $zone_id;
                    $sql_data_array['entry_state'] = '';
                } else {
                    $sql_data_array['entry_zone_id'] = '0';
                    $sql_data_array['entry_state'] = $state;
                }
            }

            if ($action == 'saveAddress') {
                $Qcheck = tep_db_query('select address_book_id from ' . TABLE_ADDRESS_BOOK . ' where address_book_id = "' . (int)$_POST['address_id'] . '" and customers_id = "' . (int)$customer_id . '"');
                if (tep_db_num_rows($Qcheck)) {
					$sql_data_array['updated_at'] = 'now()';
                    tep_db_perform(TABLE_ADDRESS_BOOK, $sql_data_array, 'update', 'address_book_id = "' . (int)$_POST['address_id'] . '"');
                }

                return '{"success": "true"}';
            } else {
				$sql_data_array['created_at'] = 'now()';
                tep_db_perform(TABLE_ADDRESS_BOOK, $sql_data_array);
                include(DIR_WS_INCLUDES . 'checkout/customer_addresses.php');
            }

        }

        function processCheckout() {
            global $customer_id,
				   $comments,
				   $schedule_value,
				   $shipping_date,
				   $shipping_time,
				   $dont_call_before_shipping,
				   $call_before_shipping,
				   $failed_to_contact,
				   $send_sms,
				   $send_email,
				   $paid_delivery,
				   $give_card,
				   $mid,
				   $concierge,
				   $is_lift,
				   $coupon,
				   $order,
				   $currencies,
				   $request_type,
				   $languages_id,
				   $currency,
				   $customer_shopping_points_spending,
				   $customer_referral,
				   $shipping,
				   $cartID,
				   $order_total_modules,
				   $onepage,
				   $credit_covers,
				   $payment,
				   $payment_modules,
				   $cart,
				   $wishList,
				   $lng,
				   $paymentMethod,
				   $order_totals,
				   $products_ordered,
				   $guest_account,
				   $cat_tree;

            if (isset($_POST['schedule_value']) && tep_not_null($_POST['schedule_value'])) {
                $schedule_value = tep_db_prepare_input($_POST['schedule_value']);
                $schedule_data_raw = explode('_', $schedule_value);
                if (!empty($schedule_data_raw)) {
                    $schedule_data = explode('-', $schedule_data_raw[0]);
                    if (!empty($schedule_data)) {
                        $schedule_data[1] = date('Y-m-d', strtotime($schedule_data[1]));
                        $shipping_date = tep_db_prepare_input($schedule_data[1]);
                        $shipping_time = tep_db_prepare_input($schedule_data[0]);
                    }
                }
            } else {
                $shipping_date = '';
                $shipping_time = '';
            }

            $dont_call_before_shipping = isset($_POST['dont_call_before_shipping']) && $_POST['dont_call_before_shipping'] == 'on' ? 1 : 0;
            $call_before_shipping = isset($_POST['call_before_shipping']) && $_POST['call_before_shipping'] == 'on' ? 1 : 0;
            $failed_to_contact = isset($_POST['failed_to_contact']) && $_POST['failed_to_contact'] == 'on' ? 1 : 0;
            $send_sms = isset($_POST['send_sms']) && $_POST['send_sms'] == 'on' ? 1 : 0;
            $send_email = isset($_POST['send_email']) && $_POST['send_email'] == 'on' ? 1 : 0;
            $paid_delivery = isset($_POST['paid_delivery']) && $_POST['paid_delivery'] == 'on' ? 1 : 0;
            $give_card = isset($_POST['give_card']) && $_POST['give_card'] == 'on' ? 1 : 0;
            $concierge = isset($_POST['concierge']) && $_POST['concierge'] == 'on' ? 1 : 0;
            $is_lift = isset($_POST['is_lift']) && $_POST['is_lift'] == 'on' ? 1 : 0;
			$mid = isset($_POST['mid']) ? tep_db_prepare_input($_POST['mid']) : null;
			$comments = tep_db_prepare_input($_POST['comments']);
            if (!tep_session_is_registered('comments')) {
				tep_session_register('comments');
			}
            $onepage['customer']['comments'] = $comments;

            $order->info['comments'] = $comments;
            $onepage['info']['comments'] = $comments;

            $order->info['schedule_value'] = $schedule_value;
            $onepage['info']['schedule_value'] = $schedule_value;

            $order->info['shipping_date'] = $shipping_date;
            $onepage['info']['shipping_date'] = $shipping_date;

            $order->info['shipping_time'] = $shipping_time;
            $onepage['info']['shipping_time'] = $shipping_time;

            $order->info['send_sms'] = $send_sms;
            $onepage['info']['send_sms'] = $send_sms;

            $order->info['send_email'] = $send_email;
            $onepage['info']['send_email'] = $send_email;

            $order->info['paid_delivery'] = $paid_delivery;
            $onepage['info']['paid_delivery'] = $paid_delivery;

            $order->info['give_card'] = $give_card;
            $onepage['info']['give_card'] = $give_card;

            $order->info['mid'] = $mid;
            $onepage['info']['mid'] = $mid;

            $order->info['dont_call_before_shipping'] = $dont_call_before_shipping;
            $onepage['info']['dont_call_before_shipping'] = $dont_call_before_shipping;

            $order->info['call_before_shipping'] = $call_before_shipping;
            $onepage['info']['call_before_shipping'] = $call_before_shipping;

            $order->info['failed_to_contact'] = $failed_to_contact;
            $onepage['info']['failed_to_contact'] = $failed_to_contact;

            $order->info['concierge'] = $concierge;
            $onepage['info']['concierge'] = $concierge;

            $order->info['is_lift'] = $is_lift;
            $onepage['info']['is_lift'] = $is_lift;

            $_SESSION['onepage'] = $onepage;

            if (MODULE_ORDER_TOTAL_DISCOUNT_COUPON_STATUS == 'true') {
                $onepage['info']['coupon'] = $order->coupons ?? null;
            }

            if (isset($_POST['diffShipping']) && $_POST['diffShipping']) {
                $onepage['info']['diffShipping'] = true;
                $order->info['diffShipping'] = true;
            }

            $onepage['customer']['newsletter'] = (isset($_POST['billing_newsletter']) ? $_POST['billing_newsletter'] : '0');

            $this->setCheckoutAddress('setSendTo');
            $this->setCheckoutAddress('setBillTo');

            $order->customer = array_merge($order->customer, $onepage['customer']);
            $order->delivery = array_merge($order->delivery, $onepage['delivery']);
            $order->billing = array_merge($order->billing, $onepage['billing']);

            $admins_ip = explode(';', ADMIN_IP_ADDRESS);
            
            if (tep_session_is_registered('customer_id') || (isset($_POST['new_customer_discount']) && $_POST['new_customer_discount'] !== 'on' && !tep_session_is_registered('customer_id'))) {
                $onepage['createAccount'] = false;
                $this->createCustomerAccount();
            } else {
                if (in_array($_SERVER['REMOTE_ADDR'], $admins_ip) && strstr($onepage['customer']['email_address'], '@zootovary.ua')) {
                    $onepage['createAccount'] = true;
                    $onepage['customer']['password'] = 'ztr123rv';
                    $this->createCustomerAccount();
                } else {
                    if (!empty($_POST['password'])) {
                        $onepage['createAccount'] = true;
                        $onepage['customer']['password'] = $_POST['password'];
                        $this->createCustomerAccount();
                    } elseif (ONEPAGE_ACCOUNT_CREATE == 'create' || (ONEPAGE_ACCOUNT_CREATE == 'create' && isset($_POST['new_customer_discount']) && $_POST['new_customer_discount'] == 'on')) {
                        $onepage['createAccount'] = true;
                        $onepage['customer']['password'] = mb_strtoupper(tep_create_random_value(ENTRY_PASSWORD_MIN_LENGTH));
                        $this->createCustomerAccount();
                    }
                }
            }

            $payment_modules->update_status();
            $paymentMethod = $onepage['info']['payment_method'];

            if (defined('MODULE_ORDER_TOTAL_COUPON_STATUS') && MODULE_ORDER_TOTAL_COUPON_STATUS == 'true') {
                // Start - CREDIT CLASS Gift Voucher Contribution
                if ($credit_covers)
                    $paymentMethod = 'credit_covers';
                unset($_POST['gv_redeem_code']);
                $order_total_modules->collect_posts();
                $order_total_modules->pre_confirmation_check();
                // End - CREDIT CLASS Gift Voucher Contribution
            }
            if (($order->info['total']) <= 0) {
                $payment = '';
                $paymentMethod = '';
                $onepage['info']['payment_method'] = '';
            }

            $checkout_mode = 'in_checkout_proccess';
            if ($paymentMethod != '') {
                if (isset($GLOBALS[$paymentMethod]->checkout_mode) && tep_not_null($GLOBALS[$paymentMethod]->checkout_mode)) {
                    $checkout_mode = $GLOBALS[$paymentMethod]->checkout_mode;
                }
                $GLOBALS[$paymentMethod]->before_process();

                if ($checkout_mode == 'in_checkout_proccess') {
                    $GLOBALS[$paymentMethod]->pre_confirmation_check();
                    $GLOBALS[$paymentMethod]->confirmation();
                    $hiddenFields = $GLOBALS[$paymentMethod]->process_button();
                    $formUrl = $GLOBALS[$paymentMethod]->form_action_url ?? null;
                    $redirectUrl = $GLOBALS[$paymentMethod]->redirect_to_uri ?? null;
                }
            }

			if (isset($redirectUrl) && tep_not_null($redirectUrl) && $checkout_mode == 'in_checkout_proccess') {
				if (!empty($order->info['real_id'])) {
					tep_db_perform('orders', ['payment_uri' => $redirectUrl], 'update', 'orders_id = ' . $order->info['real_id']);
				}
				tep_redirect($redirectUrl);
				exit;
			} elseif (isset($hiddenFields) && isset($GLOBALS[$paymentMethod]->form_action_url) && tep_not_null($GLOBALS[$paymentMethod]->form_action_url) && $checkout_mode == 'in_checkout_proccess') {
				$html = '<html>';
				$html .= '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>';
				$html .= '<body><style>input {display: none}</style>';
				$html .= '<form id="redirectForm" action="' . $formUrl . '" method="post">' . $hiddenFields . '</form>';
				$html .= '<script>document.getElementById("redirectForm").submit();</script>';
				$html .= '<div style="display: flex;flex-direction: column;align-items: center;justify-content: center;height: 100%;">';
				$html .= '<div><img src="' . DIR_WS_HTTP_CATALOG . DIR_WS_IMAGES . 'ajax-loader.gif"></div>';
				$html .= '<div>' . TEXT_ORDER_PROCESSING . '</div>';
				$html .= '</div>';
				$html .= '</body>';
				$html .= '</html>';

				return $html;
			} else {
				include('checkout_process.php');
			}
        }

        function createCustomerAccount() {
            global $customer_id, $onepage, $order, $customer_default_address_id, $customer_first_name, $customers_email_address, $customer_country_id, $customer_zone_id, $sendto, $billto, $cat_tree, $cat_names;

            if ($onepage['createAccount'] === true && $this->checkEmailAddress($onepage['customer']['email_address'])) {
                $sql_data_array = array(
                    'customers_firstname'          => $onepage['billing']['firstname'],
                    'customers_lastname'           => $onepage['billing']['lastname'],
                    'customers_email_address'      => $onepage['customer']['email_address'],
                    'customers_telephone'          => $onepage['customer']['telephone'],
                    'customers_fax'                => $onepage['customer']['additional_phone'] ?? 'null',
                    'customers_newsletter'         => 1,
                    'customers_ip'                 => $_SERVER['REMOTE_ADDR'],
                    'customers_user_agent'         => 'null',
                    'customers_password'           => tep_encrypt_password($onepage['customer']['password']),
                );

                if (ALLOW_USE_VISA_MASTERCARD_PAYMENT_FOR_NEW_REGISTERING_CUSTOMERS == 'true') {
                    $sql_data_array['customers_allow_card_payment'] = 1;
                }

                if (ACCOUNT_DOB == 'true')
                    $sql_data_array['customers_dob'] = tep_date_raw($onepage['customer']['dob']);

                tep_db_perform(TABLE_CUSTOMERS, $sql_data_array);
                $customer_id = tep_db_insert_id();

				if (tep_not_null($customer_id) && $customer_id != 0) {
					if (ESPUTNIK_INTEGRATION_ENABLED == 'true' && SEND_EVENTS_TO_ESPUTNIK == 'true') {
						require(DIR_WS_CLASSES . 'esputnik.php');
						$ES = new ES();
						$ESputnicStatus = getESputnicStatus();

						if ($ESputnicStatus == 200) {
							$result = $ES->curl('contacts?email=' . $onepage['customer']['email_address'], [], false);

							if ($result['result'] == 200 && $result['response'] == '[]') {
								$eventSettings = (object) [
									'eventTypeKey' => 'Registraciya', // идентификатор типа события
									'keyValue' => $onepage['customer']['email_address'],
									'params' => [
										[
											'name' => 'email',
											'value' => $onepage['customer']['email_address'],
										],
										[
											'name' => 'json',
											'value' => json_encode([
												'firstname' => $onepage['billing']['firstname'],
												'lastname' => $onepage['billing']['lastname'],
												'email' => $onepage['customer']['email_address'],
											]),
										],
									],
								];

								// Отправка запроса о событии о регистрации
								$ES->curl('event', $eventSettings, false);
							}

							$import_contacts_url = 'https://esputnik.com/api/v1/contacts';
							$userData = [
								'name'  => $onepage['billing']['firstname'],
								'email' => $onepage['customer']['email_address'],
							];
							$eSputnikData = makeESputnikDataForCreate($userData);
							$credentials = getESputnikCredentials();
							sendESputnikRequest($import_contacts_url, $eSputnikData, $credentials);

							$eSputnikEventData = [
								"GeneralInfo" => [
									"eventName"          => "CustomerData",
									"siteId"             => ESPUTNIK_ID,
									"datetime"           => time(),
									"user_email"         => $onepage['customer']['email_address'],
									"user_name"          => $onepage['billing']['firstname'],
									"externalCustomerId" => (string)$customer_id,
									"cookies"            => [
										"sc" => $_COOKIE['sc'],
									],
								],
							];

							eSputnikEventRegister($eSputnikEventData, basename(__FILE__));
						}
					}

					// add task for export to 1c in queue "customer"
					if (class_exists('RedisQueue')) {
						try {
							$customerQueue = new RedisQueue('customer');
							$data = [
								'cid'      => $customer_id,
								'function' => 'Create',
								'source'   => 'onepage_checkout',
								'date' 	   => date('Y-m-d H:i:s'),
							];
							$customerQueue->add($data);
						} catch (RedisQueueException $e) {
							tep_mail_html('', 'debug@zootovary.ua', 'RedisQueueException!', 'RedisQueueException! ' . "\n\t" . $e, 'DEBUG ZOOTOVARY.UA', 'debug@zootovary.ua');
						}
					} else {
						tep_mail_html('', 'debug@zootovary.ua', 'RedisQueue !class_exists', 'RedisQueue !class_exists', 'DEBUG ZOOTOVARY.UA', 'debug@zootovary.ua');
					}
				}

                $_SESSION['order_account_created'] = true;

                //генерация токена для востановления email
                $token = generateToken($onepage['customer']['email_address']);
                try {
                    updateToken($customer_id, $token);
                } catch (Exception $e) {
                    $token = generateToken($onepage['customer']['email_address']);
                    updateToken($customer_id, $token);
                }

				$shipping_method = $onepage['info']['shipping_method'] ?? null;

				if (is_array($shipping_method) && isset($shipping_method['id'])) {
					$shipping_method_value = explode('_', $shipping_method['id'])[0];
				} elseif (is_string($shipping_method)) {
					$shipping_method_value = explode('_', $shipping_method)[0];
				} else {
					$shipping_method_value = null; // или обработать как ошибку
				}
                
                $sql_data_array = array(
                    'customers_id'          => $customer_id,
                    'entry_firstname'       => $onepage['billing']['firstname'],
                    'entry_lastname'        => $onepage['billing']['lastname'],
                    'entry_street_address'  => $onepage['billing']['street_address'],
                    'entry_house'           => $onepage['billing']['house'] ?? 'null',
                    'entry_flat'            => $onepage['billing']['flat'] ?? 'null',
                    'entry_floor'           => $onepage['billing']['floor'] ?? 'null',
                    'entry_entrance'        => $onepage['billing']['entrance'] ?? 'null',
                    'entry_postcode'        => 'null',
                    'entry_city'            => $onepage['billing']['city'] ?? 'null',
                    'entry_country_id'      => $onepage['billing']['country_id'],
                    'entry_phone'           => $onepage['customer']['telephone'],
                    'entry_fax'             => $onepage['customer']['additional_phone'] ?? 'null',
                    'entry_shipping_method' => $shipping_method_value,
					'created_at'            => 'now()',
					'source'                => 'checkout.php - createCustomerAccount() - createAccount true',
                );

                if (ACCOUNT_COMPANY == 'true') {
                    $sql_data_array['entry_company'] = $onepage['billing']['company'];
                }

                if (ACCOUNT_SUBURB == 'true') {
                    $sql_data_array['entry_suburb'] = $onepage['billing']['suburb'];
                }

                if (ACCOUNT_STATE == 'true') {
                    $sql_data_array['entry_zone_id'] = $onepage['billing']['zone_id'];
                } elseif (!empty($_SESSION['onepage']['info']['shipping_method']['zone_id'])) {
                    $sql_data_array['entry_zone_id'] = $onepage['info']['shipping_method']['zone_id'] . (array_key_exists('departments_id', $onepage['delivery']) && tep_not_null($onepage['delivery']['departments_id']) ? ':' . (int)$onepage['delivery']['departments_id'] : ($onepage['delivery']['shipping_module'] ? ':' . $onepage['delivery']['shipping_module'] : ''));
                }

                if ($onepage['billing']['district'] && $onepage['billing']['country_id'] != ONEPAGE_DEFAULT_COUNTRY) {
                    $sql_data_array['entry_zone_id'] = $onepage['billing']['district'];
                } else {
                    if (array_key_exists('departments_id', $onepage['delivery'])) {
						if ($shipping_method_value == 'selfdelivery') {
							$sql_data_array['entry_zone_id'] = $onepage['delivery']['departments_id'];
						} elseif (MODULE_SHIPPING_NOVAPOCHTA_ZONE == 2 && $shipping_method_value == 'novapochta') {
							$sql_data_array['entry_zone_id'] = $onepage['info']['shipping_method']['zone_id'] . (tep_not_null($onepage['delivery']['departments_id']) ? ':' . (int)$onepage['delivery']['departments_id'] : '');
						}
					} else {
                        $sql_data_array['entry_zone_id'] = 0;
                    }
                }

                if (!empty($onepage['delivery']['address_delivery'])) {
                    $sql_data_array['entry_zone_id'] = 0;
                    $sql_data_array['entry_address_delivery'] = $onepage['delivery']['address_delivery'];
                } else {
                    $sql_data_array['entry_address_delivery'] = 0;
                }

				$sql_data_array['entry_poshtomate'] = $onepage['delivery']['poshtomate_delivery'] ?? 0;
                
                /**
                 * fallback для пустого $onepage['billing']['street_address']
                */
                if (empty($sql_data_array['entry_street_address'])) {
                    if (!empty($sql_data_array['entry_zone_id'])) {
                        
                        $isNovapochta   = $sql_data_array['entry_shipping_method'] == 'novapochta';
                        $isSelfdelivery = $sql_data_array['entry_shipping_method'] == 'selfdelivery';
                        $isPoshtomate = (int)$sql_data_array['entry_poshtomate'] == 1;
                        
                        $zoneParts = $isNovapochta && str_contains($sql_data_array['entry_zone_id'], ':') ? explode(':', $sql_data_array['entry_zone_id']) : ($isSelfdelivery ? $sql_data_array['entry_zone_id'] : null);
                        
                        if ($isSelfdelivery) {
                            $sql_data_array['entry_street_address'] = $this->getShopFromZone((int)$sql_data_array['entry_zone_id']);
                        }
                        
                        if ($isNovapochta && is_array($zoneParts) && count($zoneParts) === 2) {
                            $sql_data_array['entry_street_address'] = $this->getAddressFromZone(
                                (int)$sql_data_array['entry_country_id'],
                                (int)$zoneParts[0],
                                (int)$zoneParts[1],
                                $isPoshtomate
                            );
                        }
                    }
                }

                tep_db_perform(TABLE_ADDRESS_BOOK, $sql_data_array);

                $address_id = tep_db_insert_id();
                $billto = $address_id;
                $sendto = $address_id;

                $customer_default_address_id = $address_id;
                $customer_first_name = $onepage['billing']['firstname'];
				$customers_email_address = $onepage['customer']['email_address'];
                $customer_country_id = $onepage['billing']['country_id'];
                $customer_zone_id = $onepage['billing']['zone_id'];
                $_SESSION['customers_newsletter'] = 1;
                $_SESSION['onepage']['info']['address_id'] = $address_id;
                $onepage['info']['address_id'] = $address_id;
                $order->info['address_id'] = $address_id;
                tep_db_query("update " . TABLE_CUSTOMERS . " set customers_default_address_id = '" . (int)$address_id . "' where customers_id = '" . (int)$customer_id . "'");

				$customers_info = [
					'customers_info_id' => (int)$customer_id,
					'customers_info_number_of_logons' => 0,
					'customers_info_date_account_created' => 'now()',
				];
				tep_db_perform(TABLE_CUSTOMERS_INFO, $customers_info);

                $Qcustomer = tep_db_query('select customers_firstname, customers_lastname, customers_email_address from ' . TABLE_CUSTOMERS . ' where customers_id = "' . (int)$customer_id . '"');
                $customer = tep_db_fetch_array($Qcustomer);

                // build the message content
                $name = $customer['customers_firstname'] . ' ' . $customer['customers_lastname'];

                $email_text = sprintf(EMAIL_GREET_NONE, $customer['customers_firstname']);

                $email_text .= EMAIL_WELCOME;

                $email_text .= ' <br>' .
                    TEXT_EMAIL_LOGIN . ': ' . $onepage['customer']['email_address'] . ':<br>' .
                    TEXT_EMAIL_PASS . ': ' . $onepage['customer']['password'] . '<br><br>';

                $email_text .= EMAIL_TEXT . EMAIL_CONTACT . EMAIL_WARNING;

                $onepage['createAccount'] = false;

                if ($content_email_array = get_email_contents('new_customer_from_checkout')) {
                    $store_categories = '';
                    $sci = 0;
                    if (is_array($cat_tree)) {
                        foreach (array_keys($cat_tree) as $fcat) {
                            if ($sci < 5)
                                $store_categories .= '<a style="text-decoration:underline;color:inherit" href="' . tep_href_link(FILENAME_DEFAULT, 'cPath=' . $fcat, 'NONSSL') . '">';
                                $store_categories .= '<span>' . $cat_names[$fcat] . '</span>';
								$store_categories .= '</a>';
								$store_categories .= '<span style="padding:0 5px">&bull;</span>';
                            $sci++;
                        }
                    }

                    if (!defined('TEMPLATE_NAME')) {
                        define('TEMPLATE_NAME', 'solo');
                    }

                    $shipping_info_kiev = renderArticle('shipping_info_kiev');
                    $shipping_info_ukraine = renderArticle('shipping_info_ukraine');
                    $payment_info = renderArticle('payment_info');

                    $pattern_search = ['/{STORE_URL}/', '/{TEMPLATE_PATH}/', '/{MIN_DISCOUNT}/', '/{MAX_DISCOUNT}/', '/{MODULE_SHIPPING_NOVAPOCHTA_COST}/', '/{SHIPPING_COST}/', '/{MINIMUM_ORDER_COST}/', '/{ORDER_COST_FOR_FREE_SHIPPING}/'];
                    $replacement_search = [HTTP_SERVER, HTTP_SERVER . '/' . DIR_WS_TEMPLATES . TEMPLATE_NAME . '/images/', MODULE_LOYALTY_MIN_DISCOUNT, MODULE_LOYALTY_MAX_DISCOUNT, MODULE_SHIPPING_NOVAPOCHTA_COST, SHIPPING_COST, MINIMUM_ORDER_COST, ORDER_COST_FOR_FREE_SHIPPING];

                    $shipping_info_kiev = preg_replace($pattern_search, $replacement_search, $shipping_info_kiev);
                    $shipping_info_ukraine = preg_replace($pattern_search, $replacement_search, $shipping_info_ukraine);
                    $payment_info = preg_replace($pattern_search, $replacement_search, $payment_info);

                    $array_from_template_to_template = [
                        '{STORE_URL}'            => HTTP_SERVER,
                        '{TEMPLATE_PATH}'        => HTTP_SERVER . '/' . DIR_WS_TEMPLATES . TEMPLATE_NAME . '/images/',
                        //'{STORE_LOGO}'           => HTTP_SERVER . '/' . LOGO_IMAGE,
                        '{STORE_NAME}'           => STORE_NAME,
                        '{CUSTOMER_EMAIL}'       => $onepage['customer']['email_address'],
                        '{STORE_PHONE}'          => tep_get_render_phones(),
                        '{EMAIL_HEADER_TITLE}'   => EMAIL_HEADER_TITLE_CREATE_ACCOUNT,
                        '{EMAIL_HEADER_TITLE_2}' => EMAIL_HEADER_TITLE_CREATE_ACCOUNT_2,
                        '{EMAIL_FOOTER_TITLE}'   => EMAIL_FOOTER_TITLE_CREATE_ACCOUNT,
                        '{EMAIL_FOOTER_TITLE_2}' => EMAIL_FOOTER_TITLE_CREATE_ACCOUNT_2,
                    ];

                    // array to replace variables from html template:
                    $array_from_to = array(
                        '{HEADER}'                => prepare_email_template(get_email_contents('header__template')['content_html'], $array_from_template_to_template),
                        '{FOOTER}'                => prepare_email_template(get_email_contents('footer__template')['content_html'], $array_from_template_to_template),
                        '{TEMPLATE_PATH}'         => HTTP_SERVER . '/' . DIR_WS_TEMPLATES . TEMPLATE_NAME . '/images/',
                        '{STORE_NAME}'            => STORE_NAME,
                        '{CUSTOMER_LOGIN}'        => $onepage['customer']['email_address'],
                        '{CUSTOMER_PASSWORD}'     => $onepage['customer']['password'],
                        '{CUSTOMER_EMAIL}'        => $onepage['customer']['email_address'],
                        //'{STORE_LOGO}'            => HTTP_SERVER . '/' . LOGO_IMAGE,
                        '{STORE_URL}'             => HTTP_SERVER,
                        '{STORE_PHONE}'           => tep_get_render_phones(),
                        '{SHIPPING_INFO_KIEV}'    => (!empty($shipping_info_kiev) ? $shipping_info_kiev : ''),
                        '{SHIPPING_INFO_UKRAINE}' => (!empty($shipping_info_ukraine) ? $shipping_info_ukraine : ''),
                        '{PAYMENT_INFO}'          => (!empty($payment_info) ? $payment_info : ''),
                        '{STORE_CATEGORIES}'      => $store_categories,
                    );

                    $email_text = prepare_email_template($content_email_array['content_html'], $array_from_to);
                } else {
                    $caller_info = get_caller_info();
                    tep_mail_html('', 'debug@zootovary.ua', 'Not found template!', 'Not found template! ' . "\n\t" . $caller_info, 'DEBUG ZOOTOVARY.UA', 'debug@zootovary.ua');
                }
                // send email to customer:
                if (CHECKOUT_EMAIL_DEBUG == 'true') {
                    echo $email_text;
                }

                // send email to customer:
                tep_mail($name, $customer['customers_email_address'], EMAIL_SUBJECT, $email_text, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS);

                if (!tep_session_is_registered('customer_id'))
                    tep_session_register('customer_id');
                if (!tep_session_is_registered('customer_default_address_id'))
                    tep_session_register('customer_default_address_id');
                if (!tep_session_is_registered('customers_email_address'))
                    tep_session_register('customers_email_address');
                if (!tep_session_is_registered('customer_first_name'))
                    tep_session_register('customer_first_name');
                if (!tep_session_is_registered('customer_country_id'))
                    tep_session_register('customer_country_id');
                if (!tep_session_is_registered('customer_zone_id'))
                    tep_session_register('customer_zone_id');
                if (!tep_session_is_registered('sendto'))
                    tep_session_register('sendto');
                if (!tep_session_is_registered('billto'))
                    tep_session_register('billto');

            } elseif (
                    empty($_SESSION['onepage']['info']['address_id']) &&
                !($onepage['createAccount'] === true) &&
                $this->checkEmailAddress($onepage['customer']['email_address'])
            ) {
                $onepage['createAccount'] = false;

				if (tep_not_null($customer_id) && $customer_id != 0) {
					if (!empty($onepage['info']['shipping_method']['id']) && is_string($onepage['info']['shipping_method']['id'])) {
						$shipping_method_value = explode('_', $onepage['info']['shipping_method']['id'])[0];
					} else {
						$shipping_method_value = ''; // или другое значение по умолчанию
					}

					$sql_data_array = array(
						'customers_id'          => $customer_id,
						'entry_firstname'       => $onepage['billing']['firstname'],
						'entry_lastname'        => $onepage['billing']['lastname'],
						'entry_street_address'  => $onepage['billing']['street_address'],
						'entry_house'           => $onepage['billing']['house'],
						'entry_flat'            => $onepage['billing']['flat'],
						'entry_floor'           => $onepage['billing']['floor'],
						'entry_entrance'        => $onepage['billing']['entrance'],
						'entry_postcode'        => '',//$onepage['billing']['postcode'],
						'entry_city'            => $onepage['billing']['city'],
						'entry_country_id'      => $onepage['billing']['country_id'],
						'entry_phone'           => $onepage['customer']['telephone'],
						'entry_fax'             => $onepage['customer']['additional_phone'],
						'entry_shipping_method' => $shipping_method_value,
						'created_at'            => 'now()',
						'source'                => 'checkout.php - createCustomerAccount() - createAccount false',
					);

					if (ACCOUNT_COMPANY == 'true') {
						$sql_data_array['entry_company'] = $onepage['billing']['company'];
					}

                    if (ACCOUNT_SUBURB == 'true') {
						$sql_data_array['entry_suburb'] = $onepage['billing']['suburb'];
					}

					if (ACCOUNT_STATE == 'true') {
						$sql_data_array['entry_zone_id'] = $onepage['billing']['zone_id'];
					} elseif (!empty($_SESSION['onepage']['info']['shipping_method']['zone_id'])) {
						$sql_data_array['entry_zone_id'] = $onepage['info']['shipping_method']['zone_id'] . (array_key_exists('departments_id', $onepage['delivery']) && tep_not_null($onepage['delivery']['departments_id']) ? ':' . (int)$onepage['delivery']['departments_id'] : ($onepage['delivery']['shipping_module'] ? ':' . $onepage['delivery']['shipping_module'] : ''));
					}

					if ($onepage['billing']['district'] && $onepage['billing']['country_id'] != ONEPAGE_DEFAULT_COUNTRY) {
						$sql_data_array['entry_zone_id'] = $onepage['billing']['district'];
					} else {
						if (array_key_exists('departments_id', $onepage['delivery'])) {
							if ($shipping_method_value == 'selfdelivery') {
								$sql_data_array['entry_zone_id'] = $onepage['delivery']['departments_id'] ?? $onepage['delivery']['district'];
							} elseif (MODULE_SHIPPING_NOVAPOCHTA_ZONE == 2 && $shipping_method_value == 'novapochta') {
								$sql_data_array['entry_zone_id'] = $onepage['info']['shipping_method']['zone_id'] . (tep_not_null($onepage['delivery']['departments_id']) ? ':' . (int)$onepage['delivery']['departments_id'] : '');
							}
						} else {
							$sql_data_array['entry_zone_id'] = 0;
						}
					}

					if (!empty($onepage['delivery']['address_delivery'])) {
						$sql_data_array['entry_zone_id'] = 0;
						$sql_data_array['entry_address_delivery'] = $onepage['delivery']['address_delivery'];
					} else {
						$sql_data_array['entry_address_delivery'] = 0;
					}

					$sql_data_array['entry_poshtomate'] = $onepage['delivery']['poshtomate_delivery'] ?? 0;

                    tep_db_perform(TABLE_ADDRESS_BOOK, $sql_data_array);
					$lastId = tep_db_insert_id();
					$this->updateCustomerDefaultAddress($customer_id, $lastId);
				}
            }
        }

        function getAddressFormatted($type, $short = false) {
            global $order;
            switch ($type) {
                case 'sendto':
                    $address = $order->delivery;
                    break;
                case 'billto':
                    $address = $order->billing;
                    break;
            }
            if ($address['format_id'] == '') {
                $address['format_id'] = 1;
            }

            if ($short) {
                $address['format_id'] = 4;
            }

            return tep_address_format($address['format_id'], $address, true, '', "\n");
        }

        function verifyContents() {
            global $cart, $onepage;

            if (!isset($onepage['shippingEnabled'])) {
                $onepage['shippingEnabled'] = true;
            }
            
            // if there is nothing in the customers cart, redirect them to the shopping cart page
            if ($cart->count_contents() < 1) {
                tep_redirect(tep_href_link(FILENAME_DEFAULT));
            }
        }

        function setDefaultSendTo() {
            global $sendto, $customer_id, $customer_default_address_id, $shipping;

			// if no shipping destination address was selected, use the customers own address as default
            if (!tep_session_is_registered('sendto')) {
                $sendto = $customer_default_address_id;
                tep_session_register('sendto');
            } elseif (tep_session_is_registered('sendto') && empty($sendto) && !empty($customer_default_address_id)) {
				$sendto = $customer_default_address_id;
            } else {
                if ((is_array($sendto) && !tep_not_null($sendto)) || is_numeric($sendto)) {
					$check_address_sql = "select count(*) as total
										  from " . TABLE_ADDRESS_BOOK . "
										  where customers_id = '" . (int)$customer_id . "' and
										  		address_book_id = '" . (int)$sendto . "'";

                    $check_address_query = tep_db_query($check_address_sql);
                    $check_address = tep_db_fetch_array($check_address_query);

                    if ($check_address['total'] != '1') {
                        $sendto = $customer_default_address_id;
                        if (tep_session_is_registered('shipping'))
                            tep_session_unregister('shipping');
                    }
                }
            }
            
            $this->setAddress('shipping', $sendto);
        }

        function setDefaultBillTo() {
            global $billto, $customer_id, $customer_default_address_id, $shipping;

			// if no billing destination address was selected, use the customers own address as default
            if (!tep_session_is_registered('billto')) {
                $billto = $customer_default_address_id;
                tep_session_register('billto');
            } else {
                // verify the selected billing address
                if ((is_array($billto) && !tep_not_null($billto)) || is_numeric($billto)) {
                    $check_address_query = tep_db_query("select count(*) as total from " . TABLE_ADDRESS_BOOK . " where customers_id = '" . (int)$customer_id . "' and address_book_id = '" . (int)$billto . "'");
                    $check_address = tep_db_fetch_array($check_address_query);

                    if ($check_address['total'] != '1') {
                        $billto = $customer_default_address_id;
                        if (isset($payment) && tep_session_is_registered('payment'))
                            tep_session_unregister($payment);
                    }
                }
            }
            $this->setAddress('billing', $billto);
        }

        function removeCCGV() {
            if (tep_session_is_registered('credit_covers'))
                tep_session_unregister('credit_covers');
            if (tep_session_is_registered('cot_gv'))
                tep_session_unregister('cot_gv');
        }

        function decode_post_vars() {
            global $_POST;
            $_POST = $this->decode_inputs($_POST);
        }

        function decode_inputs($inputs) {
            if (!is_array($inputs) && !is_object($inputs)) {
				return $inputs;
            } elseif (is_array($inputs)) {
                foreach ($inputs as $key => $value) {
                    $inputs[$key] = $this->decode_inputs($value);
                }
            }

			return $inputs;
        }

        function createOrder($order_status) {
            global $order, $order_totals, $customer_id, $cartID, $order_total_modules, $currencies, $languages_id, $paymentMethod, $shipping, $coupon, $cart_payment_id;

            $order->info['order_status'] = $order_status; // status of not paid order
            $street_adresses = $this->formatStreetAddresses();

            if ($order->info['shipping_method_code'] === 'selfdelivery') {
                $order->delivery['selfdelivery_id'] = $order->delivery['departments_id'];
            }
            if (!empty($order->info['address_id'])) {
                $address_book_query = tep_db_query("select entry_firstname,
                                                            entry_lastname,
                                                            entry_phone,
                                                            entry_fax,
                                                            entry_zone_id,
                                                            entry_shipping_method,
                                                            entry_street_address
                                                    from address_book where address_book_id = '" . (int)$order->info['address_id'] . "'");
                $address_book_arr = tep_db_fetch_array($address_book_query);

                if (empty($address_book_arr['entry_phone']) && !empty($order->customer['telephone'])) {
                    tep_db_query("UPDATE address_book SET entry_phone = '{$order->customer['telephone']}' WHERE address_book_id = '" . (int)$order->info['address_id'] . "'");
                } else {
                    $order->customer['telephone'] = $address_book_arr['entry_phone'];
                }
                $order->customer['additional_phone'] = $address_book_arr['entry_fax'];
                $order->customer['firstname'] = $address_book_arr['entry_lastname'];
                $order->customer['lastname'] = $address_book_arr['entry_firstname'];

                if ($address_book_arr['entry_shipping_method'] == 'selfdelivery') {
                    $order->delivery['street_address'] = $address_book_arr['entry_street_address'];
                    $order->delivery['shipping_module'] = (int)$address_book_arr['entry_zone_id'];
                    $order->delivery['selfdelivery_id'] = (int)$address_book_arr['entry_zone_id'];
                }

                if ($address_book_arr['entry_shipping_method'] == 'novapochta' && tep_not_null($address_book_arr['entry_zone_id']) && !tep_not_null($order->delivery['shipping_module'])) {
                    $order->delivery['shipping_module'] = explode(':', $address_book_arr['entry_zone_id'])[1];
                }
            }
            $order->delivery['selfdelivery_id'] = $order->delivery['selfdelivery_id'] ?? 0;

            $telephone = $order->customer['telephone'] ?? $_SESSION['customers_telephone'] ?? '';
            $email_address = $order->customer['email_address'] ?? $_SESSION['customers_email_address'] ?? '';
            
			$sql_data_array = [
				'customers_id'                      => ($customer_id != 0) ? $customer_id : $order->customer['customer_id'],
				'customers_name'                    => $order->customer['firstname'] . ' ' . $order->customer['lastname'],
				'customers_company'                 => $order->customer['company'],
				'customers_street_address'          => $street_adresses,
				'customers_suburb'                  => $order->customer['suburb'] ?? 'null',
				'customers_city'                    => $order->customer['city'] ?? 'null',
				'customers_state'                   => $order->customer['state'] ?? 'null',
				'customers_country'                 => $order->customer['country']['title'],
				'customers_country_id'              => (int)$order->customer['country']['id'],
				'customers_telephone'               => $telephone,
				'customers_fax'                     => $order->customer['additional_phone'],
                'customers_address_book_id'         => $order->info['address_id'] ?? $_SESSION['sendto'] ?? 'null',
				'customers_email_address'           => $email_address,
				'customers_address_format_id'       => (int)$order->customer['format_id'],
				'delivery_name'                     => $order->delivery['firstname'] . ' ' . $order->delivery['lastname'],
				'delivery_company'                  => $order->delivery['company'] ?? 'null',
				'delivery_street_address'           => $street_adresses,
				'delivery_suburb'                   => $order->delivery['suburb'] ?? 'null',
				'delivery_city'                     => $order->delivery['city'] ?? 'null',
				'delivery_state'                    => (tep_not_null($order->delivery['state']) ? $order->delivery['state'] : $order->delivery['district']),
				'delivery_country'                  => $order->delivery['country']['title'],
				'delivery_country_id'               => $order->delivery['country']['id'],
				'delivery_address_format_id'        => $order->delivery['format_id'],
				'selfdelivery_id'                   => empty($order->delivery['selfdelivery_id']) ? 0 : $order->delivery['selfdelivery_id'],
				'shipping_module'                   => $_SESSION['shipping']['title'] . (!empty($_SESSION['address_delivery']) && $_SESSION['address_delivery'] == 1 ? '' : '|' . (array_key_exists('departments_id', $order->delivery) && tep_not_null($order->delivery['departments_id']) ? (int)$order->delivery['departments_id'] : (int)$order->delivery['shipping_module'])),
				'shipping_module_code'              => !empty($shipping) ? explode('_', $shipping['id'])[1] : (!empty($order->delivery['shipping_module']) ? $order->delivery['shipping_module'] : ''),
				'address_delivery'                  => $order->delivery['address_delivery'] ?? $_SESSION['address_delivery'] ?? 0,
				'poshtomate_delivery'               => $order->delivery['poshtomate_delivery'] ?? $_SESSION['poshtomate_delivery'] ?? 0,
				'billing_name'                      => $order->billing['firstname'] . ' ' . $order->billing['lastname'],
				'billing_company'                   => $order->billing['company'] ?? 'null',
				'billing_street_address'            => $street_adresses,
				'billing_suburb'                    => $order->billing['suburb'] ?? 'null',
				'billing_city'                      => $order->billing['city'] ?? 'null',
				'billing_state'                     => isset($order->billing['state']) && tep_not_null($order->billing['state']) ? $order->billing['state'] : $order->billing['district'] ?? 'null',
				'billing_country'                   => $order->billing['country']['title'],
				'billing_address_format_id'         => $order->billing['format_id'],
				'payment_method'                    => $order->info['payment_method'] ?: $paymentMethod,
				'payment_info'                      => $GLOBALS['payment_info'] ?? 'null',
				'cc_type'                           => $order->info['cc_type'] ?? 'null',
				'cc_owner'                          => $order->info['cc_owner'] ?? 'null',
				'cc_number'                         => $order->info['cc_number'] ?? 'null',
				'cc_expires'                        => $order->info['cc_expires'] ?? 'null',
				'date_purchased'                    => 'now()',
				'orders_status'                     => $order->info['order_status'], // not paid
				'currency'                          => $order->info['currency'],
				'currency_value'                    => $order->info['currency_value'],
				'ipaddy'                            => $_SERVER["REMOTE_ADDR"],
				'payment_method_code'               => $paymentMethod,
				'payment_method_merchant'           => $order->info['payment_method_merchant'] ?? 'null',
				'customer_shopping_points_spending' => $_SESSION['customer_shopping_points_spending'] ?? 0,
				'ipisp'                             => $order->info['ipisp'] ?? getAdminIdFromSession(), //поле не может быть NULL, поэтому будет просто пустым
				'sc_cookie'                         => !empty($_COOKIE['sc']) ? tep_db_input($_COOKIE['sc']) : 'null',
				'lang_id'                           => $languages_id,
			];

			$this->setUtms($sql_data_array);

			$sql_data_array['legal_entities_bank_id'] = $order->info['legal_entities_bank_id'] ?? 'null';

            tep_db_perform(TABLE_ORDERS, $sql_data_array);
            $insert_id = tep_db_insert_id();
			$order->info['real_id'] = $insert_id;
            $fake_order_id = tep_rand_order_id($insert_id);
            $sql_data_array = array('customers_order_id' => $fake_order_id);
            tep_db_perform(TABLE_ORDERS, $sql_data_array, 'update', "orders_id = '" . (int)$insert_id . "'");

            $cart_payment_id = $cartID . '-' . $fake_order_id;
            tep_session_register('cart_payment_id');
            $_SESSION['cart_payment_id'] = $cart_payment_id;

            for ($i = 0, $n = sizeof($order_totals); $i < $n; $i++) {
				$sql_data_array = [
					'orders_id'  => $insert_id,
					'title'      => tep_db_input($order_totals[$i]['title']),
					'text'       => tep_db_input($order_totals[$i]['text']),
					'value'      => (float)$order_totals[$i]['value'],
					'class'      => tep_db_input($order_totals[$i]['code']),
					'sort_order' => (int)$order_totals[$i]['sort_order'],
				];
                tep_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);

				if ($order_totals[$i]['code'] == 'ot_discount_coupon') {
					$order->coupons[$order_totals[$i]['coupon_id']]->coupon['order_total_insert_id'] = tep_db_insert_id();
				}
            }

			if (isset($order->coupons)) {
				foreach ($order->coupons as $dummy => $coupon) {
					$coupons_to_orders = [
						'id' => (int)$coupon->coupon['id'],
						'orders_id' => $insert_id,
						'sort_order' => (int)$coupon->coupon['order_total_insert_id'],
					];

					tep_db_perform('discount_coupons_to_orders', $coupons_to_orders);
				}
			}

			$sql_data_array = [
				'orders_id'                 => (int)$insert_id,
				'orders_status_id'          => (int)$order->info['order_status'],
				'date_added'                => 'now()',
				'customer_notified'         => isAcquiring($paymentMethod) ? '0' : $order->info['send_email'],
				'customer_notified_via_sms' => isAcquiring($paymentMethod) ? '0' : $order->info['send_sms'],
				'shipping_date'             => (tep_not_null($order->info['shipping_date']) ? $order->info['shipping_date'] : 'null'),
				'shipping_time'             => (tep_not_null($order->info['shipping_time']) ? $order->info['shipping_time'] : 'null'),
				'comments'                  => (tep_not_null($order->info['comments']) ? $order->info['comments'] : 'null'),
				'dont_call_before_shipping' => (int)$order->info['dont_call_before_shipping'],
				'call_before_shipping'      => (int)$order->info['call_before_shipping'],
				'concierge'                 => (int)$order->info['concierge'],
				'give_discount_card'        => (int)$order->info['give_card'],
				'pay_for_shipping'          => (int)$order->info['paid_delivery'],
				'initiator_id'              => (int)$order->info['mid'],
				'is_lift'                   => (int)$order->info['is_lift'],
			];

            tep_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

            /* bof add data for 1C export */
			if (!isAcquiring($paymentMethod)) {
				$this->addTaskExportToOneC($order->products, $insert_id, $fake_order_id);
			}
            /* eof add data for 1C export */

			/* bof update customer in 1C by queue */
			if (tep_not_null($customer_id, true)) {
				$data = [
					'cid'      => $customer_id,
					'function' => 'Update',
					'source'   => 'onepage_checkout',
				];
				queueAdd('customer', $data);
			}
			/* eof update customer in 1C by queue */

            /*
            START отправка debug письма если указанные в условии переменные пустые
            */
            $fields = [
                'paymentMethod'             => $paymentMethod ?? null,
                'order.info.payment_method' => $order->info['payment_method'] ?? null,
                'shipping'                  => $shipping ?? null,
                'telephone'                 => $telephone ?? null,
                'email_address'             => $email_address ?? null,
                'street_addresses'          => $street_adresses ?? null,
            ];

            // Если есть пропуски — логируем (вернёт true, если что-то залогировал)
            $this->dumpOrderErrorIfMissing($order, $fake_order_id, $fields, $customer_id);
            /*
            END отправка debug письма если указанные в условии переменные пустые
            */
            
            $products_ordered = '';
            if (!isAcquiring($paymentMethod)) {
                $points_toadd = get_points_toadd($order);
                tep_add_pending_points($customer_id, $insert_id, $points_toadd, TEXT_DEFAULT_COMMENT, 'SP');
                if (isset($_SESSION['customer_shopping_points_spending']) && $_SESSION['customer_shopping_points_spending']) {
                    tep_redeemed_points($customer_id, $insert_id, $_SESSION['customer_shopping_points_spending']);
                }
            }

            for ($i = 0, $n = sizeof($order->products); $i < $n; $i++) {
				if (!isAcquiring($paymentMethod)) {
                    $this->reduceProductStock($order->products[$i]);
                }

                // Update products_ordered (for bestsellers list)
                tep_db_query("update " . TABLE_PRODUCTS . "
                              set products_ordered = products_ordered + " . sprintf('%d', $order->products[$i]['qty']) . "
                              where products_id = '" . tep_get_prid($order->products[$i]['id']) . "'");

				$sql_data_array = [
					'orders_id'         => $insert_id,
					'products_id'       => tep_get_prid($order->products[$i]['id']),
					'products_model'    => $order->products[$i]['model'],
					'products_name'     => $order->products[$i]['name'],
					'products_price'    => $order->products[$i]['price'],
					'final_price'       => $order->products[$i]['final_price'],
					'products_tax'      => $order->products[$i]['tax'],
					'products_quantity' => $order->products[$i]['qty'],
					'legal_entities_id' => (tep_not_null($order->products[$i]['legal_entities_id']) ? $order->products[$i]['legal_entities_id'] : 'null'),
					'variant_id'        => (tep_not_null($order->products[$i]['variant_id']) ? $order->products[$i]['variant_id'] : 'null'),
				];
                tep_db_perform(TABLE_ORDERS_PRODUCTS, $sql_data_array);

                $order_products_id = tep_db_insert_id();

				$_SESSION['productsIsBought'][$order_products_id] = tep_get_prid($order->products[$i]['id']);
				krsort($_SESSION['productsIsBought']);

                $order_total_modules->update_credit_account($i); //ICW ADDED FOR CREDIT CLASS SYSTEM

                $products_ordered_attributes = '';

                if (isset($order->products[$i]['attributes'])) {
                    for ($j = 0, $n2 = sizeof($order->products[$i]['attributes']); $j < $n2; $j++) {
                        if (!isAcquiring($paymentMethod) && isset($order->products[$i]['qty']) && !empty($order->products[$i]['qty'])) {
                            $this->reduceAttributeStock($order->products[$i], $order->products[$i]['attributes'][$j]);
                        }

                        $attributes = tep_db_query("select popt.products_options_name,
                                                               poval.products_options_values_name,
                                                               poval.products_options_values_id,
                                                               pa.options_values_price,
                                                               pa.price_prefix,
                                                               pa.options_values_weight,
                                                               pa.1c_attr_id,
                                                               pa.legal_entities_id
                                                        from " . TABLE_PRODUCTS_OPTIONS . " popt,
                                                             " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval,
                                                             " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                                                        where pa.products_id = '" . tep_get_prid($order->products[$i]['id']) . "' and
                                                              pa.options_id = '" . (int)$order->products[$i]['attributes'][$j]['option_id'] . "' and
                                                              pa.options_id = popt.products_options_id and
                                                              pa.options_values_id = '" . (int)$order->products[$i]['attributes'][$j]['value_id'] . "' and
                                                              pa.options_values_id = poval.products_options_values_id and
                                                              popt.language_id = '" . (int)$languages_id . "' and
                                                              poval.language_id = '" . (int)$languages_id . "'");

                        $attributes_values = tep_db_fetch_array($attributes);

                        if (!tep_not_null($attributes_values['products_options_name']) && tep_not_null($order->products[$i]['attributes'][$j]['option_id'])) {
                            $products_options_temp = tep_options_name($order->products[$i]['attributes'][$j]['option_id']);
                        } else {
                            $products_options_temp = $attributes_values['products_options_name'];
                        }

                        if (!tep_not_null($order->products[$i]['attributes'][$j]['value']) && tep_not_null($order->products[$i]['attributes'][$j]['value_id'])) {
                            $products_options_values_temp = tep_values_name($order->products[$i]['attributes'][$j]['value_id']);
                        } else {
                            $products_options_values_temp = $order->products[$i]['attributes'][$j]['value'];
                        }

						$sql_data_array = [
							'orders_id'                  => $insert_id,
							'orders_products_id'         => $order_products_id,
							'products_options'           => $products_options_temp,
							'products_options_id'        => $order->products[$i]['attributes'][$j]['option_id'],
							'products_options_values'    => $products_options_values_temp,
							'products_options_values_id' => $order->products[$i]['attributes'][$j]['value_id'],
							'options_values_price'       => $order->products[$i]['final_price'],
							'price_prefix'               => $attributes_values['price_prefix'],
							'options_values_weight'      => tep_not_null($attributes_values['options_values_weight']) ? $attributes_values['options_values_weight'] : 'null',
							'1c_attr_id'                 => tep_not_null($attributes_values['1c_attr_id']) ? $attributes_values['1c_attr_id'] : 'null',
							'legal_entities_id'          => tep_not_null($attributes_values['legal_entities_id']) ? $attributes_values['legal_entities_id'] : 'null',
						];
                        tep_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES, $sql_data_array);

                        $products_ordered_attributes .= "\n\t" . $attributes_values['products_options_name'] . ' ' . tep_decode_specialchars($order->products[$i]['attributes'][$j]['value']);
                    }
                }

                $products_ordered .= $order->products[$i]['qty'] . ' x ' . $order->products[$i]['name'] . ' (' . $order->products[$i]['model'] . ') = ' . $currencies->display_price($order->products[$i]['final_price'], $order->products[$i]['qty']) . $products_ordered_attributes . "<br>";
            }

			/* не делаем массив уникальным, потому как ломается логика определения последнего купленного товара для определения к-ва баллов за отзыв */
            //$_SESSION['productsIsBought'] = array_unique($_SESSION['productsIsBought']);

			// обновление остатков и выключение аттрибутов/товаров по количеству
			if (!isAcquiring($paymentMethod)) {
				if (TRACK_STOCK_BALANCES_BY_SELLER == 'true') {
					updateProductStock($order->products, $fake_order_id, $insert_id);
				} else {
					$this->disableProductsByStock($order->products, $fake_order_id, $insert_id);
				}
			}

			// send to session because we will need it when we will come back from payment service page
			$_SESSION['$products_ordered'] = $products_ordered;
            $_SESSION['last_orders_id'] = $fake_order_id;

			// start добавление записи в очередь Redis для данных о заказе в eSputnik
			if (MEASUREMENT_PROTOCOL_SEND_METHOD == 'queue') {
				$this->addTaskSendOrderToGA($fake_order_id, $order, 'onepage-сheckout');
			}

            return $fake_order_id;
        }

        function createEmails($insert_id, $direction = 'both') {
            global $order, $order_totals, $currencies, $customer_name, $products_ordered, $proportion, $customer_id, $paymentMethod, $easy_discount, $payment_modules, $cat_tree, $cat_names, $admin, $languages_id, $language_changed_for_law, $easyDiscountData;

			$current_date = new DateTime();
			$today = $current_date->format('d.m.Y');

            if (!isset($products_ordered)) {
                $products_ordered = $_SESSION['$products_ordered'];
            }

            if (!isset($customer_name) || $customer_name != $order->delivery['firstname'] . ' ' . $order->delivery['lastname']) {
                $customer_name = $order->delivery['firstname'] . ' ' . $order->delivery['lastname'];
            }

            $customer_name = str_replace("&nbsp;", "", htmlentities(trim($customer_name ?? ''), 0, CHARSET));
			$order_total_value = isset($order->info['total_value']) && tep_not_null($order->info['total_value']) ? $order->info['total_value'] : $order->info['total'];

            // lets start with the email confirmation
            $email_order = "<b>" . STORE_NAME . "</b><br />" .
                EMAIL_SEPARATOR . "<br />" .
                EMAIL_TEXT_ORDER_NUMBER . '' . $insert_id . "<br />" .
                EMAIL_TEXT_INVOICE_URL . ' ' . tep_href_link(FILENAME_ORDERS, 'order=' . $insert_id, 'SSL', false) . "<br />" .
                EMAIL_TEXT_DATE_ORDERED . ' ' . $today . "<br />" .
                EMAIL_TEXT_CUSTOMER_NAME . ' ' . $customer_name . "<br />" .
                EMAIL_TEXT_CUSTOMER_EMAIL_ADDRESS . ' ' . $order->customer['email_address'] . "<br />" .
                EMAIL_TEXT_CUSTOMER_TELEPHONE . ' ' . $order->customer['telephone'] . "<br /><br />";

            if ($order->info['comments']) {
                $email_order .= tep_db_output($order->info['comments']) . "<br><br>";
            }

            $email_order .= '<br /><b>' . EMAIL_TEXT_PRODUCTS . '</b><br /><br />' . $products_ordered . '<br /><br />';

            $order_total = '';
            for ($i = 0, $n = sizeof($order_totals); $i < $n; $i++) {
                $email_order .= strip_tags($order_totals[$i]['title']) . ' ' . strip_tags($order_totals[$i]['text']) . "<br />";

                $order_total .= '<b>' . strip_tags($order_totals[$i]['title']) . '</b> ' . $order_totals[$i]['text'] . "<br />";
            }

            $email_order .= "<br /><b>" . EMAIL_TEXT_DELIVERY_ADDRESS . "</b><br />" .
                EMAIL_SEPARATOR . "<br />" .
                $this->getAddressFormatted('sendto', true) . "<br /><br>";

            $email_order .= "<b>" . EMAIL_TEXT_PAYMENT_METHOD . "</b><br />" . EMAIL_SEPARATOR . "<br>";

			$payment_method = '';
            $payment_checkout_mode = 'in_checkout_proccess';
            if (isset($GLOBALS[$paymentMethod]) && is_object($GLOBALS[$paymentMethod])) {
                $payment_class = $GLOBALS[$paymentMethod];
                if (isset($payment_class->checkout_mode) && tep_not_null($payment_class->checkout_mode)) {
                    $payment_checkout_mode = $payment_class->checkout_mode;
                }
                $email_order .= $payment_class->title . "<br /><br />";
                $payment_method .= $payment_class->title;

                if (isset($payment_class->email_footer) && $payment_class->email_footer) {
					$bank_id = ($order->info['legal_entities_bank_id']);
                    $email_order .= $payment_class->email_footer . '<br /><br />';
					if (method_exists($payment_class, 'getRequisites')) {
						$requisites = $payment_class->getRequisites($languages_id, $bank_id);
						$payment_class->email_footer = str_replace(['{{BANK_NAME}}', '{{IBAN}}', '{{MFO}}', '{{INN}}', '{{NAME}}', '{{PURPOSE_OF_PAYMENT}}'], [$requisites['bank_name'], $requisites['iban'], $requisites['mfo'], $requisites['inn'], $requisites['name'], $requisites['purpose_of_payment']], $payment_class->email_footer);
					}
                    $payment_method .= '<br /><br />' . sprintf($payment_class->email_footer, $insert_id, $currencies->format($order_total_value)) . '<br /><br />';
                }
            } elseif ($order->info['payment_method']) {
                $email_order .= $order->info['payment_method'] . "<br /><br />";
                $payment_method .= $order->info['payment_method'];

                if (basename($_SERVER['SCRIPT_NAME']) == 'liqpay_callback.php' && tep_not_null($order->info['payment_method_code'])) {
                    $paymentMethod = $order->info['payment_method_code'];
                    if (!class_exists('payment')) {
                        require(DIR_WS_CLASSES . 'payment.php');
                    }
                    $payment_modules = new payment($paymentMethod);
                    if (is_object($GLOBALS[$paymentMethod]) && tep_not_null($GLOBALS[$paymentMethod]->checkout_mode)) {
                        $payment_checkout_mode = $GLOBALS[$paymentMethod]->checkout_mode;
                    }
                }
            }

            if ($payment_checkout_mode != 'in_checkout_proccess') {
                if (basename($_SERVER['SCRIPT_NAME']) == 'checkout.php') {
                    $direction = 'customer';
                } elseif (basename($_SERVER['SCRIPT_NAME']) == 'liqpay_callback.php') {
                    $direction = 'internal';
                }
            }

            $is_kiev = false;
            $is_selfdelivery = false;
            $is_address_delivery = false;
            $is_kiev_np_delivery = false;
            $is_kiev_np_address_delivery = false;

            if ($order->delivery['country_id'] == '220') {
                $is_kiev = true;
            }
            
            if (empty($order->info['shipping_method_code']) && !empty($_SESSION['onepage']['delivery']['shipping_module'])) {
                $order->info['shipping_method_code'] = $_SESSION['onepage']['delivery']['shipping_module'];
            }
            
            if ($order->info['shipping_method_code'] == 'selfdelivery') {
                $is_selfdelivery = true;
            }
            if ($is_kiev && $order->info['shipping_method_code'] == 'flat' || !$is_kiev && isset($_SESSION['address_delivery']) && $_SESSION['address_delivery'] == '1') {
                $is_address_delivery = true;
            }
            if ($is_kiev && $order->info['shipping_method_code'] == 'novapochta') {
                $is_kiev_np_delivery = true;
            }
            if ($is_kiev_np_delivery && isset($_SESSION['address_delivery']) && $_SESSION['address_delivery'] == '1') {
                $is_kiev_np_address_delivery = true;
            }

			$customers_orders_total = tep_count_customer_orders($customer_id, false);
            $gift = gift_list($customer_id, $insert_id, $order, $customers_orders_total);
			$public_gift = '';
            if ($gift != '') {
				$public_gift_data = tep_get_gift_history($customer_id, $order->info['real_id'], true);
                if (!empty($public_gift_data)) {
					$public_gift .= '<div style="margin-bottom: 10px;"><b>' . TEXT_GIFT . ' :)</b></div>';
                    foreach ($public_gift_data as $_gid => $_gdata) {
						$search = [
							'{{PERCENT}}',
							'{{MIN_ORDER}}',
							'{{EXPIRED}}',
							'{{CODE}}',
						];

						$replace = [
							$_gdata['coupons_discount_amount'] * 100,
							(int)$_gdata['coupons_min_order'] ,
							tep_date_long_translate(date('d F Y H:i', strtotime($_gdata['coupons_date_end']))),
							$_gdata['coupons_id'],
						];

						$_gdata['description'] = str_replace($search, $replace, $_gdata['description']);

						$public_gift .= '<div style="border-top:1px solid #e0e0e0;padding-top: 5px;padding-bottom: 5px;">' . strip_tags($_gdata['description'], '<br><span><strong>') . '</div>';
					}
				}
			}

            if ($content_email_array = get_email_contents('create_order')) {

                $delivery_cost_info = ''; // done
                $shipping_date_text = ''; // done
                $shipping_time_text = ''; // done
                $ukraine_sending_due = ''; // done
				$delivery_cost_pay_customer = ''; // done
                $targeted_delivery = ''; // done
                $suburban_delivery = ''; // done
                $self_delivery_reserve = ''; // done
                $comments = '';
                $store_address = '';
                $shop_map_link = '';
                $failed_to_contact = (!empty($order->info['failed_to_contact']) ? TEXT_FAILED_TO_CONTACT_SHORT : ''); // done
                $shipping_time_short_text = ''; // done
                $shipping_time_short = [
                    1 => 'ДЕНЬ',
                    2 => 'ДЕНЬ',
                    3 => 'ДЕНЬ',
                    4 => 'ВЕЧЕР',
                    5 => 'ВЕЧЕР',
                    6 => 'ДЕНЬ',
                    7 => 'ДЕНЬ',
                    8 => 'ВЕЧЕР',
                    9 => 'ДЕНЬ',
                    10 => 'ДЕНЬ',
                ];
                $delivery_method_code_array = [
                    'flat'         => 'К',
                    'selfdelivery' => 'С',
                    'novapochta'   => 'НП',
                ];

                $shops_code_array = get_shop_code();
				$shops_array = get_shop_code('long');

				$payment_method_code_array = [
					'pod'       => 'COD',
					'cod'       => 'НАЛ',
					'cardod'    => 'КРТ',
					'rusbank'   => 'Б/Н',
					'urbank'    => 'ТОВ',
				];

				foreach (getPaymentProviders() as $paymentName => $dummy) {
					$payment_method_code_array[$paymentName] = 'CARD';
				}

                $shipping_time = get_delivery_schedule_times();
				$street_adresses = $this->formatStreetAddresses();
                $store_categories = '';
                $sci = 0;
                foreach (array_keys($cat_tree) as $fcat) {
                    if ($sci < 5)
                        $store_categories .= '<a style="text-decoration:underline;color:inherit" href="' . tep_href_link(FILENAME_DEFAULT, 'cPath=' . $fcat, 'NONSSL') . '"><span>' . $cat_names[$fcat] . '</span></a><span style="padding:0 5px">&bull;</span>';
                    $sci++;
                }

                if ($is_kiev && !$is_kiev_np_delivery && !$is_kiev_np_address_delivery && ($is_selfdelivery || $is_address_delivery)) {
                    $shipping_date_raw = $order->info['shipping_date'];
                    $shipping_date_text = dateToRusDate($order->info['shipping_date']);
                    $shipping_time_text = $shipping_time[$order->info['shipping_time']];
                    $shipping_time_short_text = $shipping_time_short[$order->info['shipping_time']];
                }

                if ($is_kiev && $is_address_delivery) {
                    if (tep_not_null($order_total_value) && $order_total_value <= (int)ORDER_COST_FOR_FREE_SHIPPING) {
                        $delivery_cost_info .= '<div>' . DELIVERY_COST . ':</div>';
                        $delivery_cost_info .= '<div>';
                        $delivery_cost_info .= DELIVERY_COST . '*: <b>' . $currencies->format(($order_total_value <= ((int)ORDER_COST_FOR_FREE_SHIPPING - (int)SHIPPING_COST) ? (int)SHIPPING_COST : ((int)ORDER_COST_FOR_FREE_SHIPPING - $order_total_value)), false) . '</b>';
                        if ($order_total_value <= ((int)ORDER_COST_FOR_FREE_SHIPPING - (int)SHIPPING_COST)) {
                            $delivery_cost_info .= '<div>' . DELIVERY_COST_FULL . '</div>';
                        } else {
                            $delivery_cost_info .= '<div>' . DELIVERY_COST_PART . '</div>';
                        }
                        $delivery_cost_info .= '<div>*' . DELIVERY_COST_SMALL_ORDERS . '</div>';
                        $delivery_cost_info .= '</div>'; // конец стоимость доставки
                    }
                }

                if ($is_kiev && $is_selfdelivery) {
                    if (preg_match('/' . TEXT_TROESCHINA . '/i', $street_adresses)) {
                        $shop_map_link = 'N3hYNyQYC3o';
                    } elseif (preg_match('/' . TEXT_KHARKOVSKIY . '/i', $street_adresses)) {
                        $shop_map_link = 'uc4zfEbyWdU2';
                    } elseif (preg_match('/' . TEXT_VINOGRADAR . '/i', $street_adresses)) {
                        $shop_map_link = 'RSM7M3vYA8k';
                    } elseif (preg_match('/' . TEXT_DARNITCA . '/i', $street_adresses)) {
                        $shop_map_link = 't3TnZAxzjcp';
                    }
                    if (!empty($shop_map_link)) {
                        $store_address = '<table cellpadding="0" cellspacing="0" style="border:0;padding:0;margin:0;border-collapse:collapse;width:100%"><tr><td style="width:20px;vertical-align:top;padding-top:3px"><a href="https://goo.gl/maps/' . $shop_map_link . '" target="_blank" style="text-decoration:none"><img src="https://zootovary.ua/ext/images/email_shop_address.png" width="10" height="15" style="border:0;display:block;line-height:0;outline:0"></a></td><td style="vertical-align:top;font-size:13px;color:#999999;line-height:20px"><a href="https://goo.gl/maps/' . $shop_map_link . '" target="_blank" style="text-decoration:none;color:#76c000"><b>' . LOOK_AT_THE_MAP . '</b></a></td></tr></table>';
                    }
                    $self_delivery_reserve = SELF_DELIVERY_SCHEDULE_TEXT . '. ' . SELF_DELIVERY_RESERVE;
                }
                if ($is_kiev && !$is_selfdelivery) {
                    if (detect_suburb_delivery_address($street_adresses)) {
                        $suburban_delivery = mb_strtoupper(SUBURBAN_DELIVERY);
                    }
                }

                if (!$is_kiev || $is_kiev_np_delivery) {
                    if ($order->info['shipping_method_code'] == 'novapochta') {
                        if ((int)MODULE_SHIPPING_NOVAPOCHTA_COST == 0 || MODULE_SHIPPING_NOVAPOCHTA_COST == '') {
                            if ($order->info['shipping_method_code'] == 'novapochta') {
                                $ukraine_sending_due = '<br>' . TEXT_UKRAINE_SENDING_DUE_CUSTOMER;
								$delivery_cost_pay_customer = TEXT_UKRAINE_SENDING_DUE_CUSTOMER;
                            }
                        } else {
                            $ukraine_sending_due = '<br>' . TEXT_UKRAINE_SENDING_DUE_ZOOTOVARY;
                        }
                        if (($is_address_delivery || $is_kiev_np_address_delivery) && $order->info['shipping_method_code'] == 'novapochta') {
                            $targeted_delivery = TEXT_ADDRESS_DELIVERY . ($order->info['is_lift'] == 1 ? ', ' . TEXT_ELEVATOR_YES : ', ' . TEXT_ELEVATOR_NO) . '<br>';
                        }
                        if ($order->info['payment_method_code'] == 'pod') {
                            if (defined('MODULE_PAYMENT_POD_INCLUDE_DELIVERY_COST')) {
                                if (MODULE_PAYMENT_POD_INCLUDE_DELIVERY_COST == 'False') {
                                    $ukraine_sending_due = '<br><br>' . TEXT_UKRAINE_SENDING_ALERT . ' ' . TEXT_UKRAINE_SENDING_POD . '! ' . TEXT_UKRAINE_SENDING_DUE_CUSTOMER . '!<br>До оплати ' . $order->info['total'] . ' ₴';
									$delivery_cost_pay_customer = TEXT_UKRAINE_SENDING_DUE_CUSTOMER;
                                } else {
                                    if ((float)$order_total_value >= (float)ORDER_COST_FOR_FREE_SHIPPING) {
                                        $ukraine_sending_due = '<br><br>' . TEXT_UKRAINE_SENDING_ALERT . ' ' . TEXT_UKRAINE_SENDING_POD . '! ' . TEXT_UKRAINE_SENDING_DUE_ZOOTOVARY . '<br>До оплати ' . $order->info['total'] . ' ₴';
                                    } else {
                                        $ukraine_sending_due = '<br><br>' . TEXT_UKRAINE_SENDING_ALERT . ' ' . TEXT_UKRAINE_SENDING_POD . '! ' . TEXT_UKRAINE_SENDING_DUE_ZOOTOVARY . '<br>До оплати ' . $order->info['total'] . ' ₴ (' . TEXT_UKRAINE_SENDING_DUE_ZOOTOVARY_DELIVERY_PAYED . ')';
                                    }
                                }
                            }
                        }

                        if (defined(MODULE_CALCULATE_PROPORTION_STATUS) && MODULE_CALCULATE_PROPORTION_STATUS == 'true') {
                            if (!is_object($proportion)) {
                                require_once(DIR_WS_MODULES . 'dates/calculate_proportion.php');
                                $proportion = new calculate_proportion;
                            }
                            $proportion->process();
                            if ($proportion->get_disallow()) {
                                $ukraine_sending_due = '<br>' . TEXT_UKRAINE_SENDING_DUE_CUSTOMER;
								$delivery_cost_pay_customer = TEXT_UKRAINE_SENDING_DUE_CUSTOMER;
                            }
                        }
                    }
                }

                $pade_delivery = (!empty($order->info['paid_delivery']) ? mb_strtolower(DELIVERY_COST) . ' ' . SHIPPING_COST . ' ₴' : ''); // done
                if (!empty($order->customer['comments']) || !empty($pade_delivery)) {
                    $comments = SplitText(tep_db_output($order->customer['comments']), 50, '', '', '&shy;') . (!empty($order->customer['comments']) ? '<br>' : '') . $pade_delivery;
                }

                $quarantine_delivery_alert = (checkDateRange(MODULE_DATES_QUARANTINE_START, MODULE_DATES_QUARANTINE_END) && ($is_kiev || $is_kiev_np_address_delivery) && !$is_kiev_np_delivery && !$is_selfdelivery ? WAR2022_DELIVERY_ALERT : '');

                $array_from_template_to_template = [
                    '{STORE_URL}'            => HTTP_SERVER,
                    '{TEMPLATE_PATH}'        => HTTP_SERVER . '/' . DIR_WS_TEMPLATES . TEMPLATE_NAME . '/images/',
                    //'{STORE_LOGO}'           => HTTP_SERVER . '/' . LOGO_IMAGE,
                    '{STORE_NAME}'           => STORE_NAME,
                    '{CUSTOMER_EMAIL}'       => $order->customer['email_address'],
                    '{ORDER_NUMBER}'         => $insert_id,
                    '{STORE_PHONE}'          => tep_get_render_phones(),
                    '{EMAIL_HEADER_TITLE}'   => EMAIL_HEADER_TITLE_NEW_ORDER,
                    '{EMAIL_HEADER_TITLE_2}' => EMAIL_HEADER_TITLE_NEW_ORDER_2,
                    '{EMAIL_FOOTER_TITLE}'   => EMAIL_FOOTER_TITLE_NEW_ORDER,
                    '{EMAIL_FOOTER_TITLE_2}' => '',
                ];

                if (tep_not_null($order->info['shipping_method_code'])) {
                    if ($language_changed_for_law &&
                        $languages_id == 3 &&
                        isset($GLOBALS[$order->info['shipping_method_code']]) &&
                        tep_not_null($GLOBALS[$order->info['shipping_method_code']]->title))
                    {
                        $order->info['shipping_method'] = $GLOBALS[$order->info['shipping_method_code']]->title;
                    }
                }

                // array to replace variables from html template:
                $array_from_to = array(
					'{HEADER}'                    => prepare_email_template(get_email_contents('header__template')['content_html'], $array_from_template_to_template),
					'{FOOTER}'                    => prepare_email_template(get_email_contents('footer__template')['content_html'], $array_from_template_to_template),
					'{TEMPLATE_PATH}'             => HTTP_SERVER . '/' . DIR_WS_TEMPLATES . TEMPLATE_NAME . '/images/',
					'{STORE_NAME}'                => STORE_NAME,
					'{ORDER_NUMBER}'              => $insert_id,
					'{DETAILED_I}'                => tep_href_link(FILENAME_ORDERS, 'order=' . $insert_id, 'SSL', false),
					'{DATE_ORDER}'                => dateToRusDate(date("Y-m-d H:i:s"), true),
					'{CUSTOMER_NAME}'             => $customer_name,
					'{CUSTOMER_EMAIL}'            => $order->customer['email_address'],
					'{CUSTOMER_PHONE}'            => $order->customer['telephone'],
					'{CUSTOMER_ADDITIONAL_PHONE}' => $order->customer['additional_phone'] ?? '',
					'{PRODUCTS}'                  => prepare_products_list($order->products, false, true), //$products_ordered
					'{ORDER_TOTALS}'              => strip_tags($order_total, '<br>'),
					'{DELIVERY_ADDRESS}'          => (empty($suburban_delivery) ? ($order->delivery['country']['title'] ?? $order->delivery['country']) . ', ' : '') . ($is_kiev && !$is_selfdelivery ? $this->getAddressFormatted('sendto', true) : $street_adresses),
					'{ORDER_COMMENTS}'            => $comments,
					'{DELIVERY_DATE}'             => $shipping_date_text,
					'{DELIVERY_TIME}'             => $shipping_time_text,
					'{FAILED_TO_CONTACT}'         => $failed_to_contact,
					'{GIVE_GIFT}'                 => $public_gift,
					'{DELIVERY_METHOD}'           => $targeted_delivery . $order->info['shipping_method'],
					'{DELIVERY_COST_INFO}'        => $delivery_cost_info,
					//'{STORE_LOGO}'                => HTTP_SERVER . '/' . LOGO_IMAGE,
					'{STORE_URL}'                 => HTTP_SERVER,
					'{STORE_OWNER_EMAIL}'         => STORE_OWNER_EMAIL_ADDRESS,
					'{STORE_ADDRESS}'             => $store_address,
					'{STORE_PHONE}'               => tep_get_render_phones(),
					'{STORE_CATEGORIES}'          => $store_categories,
					'{PAYMENT_METHOD}'            => $payment_method,
					'{QUARANTINE_DELIVERY_ALERT}' => $quarantine_delivery_alert,
					'{SELF_DELIVERY_RESERVE}'     => $self_delivery_reserve,
                );

                $email_order = prepare_email_template($content_email_array['content_html'], $array_from_to);

            } else {
                $caller_info = get_caller_info();
                tep_mail_html('', 'debug@zootovary.ua', 'Not found template!', 'Not found template! ' . "\n\t" . $caller_info, 'DEBUG ZOOTOVARY.UA', 'debug@zootovary.ua');
            }

            if ($direction == 'both' || $direction == 'customer') {
				if (isset($order->info['send_email']) ? $order->info['send_email'] == 1 : SEND_EMAILS == 'true') {
					tep_mail_html($customer_name, $order->customer['email_address'], $content_email_array['subject'] . $insert_id . ' - ' . $today, $email_order, $content_email_array['from_name'], $content_email_array['from_email']);
                }

                // send email to customer:
                if (CHECKOUT_EMAIL_DEBUG == 'true') {
                    echo 'sending email to ' . $order->customer['email_address'];
                    echo $email_order;
                }
            }

            // создание параметра в сессии для отправка SMS о заказе start
            if (SEND_EXTRA_ORDER_SMS == 'false') { // если запрещена отправка sms в настройках магазина = отменяем отправку смс
                $order->info['send_sms'] = false;
            }
            if (isset($order->info['send_sms']) ? $order->info['send_sms'] == 1 : SEND_EXTRA_ORDER_SMS == 'true') {
                $sendSms = true;
            } else {
                $sendSms = false;
            }
            if (!tep_session_is_registered('sendSms')) {
                tep_session_register('sendSms');
            }
            $_SESSION['sendSms'] = $sendSms;
            // отправка SMS end

			$sendInternalEmail = SEND_EXTRA_ORDER_EMAILS_TO != '';

			if (isset($order->info['send_email']) && $order->info['send_email'] == 0) {
				$sendInternalEmail = false;
			}

            // отправка письма с накладной start
            if ($sendInternalEmail && ($direction == 'both' || $direction == 'internal')) {
                if ($content_email_array = get_email_contents('create_order_internal')) {
					$contract_of_agency = ''; // done
                    $order_total = ''; // done
                    $customer_debt = ''; //done
                    $first_order_mark = ''; // done
                    $first_order_css_mark = ''; // done
					$problem_client_css_mark = tep_check_is_problem_client($customer_id) ? 'border-left:solid 10px black !important;padding-left:5px;' : '';

                    if (!empty($order->info['payment_method_code']) && in_array($order->info['payment_method_code'], ['rusbank', 'urbank'])) {
						if (in_array($order->info['legal_entities_bank_id'], [6])) {
							$payment_method_code_css_mark = 'background-color:#000; color:#fff;';
						} else {
							$payment_method_code_css_mark = 'border-top:solid 10px black !important;border-bottom:solid 10px black !important;padding-top: 0;padding-bottom:0;';
						}
                    } elseif (!empty($order->info['payment_method_code']) && $order->info['payment_method_code'] == 'pod') {
                        $payment_method_code_css_mark = 'border-left:solid 10px black !important;border-right:solid 10px black !important;';
                    } else {
                        $payment_method_code_css_mark = '';
                    }
                    $delivery_method_code = $delivery_method_code_array[$order->info['shipping_method_code']]; // done
                    $payment_method_code = (tep_not_null($paymentMethod) && array_key_exists($paymentMethod, $payment_method_code_array) ? $payment_method_code_array[$paymentMethod] : $payment_method_code_array[$order->info['payment_method_code']]); // done
                    $dont_call_before_shipping = (!empty($order->info['dont_call_before_shipping']) ? mb_strtoupper(TEXT_DONT_CALLBACK_BEFORE) : ''); // done
                    $call_before_shipping = (!empty($order->info['call_before_shipping']) ? mb_strtoupper(TEXT_CALLBACK_BEFORE) : ''); // done
                    $failed_to_contact = (!empty($order->info['failed_to_contact']) ? mb_strtoupper(TEXT_FAILED_TO_CONTACT) : ''); // done
                    $give_discount_cart = (!empty($order->info['give_card']) ? mb_strtoupper(GIVE_CARD . ' ' . substr($order->customer['email_address'], 0, strpos($order->customer['email_address'], "@"))) : ''); // done
                    $leave_order_concierge = (!empty($order->info['concierge']) ? mb_strtoupper(TEXT_LEAVE_ORDER_WITH_CONCIERGE) : '');
                    $global_discount = ''; //done
                    $self_delivery_shop = ($is_selfdelivery ? mb_strtoupper($order->info['shipping_method']) . (!empty($order->delivery['selfdelivery_id']) ? ' ' . mb_strtoupper( $shops_array[$order->delivery['selfdelivery_id']]) : '') : '');

                    check_turbosms_status(HTTPS_SERVER . DIR_WS_HTTPS_CATALOG . $admin . '/includes/classes/barcode/barcode.php?text=' . $insert_id);
                    $order_sku = '<img style="margin-top:5px;margin-bottom:5px;width:286px;height:41px;" src=' . $insert_id . '.png>';
                    //$order_sku = '<img style="margin-top:5px;margin-bottom:5px" src=' . data_uri(DIR_FS_CATALOG . 'temp/barcode/' . $insert_id . '.png','image/png') . '>';

                    $order_total_text = strip_tags($order_totals[sizeof($order_totals) - 1]['text']);
                    $order_total_value = strip_tags($order_totals[sizeof($order_totals) - 1]['value']);

                    if ($customer_id > 0) {
                        if ($customers_orders_total == 1) {
                            $first_order_css_mark = "border-left:solid 10px black !important;";

                            if (MODULE_PAYMENT_POD_STATUS == 'True') {
                                $_set_customers_allow_pod_payment = false;
                                if (MODULE_PAYMENT_POD_ZONE == 1 && !$is_kiev) {
                                    $_set_customers_allow_pod_payment = true;
                                } elseif (MODULE_PAYMENT_POD_ZONE == 2) {
                                    $_set_customers_allow_pod_payment = true;
                                } elseif (MODULE_PAYMENT_POD_ZONE == 3 && $is_kiev) {
                                    $_set_customers_allow_pod_payment = true;
                                }
                                if ($_set_customers_allow_pod_payment) {
                                    tep_db_query("UPDATE " . TABLE_CUSTOMERS . "
                                                  SET customers_allow_pod_payment = '1'
                                                  WHERE customers_id = '" . (int)$customer_id . "'");
                                }
                            }
                        } elseif ($customers_orders_total > 0) {
                            $first_order_mark = '<div style="font-size:10px;margin-top:10px">' . $customers_orders_total . '</div>';
                        }
                    }

					// добавление в накладную текста о Дополнительной скидке
                    if (!empty($easyDiscountData)) {
                        $global_discount = easy_discount_description_display() . '<div style="font-weight: normal">' . ADDITIONAL_DISCOUNT . ' ' . $easy_discount->total() . '% (' . FROM . ' ' . tep_date_long_without_day($easyDiscountData['start'], '-') . ' по ' . tep_date_long_without_day($easyDiscountData['end'], '-') . ')</div>';
                    }

                    for ($i = 0, $n = sizeof($order_totals); $i < $n; $i++) {
                        $order_total .= strip_tags($order_totals[$i]['title']) . ' ' . strip_tags($order_totals[$i]['text']) . "<br />";
                    }

                    // добавление в накладную текста Задолженности клиента/клиенту
                    if (tep_not_null($customer_id)) {
                        $debt_query = tep_db_query("select customers_info_id, customers_info_credit_value from " . TABLE_CUSTOMERS_INFO . " where customers_info_id  = '" . (int)$customer_id . "'");
                        $debt_values = tep_db_fetch_array($debt_query);
                        if (!empty($debt_values['customers_info_credit_value']) && $debt_values['customers_info_credit_value'] != 0) {
                            if ($debt_values['customers_info_credit_value'] > 0) {
                                $customer_debt = DEBT_TO_SHOP . ' ' . $currencies->format($debt_values['customers_info_credit_value']);
                            } else {
                                $customer_debt = DEBT_TO_CUSTOMER . ' ' . $currencies->format($debt_values['customers_info_credit_value'] * -1);
                            }
                        }
                    }

                    // добавление в накладную текста Договора поручения
                    if (defined('ADD_CONTRACT_OF_AGENCY') && ADD_CONTRACT_OF_AGENCY == 'true' && $order->info['payment_method_code'] == 'cod') {
						if ($content_contract_of_agency_array = get_email_contents('contract_of_agency')) {
							require 'admin/summa_ukr.php';
							$iw = new inwords;
							$contract_of_agency_array_from_to = [
								'{ORDER_ID}'         => $insert_id,
								'{DATE}'             => $today,
								'{CUSTOMER_NAME}'    => $customer_name,
								'{DRIVER_NAME}'      => $content_contract_of_agency_array['from_name'] ?: '__________________________________________________',
								'{TOTAL_VALUE}'      => round($order_total_value, 2),
								'{TOTAL_VALUE_TEXT}' => $iw->get($order_total_value),
							];
							$contract_of_agency = prepare_email_template($content_contract_of_agency_array['content_html'], $contract_of_agency_array_from_to);
						}
					}

                    // добавление в накладную информации об не полученных посылках
					$parcels_not_received = isset($order->info['real_id']) ? detect_parcels_not_received($order->info['real_id'], $customer_id, $order) : '';

                    // array to replace variables from html template:
					if (isset($shipping_date_raw)) {
						$date = new DateTime($shipping_date_raw);
					}
                    $array_from_to = array(
                        '{DELIVERY_COST_PAY_CUSTOMER}'     => $delivery_cost_pay_customer,
                        '{DUPLICATE}'                      => '',
                        '{INITIATOR_ID}'                   => '',
                        '{DELIVERY_SHORT}'                 => isset($shipping_date_raw) && tep_not_null($shipping_date_raw) && $is_kiev && !$is_kiev_np_delivery && !$is_kiev_np_address_delivery ? date('j', strtotime($shipping_date_raw)) . ' ' . strtr($date->format('w'), ['1' => 'ПН', '2' => 'ВТ', '3' => 'СР', '4' => 'ЧТ', '5' => 'ПТ', '6' => 'СБ', '0' => 'ВС']) . ' ' . $shipping_time_short_text : '',
                        '{DELIVERY_METHOD_CODE}'           => $delivery_method_code . ((!$is_kiev || $is_kiev_np_delivery) && $targeted_delivery != '' ? ' A' : ($is_selfdelivery ? ' ' . $shops_code_array[$order->delivery['selfdelivery_id'] ?? $order->delivery['state']] : '')),
                        '{PAYMENT_METHOD_CODE}'            => $payment_method_code,
                        '{ORDER_NUMBER}'                   => $insert_id,
                        '{DONT_CALL_BEFORE_SHIPPING}'      => $dont_call_before_shipping,
                        '{CALL_BEFORE_SHIPPING}'           => $call_before_shipping,
                        '{FAILED_TO_CONTACT}'              => $failed_to_contact,
                        '{CUSTOMER_PHONE}'                 => $order->customer['telephone'],
                        '{CUSTOMER_ADDITIONAL_PHONE}'      => (isset($order->customer['additional_phone']) && $order->customer['additional_phone'] != $order->customer['telephone'] ? $order->customer['additional_phone'] : ''),
                        '{CUSTOMER_DEBT}'                  => $customer_debt,
                        '{CUSTOMER_EMAIL}'                 => $order->customer['email_address'],
                        '{CUSTOMER_ID}'                    => $customer_id != 0 ? '(' . $customer_id . ')' : '',
                        '{CUSTOMER_NAME}'                  => $customer_name,
                        '{DATE_ORDER}'                     => $today,
                        '{TIME_ORDER}'                     => date('H:i:s'),
                        //'{DELIVERY_ADDRESS}'          => (!$is_kiev || $is_kiev_np_delivery ? (isset($order->delivery['country']['title']) ? $order->delivery['country']['title'] : $order->delivery['country']) . '<br>' : '') . ($is_kiev && !$is_selfdelivery ? $this->getAddressFormatted('sendto', true) : $street_adresses),
                        '{DELIVERY_ADDRESS}'               => (isset($order->delivery['country']['title']) ? $order->delivery['country']['title'] : $order->delivery['country']) . '<br>' . ($is_kiev && !$is_selfdelivery ? $this->getAddressFormatted('sendto', true) : $street_adresses),
                        '{DELIVERY_COST_INFO}'             => $delivery_cost_info,
                        '{DELIVERY_DATE}'                  => $shipping_date_text,
                        '{DELIVERY_METHOD}'                => ($is_selfdelivery || !$is_kiev || $is_kiev_np_delivery ? $targeted_delivery . $order->info['shipping_method'] . $ukraine_sending_due : ''),
                        '{DELIVERY_TIME}'                  => $shipping_time_text,
                        '{FIRST_ORDER_MARK}'               => $first_order_mark,
                        '{{first_order_css_mark}}'         => $first_order_css_mark,
                        '{{payment_method_code_css_mark}}' => $payment_method_code_css_mark,
                        '{{problem_client_css_mark}}' 	   => $problem_client_css_mark,
                        '{GIVE_DISCOUNT_CART}'             => $give_discount_cart,
                        '{GIVE_GIFT}'                      => $gift,
                        '{GLOBAL_DISCOUNT}'                => $global_discount,
                        '{LEAVE_ORDER_CONCIERGE}'          => $leave_order_concierge,
                        '{ORDER_COMMENTS}'                 => $comments,
                        '{ORDER_SKU}'                      => $order_sku,
                        '{ORDER_TOTAL_TEXT}'               => $order_total_text,
                        '{ORDER_TOTALS}'                   => $order_total,
                        '{PAYMENT_METHOD}'                 => $order->info['payment_method'],
                        '{PRODUCTS}'                       => prepare_products_list($order->products, true, false, true), //$products_ordered
                        '{SELF_DELIVERY_SHOP}'             => $self_delivery_shop,
                        '{SUBURBAN_DELIVERY}'              => $suburban_delivery,
                        '{QUARANTINE_DELIVERY_ALERT}'      => $quarantine_delivery_alert,
                        '{SELF_DELIVERY_RESERVE}'          => $self_delivery_reserve,
                        '{ORDER_HISTORY}'                  => '',
                        '{CONTRACT_OF_AGENCY}'             => $contract_of_agency . $contract_of_agency,
                        '{PARCELS_NOT_RECEIVED}'           => $parcels_not_received,
                    );

                    $email_order = prepare_email_template($content_email_array['content_html'], $array_from_to);

					// send internal email about order
                    $extra_order_emails = preg_replace('/(.*)<(.*)>(.*)/sm', '\2', SEND_EXTRA_ORDER_EMAILS_TO);

                    tep_mail_html(STORE_NAME . ' ' . STORE_OWNER, $extra_order_emails, '# ' . $insert_id, $email_order, '(' . $customer_id . ') ' . $customer_name, $order->customer['email_address'], $insert_id . '.png', ['pixel_black.gif']);

                    // удаление изображения штрих-кода номера заказа
					if (file_exists(DIR_FS_CATALOG . 'temp/barcode/' . $insert_id . '.png')) {
						unlink(DIR_FS_CATALOG . 'temp/barcode/' . $insert_id . '.png');
					}
                } else {
                    $caller_info = get_caller_info();
                    tep_mail_html('', 'debug@zootovary.ua', 'Not found template!', 'Not found template! ' . "\n\t" . $caller_info, 'DEBUG ZOOTOVARY.UA', 'debug@zootovary.ua');
                }
            }

			if (CHECKOUT_EMAIL_DEBUG == 'true') {
				if ($sendInternalEmail) {
					echo $email_order;
					echo 'email sended to ' . SEND_EXTRA_ORDER_EMAILS_TO;
				} else {
					echo 'email sending is DISABLED on checkout page or $direction not both|internal or SEND_EXTRA_ORDER_EMAILS_TO == \'\'';
				}
				var_dump($order);
			}
            // отправка письма с накладной end
        }

        function tep_send_sms($recipients, $content, $order_id, $recordDB = true, string $type = TURBOSMS_MESSAGE_TYPE) {
            global $turbosms;

            $fake_id = $order_id;
			$sms_id = null;

            if (tep_not_null($recipients) && is_object($turbosms)) {
                $text = htmlspecialchars_decode(strip_tags($content));
				$responce = $turbosms->sendMessages([$recipients], $text, $type);

				if (tep_not_null($responce) && $responce['success'] && isset($responce['result'][0]['message_id']) && tep_not_null($responce['result'][0]['message_id'])) {
					$sms_id = $responce['result'][0]['message_id'];
				}

                if ($recordDB && tep_not_null($sms_id)) {
                    $order_id = tep_get_real_order_id($fake_id);
                    tep_db_query("update " . TABLE_ORDERS . " set smsid = '" . $sms_id . "' where orders_id = '" . (int)$order_id . "'");
                }

				return $responce['success'];
            } else {
                return false;
            }
        }

        function generate_email_by_order_id($order_id) {
            global $order, $customer_name, $order_totals, $currencies, $products_ordered;

            $check_query = tep_db_query('select * from ' . TABLE_ORDERS . ' where orders_id = "' . $order_id . '"');
            $check = tep_db_fetch_array($check_query);

            // order products:
            $products_ordered = '';
            $order_products_query = tep_db_query("select op.products_id, op.products_model as model, op.products_name as name, op.products_quantity as qty, op.final_price as final_price, op.products_tax as tax, opa.products_options as at_name, opa.orders_products_attributes_id from " . TABLE_ORDERS_PRODUCTS . " op left join " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . " opa on op.orders_id = opa.orders_id where op.orders_id = '" . $order_id . "'");
            while ($order_products = tep_db_fetch_array($order_products_query)) {

                // attributes:
                $products_ordered_attributes = '';
                if (isset($order_products['orders_products_attributes_id'])) {
                    $order_products_attr_query = tep_db_query("select opa.products_options as at_name, opa.products_options_values as at_value from " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . " opa where opa.orders_products_attributes_id = '" . $order_products['orders_products_attributes_id'] . "'");
                    while ($order_products_attr = tep_db_fetch_array($order_products_attr_query)) {
                        $products_ordered_attributes .= "<br /><small>" . $order_products_attr['at_name'] . ': ' . tep_decode_specialchars($order_products_attr['at_value']) . '</small>';
                    }
                }

                //products:
                $products_ordered .= $order_products['qty'] . ' x ' . $order_products['name'] . ' (' . $order_products['model'] . ') = ' . $currencies->display_price($order_products['final_price'], $order_products['qty']) . $products_ordered_attributes . "<br>";

            }

            // order info:
			ClassLoader::load('order', DIR_WS_CLASSES . 'order.php');

            $order = new order($order_id);
            $comment = $order->info['comments']['comments'];
            $shipping_time = $order->info['comments']['shipping_time'];
            $shipping_date = $order->info['comments']['shipping_date'];
            $concierge = $order->info['comments']['concierge'];
            $call_before_shipping = $order->info['comments']['call_before_shipping'];

            $order->delivery = [
                'firstname'      => $check['delivery_name'],
                'lastname'       => '',
                'company'        => $check['delivery_company'],
                'street_address' => $check['delivery_street_address'],
                'suburb'         => $check['delivery_suburb'],
                'city'           => $check['delivery_city'],
                'postcode'       => $check['delivery_postcode'],
                'state'          => $check['delivery_state'],
                'zone_id'        => '',
                'country'        => $check['delivery_country'],
                'country_id'     => $order->delivery['country_id'],
                'format_id'      => $check['delivery_address_format_id'],
            ];

            $order->billing = [
                'firstname'      => $check['billing_name'],
                'lastname'       => '',
                'company'        => $check['billing_company'],
                'street_address' => $check['billing_street_address'],
                'suburb'         => $check['billing_suburb'],
                'city'           => $check['billing_city'],
                'postcode'       => $check['billing_postcode'],
                'state'          => $check['billing_state'],
                'zone_id'        => '',
                'country'        => $check['billing_country'],
                'country_id'     => $order->delivery['country_id'],
                'format_id'      => $check['billing_address_format_id'],
            ];

            $order->customer = [
                'email_address' => $check['customers_email_address'],
                'telephone'     => $check['customers_telephone'],
                'comments'      => $comment,
                'shipping_date' => $shipping_date,
                'shipping_time' => $shipping_time,
            ];

			$order->info = [
				'currency'             => $order->info['currency'],
				'shipping_method'      => $order->info['shipping_method'],
				'shipping_method_code' => $order->info['shipping_module_code'],
				'lang_id'              => $check['lang_id'],
				'real_id'              => $check['orders_id'],
				'comments'             => $comment,
				'call_before_shipping' => $call_before_shipping,
				'concierge'            => $concierge,
				'shipping_date'        => $shipping_date,
				'shipping_time'        => $shipping_time,
				'payment_method'       => $check['payment_method'],
				'payment_method_code'  => $check['payment_method_code'],
				'shipping_module'      => $check['shipping_module'],
			];

            $customer_name = $check['customers_name'];

            // get $order_totals:
            $totals_query = tep_db_query("select * from " . TABLE_ORDERS_TOTAL . " where orders_id = '" . (int)$order_id . "' order by sort_order");
            while ($totals = tep_db_fetch_array($totals_query)) {
                $order_totals[] = [
                    'title'           => $totals['title'],
                    'text'            => $totals['text'],
                    'class'           => $totals['class'],
                    'value'           => $totals['value'],
                    'orders_total_id' => $totals['orders_total_id'],
                ];
            }
            $order->info['total'] = strip_tags(end($order_totals)['text']);
            $order->info['total_value'] = strip_tags(end($order_totals)['value']);

        }

        function addProductsToViewed() {
            global $order, $customer_id;
            if (!$customer_id)
                return false;
            $order_products = array_reduce($order->products, function ($count, $product) use ($customer_id) {
                $count[] = "(" . (int)$product['id'] . ",$customer_id,NOW())";
                return $count;
            }, []);
            tep_db_query("INSERT INTO `customers_recently_viewed` (`products_id`,`customers_id`,`date_added`)
                          VALUES " . implode(',', $order_products) . "
                            ON DUPLICATE KEY UPDATE `date_added`=NOW()");
        }

        function updateCustomerDefaultAddress($customer_id, $address_id) {
            global $order;
            if (tep_not_null($customer_id) && $customer_id != 0) {
                $sql = tep_db_query("SELECT customers_default_address_id FROM customers WHERE customers_id = " . (int)$customer_id);
                $id = tep_db_fetch_array($sql)['customers_default_address_id'];
                if (!$id) {
                    $phone = format_phone($order->customer['telephone']);
                    $fax = format_phone($order->customer['additional_phone']);
                    $fax = (tep_not_null($fax) ? tep_db_prepare_input($fax) : 'null');

                    $sql_data_array = array('customers_firstname'          => tep_db_prepare_input($order->customer['firstname']),
                                            'customers_lastname'           => tep_db_prepare_input($order->customer['lastname']),
                                            'customers_telephone'          => tep_db_prepare_input($phone),
                                            'customers_fax'                => $fax,
                                            'customers_default_address_id' => (int)$address_id,
                    );

                    tep_db_perform(TABLE_CUSTOMERS, $sql_data_array, 'update', "customers_id = '" . (int)$customer_id . "'");

                    $_SESSION['customer_first_name'] = $order->customer['firstname'];
                    $_SESSION['customer_last_name'] = $order->customer['lastname'];
                }
            }
        }

		// todo: delete after check usage in other places
        function getBulkData($pid) {
            $query = tep_db_query("select products_bulk,
                                          products_bulk_quantity,
                                          products_bulk_discount
                                   from products
                                   where products_id = '" . (int)$pid . "'
                                   limit 1");
            return tep_db_fetch_array($query);
        }

		// todo: delete after check usage in other places
        function getAttributeData($pid, $paid) {
            $query = tep_db_query("select products_attributes_id as id,
                                          pa_qty as qty,
                                          option_check_stock as stock,
                                          options_values_weight as weight,
                                          options_id as oid,
                                          legal_entities_id as entities_id
                                   from " . TABLE_PRODUCTS_ATTRIBUTES . " as pa
                                   where pa.products_attributes_id ='" . (int)$paid . "' and
                                         pa.products_id = '" . (int)$pid . "'
                                   limit 1");
            return tep_db_fetch_array($query);
        }

		/**
		 * @param $products массив товаров
		 * @param $customers_order_id пользовательский номер заказа
		 * @param $order_id номер заказа в БД
		 * FORCE_DISABLE_BULK_ATTRIBUTE используется при определении есть ли у поштучного аттрибута аттрибут упаковки для выключении аттрибута упаковки если поштучный закончился
		 */

		// todo: delete
        public function disableProductsByStock($products, $customers_order_id, $order_id) {
            foreach ($products as $product) {
                $pid = tep_get_prid($product['id']);
				$ordered_quantity = $product['quantity'];
				$disable_check_stock = DISABLE_CHECK_STOCK_IF_NEGATIVE_BALANCES == 'true';

                if (empty($product['attributes'])) { // если нет аттрибутов

					write_log_front($pid, 'изменение остатка', 'checkout::auto', 0, 0, 1111, 0, $customers_order_id, $order_id, $ordered_quantity );

                    $stock_query = tep_db_fetch_array(
                            tep_db_query("
                                SELECT `products_quantity` AS `qty`,
                                       `products_check_stock` AS `stock`
                                FROM " . TABLE_PRODUCTS . "
                                WHERE `products_id` = '" . (int)$pid . "'"),
                    );

                    if ($stock_query['stock']) { //если включена проверка наличия товара
                        if ($stock_query['qty'] < 1) { // если количество после покупки <1 то отключаем
                            // выключение слежения за количеством
                            if ($disable_check_stock) {
								tep_set_product_check_stock($pid, 0);
							}
                            // выключение товара
                            tep_set_product_stock($pid, 0);
                            // запись о выключении товара в лог
                            $current_quantity = array_key_exists($pid, $this->stockValue) && !is_array($this->stockValue[$pid]) ? $this->stockValue[$pid] : 0;
                            $log_text = 'Автоматическое выключение товара';
                            write_log_front($pid, $log_text, 'checkout::auto', 0, 0, 1, 0, $customers_order_id, $order_id, $ordered_quantity, $current_quantity);

                            if (tep_not_null($product['date_expiration'])) {
                                // обнуление поля срока годности для товара
                                $this->clearDateExpiration($pid);
                                // запись о сбросе срока годности
                                write_log_front($pid, 'Сброс срока годности - ' . $product['name'], 'checkout::auto', 0);
                            }
                        }
                    }
                } else { // если есть аттрибуты
                    $_aid = (int)$product['attributes'][0]['products_attributes_id']; // id аттрибута в заказе
                    $_entytiId = (int)$product['attributes'][0]['legal_entities_id']; // продавец аттрибута в заказе
                    $stock_query = $this->getAttributeData($pid, $_aid);
                    $_isBulkAttribute = false;

					$bulk = $this->getBulkData($pid);

					write_log_front($pid, 'изменение остатка', 'checkout::auto', 0, $_aid, 1111, 0, $customers_order_id, $order_id, $ordered_quantity );

					if (!empty($bulk) && $bulk['products_bulk'] !== null && $bulk['products_bulk_quantity'] !== null) {
						$bulk['products_bulk'] = unserialize($bulk['products_bulk']);
						$bulk['products_bulk_quantity'] = unserialize($bulk['products_bulk_quantity']);

						if ($stock_query['oid'] == 23) { // если это упаковка, узнаем aid поштучного атрибута
							$_aidBulk = array_search($_aid, $bulk['products_bulk']);
							$_bulkAttributeData = $this->getAttributeData($pid, $_aidBulk);
							$ordered_quantity = $ordered_quantity * $bulk['products_bulk_quantity'][$_aidBulk];
							// если продавец не ФОП Лысенко или продавец и упаковки и поштучного товара ФОП Лысенко
							if ($_entytiId != 5 ||
								FORCE_DISABLE_BULK_ATTRIBUTE == 'true' ||
                                ($_bulkAttributeData['entities_id'] == 5 && $_entytiId == $_bulkAttributeData['entities_id'])
                            ) {
								$_isBulkAttribute = true;
								$stock_query['stock'] = $_bulkAttributeData['stock'];
								$stock_query['qty'] = $_bulkAttributeData['qty'];
								$stock_query['id'] = $_bulkAttributeData['id'];
							}
						} else {
							if (array_key_exists($_aid, $bulk['products_bulk'])) {
								$_aidBulk = $bulk['products_bulk'][$_aid];
								$_bulkAttributeData = $this->getAttributeData($pid, $_aidBulk); // берем данные аттрибута упаковки
								if ($_entytiId != 5 ||
									FORCE_DISABLE_BULK_ATTRIBUTE == 'true' ||
                                    ($_bulkAttributeData['entities_id'] == 5 && $_entytiId == $_bulkAttributeData['entities_id'])
                                ) {
									$_aid = $_aidBulk; // заменяем id аттрибута в заказе на id аттрибута упаковки
									$_isBulkAttribute = true;
								}
							}
						}
					}

                    if ($stock_query['stock']) {  //если включена проверка наличия товара
                        if ($stock_query['qty'] < 1) { // если количество после покупки <1 - то отключаем
                            // выключение слежения за количеством
							if ($disable_check_stock) {
								tep_set_attribute_stock($stock_query['id'], 0);
							}
                            // выключение атрибута
                            tep_set_attribute_status($stock_query['id'], 0);

                            // запись о выключении атрибута в лог
							$current_quantity = array_key_exists($pid, $this->stockValue) && is_array($this->stockValue[$pid]) && array_key_exists($stock_query['id'], $this->stockValue[$pid]) ? $this->stockValue[$pid][$stock_query['id']] : 0;
							$log_text = 'Автоматическое выключение аттрибута - ' . $product['attributes'][0]['option'] . ' - ' . $product['attributes'][0]['value'];
                            write_log_front($pid, $log_text, 'checkout::auto', 0, $stock_query['id'], 1, 0, $customers_order_id, $order_id, $ordered_quantity, $current_quantity);

                            // тоже самое для аттрибута упаковки
                            if ($_isBulkAttribute) {
                                tep_set_attribute_stock($_aid, 0);
                                tep_set_attribute_status($_aid, 0);
								$log_text = 'Автоматическое выключение аттрибута - ' . $product['attributes'][0]['option'] . ' - упаковка';
                                write_log_front($pid, $log_text, 'checkout::auto', 0, $_aid, 1, 0, $customers_order_id, $order_id, $ordered_quantity, $current_quantity);
                            }

							// выключение товара если у товара нет ни одного аттрибута в наличии
							if (!checkProductAttributesStatus($pid)) {
								tep_set_product_stock($pid, 0);
								// запись о выключении товара в лог
								$log_text = 'Автоматическое выключение товара';
								write_log_front($pid, $log_text, 'checkout::auto', 0, 0, 1, 0, $customers_order_id, $order_id, $ordered_quantity);
							}

                            if (tep_not_null($product['date_expiration'])) {
                                // обнуление поля срока годности для товара
                                $this->clearDateExpiration($pid);
                                // запись о сбросе срока годности товара
                                write_log_front($pid, 'Сброс срока годности товара', 'checkout::auto', 0);
                            }
                            if (tep_not_null($product['attributes'][0]['date_expiration'])) {
                                // обнуление поля срока годности для аттрибута
                                $this->clearAttributesDateExpiration($_aid);
                                // запись о сбросе срока годности аттрибута
                                write_log_front($pid, 'Сброс срока годности аттрибута - ' . $product['attributes'][0]['option'] . ' - ' . $product['attributes'][0]['value'], 'checkout::auto', 0, $_aid);
                            }
                            // отправка письма о выключении
                            $reportEmails = ['shop@zootovary.ua'];
                            foreach ($reportEmails as $email) {
                                tep_mail_html('', $email, 'Аттрибут выключен - ' . $product['name'], 'Аттрибут выключен - ' . $product['name'] . ' ' . $product['attributes'][0]['option'] . ' - ' . $product['attributes'][0]['value'], 'ZOOTOVARY.UA', 'shop@zootovary.ua');
                            }
                        }
                    }
                }
            }
        }

		// todo: delete
        public function reduceProductStock(&$product) {
			if (TRACK_STOCK_BALANCES_BY_SELLER == 'true') {
				return;
			}
			$pid = tep_get_prid($product['id']);

			$stock_query_params = [
				'products_id' => (int)$pid,
			];
			$stock_query = tep_db_perform(TABLE_PRODUCTS, ['products_quantity'], 'select', $stock_query_params);

            if (tep_db_num_rows($stock_query) > 0 && $product['check_stock'] == 1) {
                $stock_values = tep_db_fetch_array($stock_query);
				$this->stockValue[$pid] = $stock_values['products_quantity'];
				$stock_left = $stock_values['products_quantity'] - $product['qty'];

				if ($stock_left < 0) {
					$product['stock_warning'] = true;
				}

                $stock_left = (ALLOW_STOCK_NEGATIVE_BALANCES == 'true' ? $stock_left : max($stock_left, 0));

				$fields_params = [
					'products_quantity' => $stock_left,
				];
				$where_params = [
					'products_id' => (int)$pid,
				];

				tep_db_perform(TABLE_PRODUCTS, $fields_params, 'update', $where_params);
            }
        }

		// todo: delete
        public function reduceAttributeStock(&$product, $attributes) {
			if (TRACK_STOCK_BALANCES_BY_SELLER == 'true') {
				return;
			}

            $pid = tep_get_prid($product['id']);
            $_qtyTemp = (int)$product['qty'];
            $_aidTemp = (int)$attributes['products_attributes_id'];
            $_entytiId = (int)$product['attributes'][0]['legal_entities_id'];

			// если это упаковка, узнаем aid поштучного атрибута
            if ((isset($attributes['option_id']) && $attributes['option_id'] == 23) || (isset($attributes['options_id']) && $attributes['options_id'] == 23)) {
                $bulk = $this->getBulkData($pid);
                $bulk['products_bulk'] = unserialize($bulk['products_bulk']);
                $bulk['products_bulk_quantity'] = unserialize($bulk['products_bulk_quantity']);
                $_aid = array_search($_aidTemp, $bulk['products_bulk']);
                $_bulkAttributeData = $this->getAttributeData($pid, $_aid);
                // если продавец не ФОП Лысенко или продавец и упаковки и поштучного товара ФОП Лысенко
                if ($_entytiId != 5 || ($_bulkAttributeData['entities_id'] == 5 && $_entytiId == $_bulkAttributeData['entities_id'])) {
                    $_qtyTemp = (int)$bulk['products_bulk_quantity'][$_aid] * $_qtyTemp;
                    $_aidTemp = (int)$_aid;
                }
            }

			$stock_query_params = [
				'products_id'            => (int)$pid,
				'products_attributes_id' => $_aidTemp,
			];
			$stock_query = tep_db_perform(TABLE_PRODUCTS_ATTRIBUTES, ['pa_qty', 'option_check_stock'], 'select', $stock_query_params);

			if (tep_db_num_rows($stock_query) > 0) {
				$stock_values = tep_db_fetch_array($stock_query);
				$this->stockValue[$pid][$_aidTemp] = $stock_values['pa_qty'];
				$stock_left = $stock_values['pa_qty'] - $_qtyTemp;

				if ($stock_values['option_check_stock'] == 1 && $stock_left < 0) {
					$product['stock_warning'] = true;
				}

				$stock_left = (ALLOW_STOCK_NEGATIVE_BALANCES == 'true' ? $stock_left : max($stock_left, 0));

				$fields_params = [
                    'pa_qty' => $stock_left,
                ];
				$where_params = [
					'products_id' => (int)$pid,
					'products_attributes_id' => $_aidTemp,
				];

				tep_db_perform(TABLE_PRODUCTS_ATTRIBUTES, $fields_params, 'update', $where_params);
			}
        }

		// todo: delete
		public function clearDateExpiration($pid) {
			$update_sql_data = array('products_date_expiration' => 'null');
			tep_db_perform(TABLE_PRODUCTS, $update_sql_data, 'update', "products_id = '" . (int)$pid . "'");
		}

		// todo: delete
		public function clearAttributesDateExpiration($aid) {
			$update_sql_data = array('option_expiration' => 'null',
									 'option_expiration_date' => 'null',
			);
			tep_db_perform(TABLE_PRODUCTS_ATTRIBUTES, $update_sql_data, 'update', "products_attributes_id = '" . (int)$aid . "'");
		}

        public function checkFreeShipForKiev() {
            global $order_total_modules, $ot_total, $shipping;
            $status = false;
            if (isset($shipping['id']) && $shipping['id'] === 'flat_flat') {
                if (empty($ot_total->output)) {
                    $order_total_modules->process();
                }
                $total = end($ot_total->output);
                if ((int)$total['value'] < (int)ORDER_COST_FOR_FREE_SHIPPING) {
                    $status = true;
                }
            }
            return $status;
        }

        public function checkFreeShipForNP() {
            global $order_total_modules, $ot_shipping, $shipping;
            $status = true;
            if (isset($shipping['id']) && $shipping['id'] === 'novapochta_novapochta') {
                if (empty($ot_shipping)) {
                    $order_total_modules->process();
                }
                $shipping_cost = end($ot_shipping->output);
                if (isset($shipping_cost['value']) && (int)$shipping_cost['value'] != 0) {
                    $status = false;
                }
            }
            return $status;
        }

        public function checkMinOrderForKiev() {
            global $order_total_modules, $ot_total, $shipping;
            $status = false;
            if (isset($shipping['id']) && $shipping['id'] === 'flat_flat') {
                if (empty($ot_total->output)) {
                    $order_total_modules->process();
                }
                $total = end($ot_total->output);
                if ((int)$total['value'] < (int)MINIMUM_ORDER_COST) {
                    $status = true;
                }
            }
            return $status;
        }

        public function getOrderWeight() {
            global $cart;

            return $cart->show_weight();
        }

        public function getMaxWidth() {
            global $cart;

            return $cart->get_max_width();
        }

		public function addTaskSendOrderToGA($fake_id, $order, $source) {
			global $cat_names_multilanguage, $clientIdIsFound, $sessionIdIsFound;

			if (USE_GOOGLE_TAGS == 'false') {
				return false;
			}

			if (MEASUREMENT_PROTOCOL_SEND_METHOD != 'queue') {
				$report = "Google Measurement Protocol report:\n";
				$report .= "Send method is not queue";
				sendTelegramReport($report);
				exit;
			}

			if (empty($cat_names_multilanguage)) {
				$cat_names_multilanguage = [];
				cat_names_multilang_tree();
			}

			if (class_exists('RedisQueue')) {
				try {
					$payment_type = $order->info['payment_method_code'] ?? '';
					$delivery_type = $order->info['shipping_method_code'] ?? $order->info['shipping_module_code'];
					$value = (float)tep_round($order->info['total_value'] ?? $order->info['total'], 2);
					$client_id = gaParseCookie();
					$session_id = gaParseSessionCookie();
					$adblockDetected = !$clientIdIsFound && !$sessionIdIsFound;

					$orderQueue = new RedisQueue('ga_events');
					$data['fid'] = $fake_id;
					$data['source'] = $source;

					if (isAcquiring($payment_type)) {
						$data['time'] = date('Y-m-d H:i:s', strtotime('+30 minutes'));
					} else {
						$data['time'] = date('Y-m-d H:i:s');
					}

					$json = [
						"client_id"            => $client_id,
						"non_personalized_ads" => false,
						"events"               => [
							[
								"name"   => "purchase",
								"params" => [
									"engagement_time_msec" => 1200,
									"session_id"           => $session_id,
									"currency"             => "UAH",
									"transaction_id"       => $fake_id,
									"payment_type"         => $payment_type,
									"delivery_type"        => $delivery_type,
									"value"                => $value,
									"items"                => processOrderProducts($order->products, $cat_names_multilanguage[3]),
								],
							],
						],
					];

					if ($adblockDetected) {
						$json['user_properties'] = [
							"sessionSource" => ["value" => "adblock"],
							"sessionMedium" => ["value" => "script"],
						];
					}

					$data['json'] = json_encode($json);

					if ($index = $orderQueue->search($data['fid'])) {
						$orderQueue->update($index, $data);
					} else {
						$orderQueue->add($data);
					}
				} catch (RedisQueueException $e) {
					tep_mail_html('', 'debug@zootovary.ua', 'RedisQueueException!', 'RedisQueueException! ' . "\n\t" . $e, 'DEBUG ZOOTOVARY.UA', 'debug@zootovary.ua');
				}
			} else {
				tep_mail_html('', 'debug@zootovary.ua', 'RedisQueue !class_exists', 'RedisQueue !class_exists', 'DEBUG ZOOTOVARY.UA', 'debug@zootovary.ua');
			}
		}

		public function addTaskSendOrderToEsputnik($fake_order_id, $source) {
			if (class_exists('RedisQueue')) {
				try {
					$orderQueue = new RedisQueue('esputnikData');
					$data = [
						'fid'    => $fake_order_id,
						'source' => $source,
					];
					$orderQueue->add($data);
				} catch (RedisQueueException $e) {
					tep_mail_html('', 'debug@zootovary.ua', 'RedisQueueException!', 'RedisQueueException! ' . "\n\t" . $e, 'DEBUG ZOOTOVARY.UA', 'debug@zootovary.ua');
				}
			} else {
				tep_mail_html('', 'debug@zootovary.ua', 'RedisQueue !class_exists', 'RedisQueue !class_exists', 'DEBUG ZOOTOVARY.UA', 'debug@zootovary.ua');
			}
		}

		public function addTaskSendSms($fake_order_id, $source) {
			if (class_exists('RedisQueue')) {
				try {
					$orderQueue = new RedisQueue('sms');
					$data = [
						'fid'    => $fake_order_id,
						'source' => $source,
					];
					$orderQueue->add($data);
				} catch (RedisQueueException $e) {
					tep_mail_html('', 'debug@zootovary.ua', 'RedisQueueException!', 'RedisQueueException! ' . "\n\t" . $e, 'DEBUG ZOOTOVARY.UA', 'debug@zootovary.ua');
				}
			} else {
				tep_mail_html('', 'debug@zootovary.ua', 'RedisQueue !class_exists', 'RedisQueue !class_exists', 'DEBUG ZOOTOVARY.UA', 'debug@zootovary.ua');
			}
		}

		public function addTaskExportToOneC($products, $order_id, $fake_id) {
			if (defined('ONE_C_ADD_TO_ORDERS_CUSTOMERS') && ONE_C_ADD_TO_ORDERS_CUSTOMERS == 'true' && isAllowExportToOneC($products)) {
				if (isFullExport($products) || (defined('ONE_C_ALLOW_PARTIAL_EXPORT') && ONE_C_ALLOW_PARTIAL_EXPORT == 'true')) {
					createRecordOneCExportTable($order_id, 1);
					if (class_exists('RedisQueue')) {
						try {
							$orderQueue = new RedisQueue('order');
							$data = [
								'oid'    => $order_id,
								'fid'    => $fake_id,
								'source' => 'onepage_checkout',
							];
							$orderQueue->add($data);
						} catch (RedisQueueException $e) {
							tep_mail_html('', 'debug@zootovary.ua', 'RedisQueueException!', 'RedisQueueException! ' . "\n\t" . $e, 'DEBUG ZOOTOVARY.UA', 'debug@zootovary.ua');
						}
					} else {
						tep_mail_html('', 'debug@zootovary.ua', 'RedisQueue !class_exists', 'RedisQueue !class_exists', 'DEBUG ZOOTOVARY.UA', 'debug@zootovary.ua');
					}
				}
			}
		}

        public function formCheck(&$data) {
            global $order, $rootPath, $csrf_token, $customer_id;

            $error = false;
            $log = '';

            if (!check_csrf_token()) {
                $log .= 'csrf_token error' . PHP_EOL;
                $log .= 'current token: ' . $csrf_token . PHP_EOL;
                $log .= 'form token: ' . $data['csrf-token'] . PHP_EOL;
                $error = true;
            }

            if (!$customer_id) {
                if (empty($data['billing_firstname']) || empty($data['billing_lastname'])) {
                    $log .= 'firstname/lastname error' . PHP_EOL;
                    $log .= 'form firstname: ' . $data['billing_firstname'] . PHP_EOL;
                    $log .= 'form lastname: ' . $data['billing_lastname'] . PHP_EOL;
                    $error = true;
                }
                if ($this->checkEmailAddress($data['billing_email_address'], false) != 'true') {
                    $log .= 'incorrect email address:  ' . $data['billing_email_address'] . PHP_EOL;
                    $error = true;
                }
                if (!phone_number_check($data['billing_telephone'])) {
                    $log .= 'incorrect phone number:  ' . $data['billing_telephone'] . PHP_EOL;
                    $error = true;
                }
            }

            if (empty($order->info['payment_method'])) {
                $log .= 'empty payment_method :  ' . $order->info['payment_method'] . PHP_EOL;
                $error = true;
            }

            if (empty($order->info['shipping_method_code'])) {
                $log .= 'empty shipping_method_code :  ' . $order->info['shipping_method_code'] . PHP_EOL;
                $error = true;

            }

            if ($error) {
                // по хорошему редиректить с каким-то параметром (ну или писать в сессию метку) и выводить сообщение что произошла ошибка, введите данные
                $log .= 'ip: ' . $_SERVER['REMOTE_ADDR'] . PHP_EOL;
                $log .= 'useragent: ' . $_SERVER['HTTP_USER_AGENT'] . PHP_EOL;
                $log .= 'datetime: ' . date('c') . PHP_EOL;
                $log .= '$customer_id: ' . $customer_id . PHP_EOL;
                $log .= '$email: ' . $order->customer['email_address'] . PHP_EOL;
                $log .= '-------------- $_SESSION -------------- ' . PHP_EOL;
                $log .= '$_SESSION[onepage]: ' . PHP_EOL . var_export($_SESSION['onepage'], true) . PHP_EOL;
                $log .= '$_SESSION[shipping]: ' . PHP_EOL . var_export($_SESSION['shipping'], true) . PHP_EOL;
                $log .= '$_SESSION[payment]: ' . PHP_EOL . var_export($_SESSION['payment'], true) . PHP_EOL;
                $log .= '-------------- $order -------------- ' . PHP_EOL;
                $log .= '$order->info: ' . PHP_EOL . var_export($order->info, true) . PHP_EOL;
                $log .= '$order->customer: ' . PHP_EOL . var_export($order->customer, true) . PHP_EOL;
                $log .= '$order->delivery: ' . PHP_EOL . var_export($order->delivery, true) . PHP_EOL;
                $log .= '$order->billing: ' . PHP_EOL . var_export($order->billing, true) . PHP_EOL;
                $log .= '==========================' . PHP_EOL;

                file_put_contents($rootPath . DIRECTORY_SEPARATOR . '_checkout_error.log', $log, FILE_APPEND);

                tep_redirect(tep_href_link(FILENAME_CHECKOUT));
                die;
            }

            return $error;
        }

		function redeemCoupon(): string {
			global $coupon, $valid_coupons;

			if (!is_string($coupon)) {
				return '{"result": "error"}';
			}

			if ($coupon === '0') {
				tep_session_unregister('valid_coupons');
				unset($valid_coupons);
				return '{"result": "total unregistered"}';
			} else if (isset($_POST['subaction']) && $_POST['subaction'] == 'remove') {
				$index = array_search($coupon, $valid_coupons);
				if ($index !== false) {
					unset($valid_coupons[$index]);
					$valid_coupons = array_values($valid_coupons);
					tep_session_register('valid_coupons');
					return '{"result": "coupon removed"}';
				} else {
					return '{"result": "coupon not found"}';
				}
			} else {
				if (!empty($coupon)) {
					if (!in_array($coupon, $valid_coupons)) {
						$coupon = removeEmoji($coupon);
						$coupon = preg_replace("/[^a-zA-Z0-9-]/", "", $coupon);
						$valid_coupons[] = mb_strtoupper($coupon);
						tep_session_register('valid_coupons');
						return '{"result": "coupon registered"}';
					} else {
						return '{"result": "coupon duplicate found"}';
					}
				} else {
					return '{"result": "coupon empty"}';
				}
			}
		}
        
        function getAddressFromAddressBook($aid) {
            $sql = "select entry_street_address, entry_house, entry_flat, entry_floor, entry_entrance
                    from address_book
                    where address_book_id = '" . (int)$aid . "'";
            $address_book_query = tep_db_query($sql);
            
            return tep_db_fetch_array($address_book_query);
        }

		function formatStreetAddresses() {
			global $order;
            
            if (empty($order->delivery['street_address'])) {
                
                $address_id = $order->info['address_id'] ?? null;
                if (!empty($address_id)) {
                    $address = $this->getAddressFromAddressBook($address_id);
                    
                    if ( !empty($address)) {
                        $order->delivery['street_address'] = $address['entry_street_address'];
                        $order->delivery['house']          = $address['entry_house'] ?? '';
                        $order->delivery['entrance']       = $address['entry_entrance'] ?? '';
                        $order->delivery['floor']          = $address['entry_floor'] ?? '';
                        $order->delivery['flat']           = $address['entry_flat'] ?? '';
                    }
                }
            }
            
			$result = $order->delivery['street_address'];

			if (!empty($order->delivery['house'])) {
				$result = $order->delivery['street_address']
					. ($order->delivery['house'] ? ', ' . TEXT_HOUSE . ' ' . $order->delivery['house'] : '')
					. ($order->delivery['entrance'] ? ', ' . TEXT_ENTRANCE . ' ' . $order->delivery['entrance'] : '')
					. ($order->delivery['floor'] ? ', ' . TEXT_FLOOR . ' ' . $order->delivery['floor'] : '')
					. ($order->delivery['flat'] ? ', ' . TEXT_FLAT . ' ' . $order->delivery['flat'] : '');
			}

			return $result;
		}

		/**
		Set UTM parameters from cookies to SQL data array.
		@param array $sql_data_array Reference to the SQL data array where the UTM parameters will be added.
		@return void
		 */
		function setUtms(&$sql_data_array) {
			$utm_params = [
				'utm_source',
				'utm_medium',
				'utm_campaign',
			];

			foreach ($utm_params as $param) {
				if (!empty($_COOKIE[$param])) {
					$sql_data_array[$param] = tep_db_input(htmlspecialchars($_COOKIE[$param], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
				}
			}
		}

		function getShippingFieldValue($fieldKey) {
			global $onepage, $shipping_modules;

			$shipping_module = $onepage['delivery']['shipping_module'] ?? '';

			if (empty($shipping_module)) {
				$shipping_method = $onepage['info']['shipping_method']['id'] ?? '';
				if (!empty($shipping_method)) {
					$parts = explode('_', $shipping_method);
					$shipping_module = $parts[0] ?? '';
				}
			}

			if (empty($shipping_module)) {
				return json_encode([]);
			}

			$quotes = $shipping_modules->quote();
			foreach ($quotes as $quote) {
				if (!isset($quote['id']) || $quote['id'] !== $shipping_module) {
					continue;
				}

				$field = $quote['field'][0] ?? [];

				if ($fieldKey === 'poshtomats' && !empty($field[$fieldKey][0]['value'])) {
					return json_encode($field[$fieldKey][0]['value']);
				}

				if (!empty($field[$fieldKey])) {
					return json_encode($field[$fieldKey]);
				}

				break;
			}

			return json_encode([]);
		}

		function getWarehouses() {
			return $this->getShippingFieldValue('value');
		}

		function getPoshtomates() {
			return $this->getShippingFieldValue('poshtomats');
		}
        
        /**
         * Быстрая проверка и вызов логирования, если есть пустые поля.
         * Возвращает true, если были пустые поля и создан лог.
         */
        private function dumpOrderErrorIfMissing($order, $fakeOrderId, array $fields, $customerId = null) : bool {
            // Пустым считаем: null, '', [], но НЕ '0' и НЕ 0
            $isEmpty = static function($v) {
                return $v === null || $v === '' || (is_array($v) && $v === []);
            };
            
            $missing = array_keys(array_filter($fields, $isEmpty));
            
            if ( !$missing) {
                return false; // ничего не делаем
            }
            
            $this->dumpOrderError($order, $fakeOrderId, $fields, $missing, $customerId);
            
            return true;
        }
        
        /**
         * Пишет один файл на заказ и ДОЗАПИСЫВАЕТ новый блок события.
         * ПД не маскируем (по вашему требованию).
         */
        private function dumpOrderError($order, $fakeOrderId, array $fields, array $missing, $customerId = null) : void {
            $logDir     = __DIR__ . '/logs/checkout_empty';
            $maxDumpLen = 200000;
            
            if ( !$this->ensureLogDirOrNotify($logDir)) {
                // Не удалось обеспечить каталог — выходим, чтобы не ловить новые ошибки
                return;
            }
            
            $orderId = (string)($fakeOrderId ?? ($order->info['order_id'] ?? 'unknown'));
            $orderId = preg_replace('/[^0-9A-Za-z_-]/', '-', $orderId);
            $logFile = $logDir . '/' . $orderId . '.log';
            
            $timestamp = date('c');
            
            $dump = static function($v) use ($maxDumpLen) {
                $out = var_export($v, true);
                if (strlen($out) > $maxDumpLen) {
                    $out = substr($out, 0, $maxDumpLen) . "\n... [truncated]";
                }
                
                return $out;
            };
            
            if ( !file_exists($logFile)) {
                $header = <<<HDR
                    ====================================================================
                    Checkout Empty Fields Log
                    Order ID      : {$orderId}
                    Created at    : {$timestamp}
                    ====================================================================
                    HDR;
                file_put_contents($logFile, $header, LOCK_EX);
            }
            
            // Формируем блок события
            $block .= "Customer ID  : " . ($customerId ?? '') . "\n";
            $block .= "Empty fields : " . implode(', ', $missing) . "\n";
            $block .= "--------------------------------------------------------------------\n";
            $block .= "Fields status:\n";
            
            foreach ($fields as $k => $v) {
                $ok    = ($v === null || $v === '' || (is_array($v) && $v === [])) ? 'EMPTY' : 'OK';
                $val   = (is_array($v) || is_object($v)) ? $dump($v) : (string)$v;
                $block .= sprintf("  - %-28s : %s%s\n", $k, $ok, $ok === 'OK' ? " | value: {$val}" : '');
            }
            
            $block .= "--------------------------------------------------------------------\n";
            $block .= "POST:\n" . $dump($_POST) . "\n";
            $block .= "------------------------------------------------------------\n";
            $block .= "SESSION:\n" . $dump($_SESSION) . "\n";
            $block .= "------------------------------------------------------------\n";
            $block .= "ORDER:\n" . $dump($order) . "\n";
            $block .= "====================================================================\n\n";
            
            // Атомарная дозапись
            $fh = fopen($logFile, 'ab');
            if ($fh) {
                flock($fh, LOCK_EX);
                fwrite($fh, $block);
                fflush($fh);
                flock($fh, LOCK_UN);
                fclose($fh);
            }
            
            // уведомление на почту
            $emailTheme = $fakeOrderId . ' empty [' . implode(', ', $missing) . "] in onepage_checkout.php";
            $emailBody  = "Log file: {$logFile}\nEvent at: {$timestamp}";
            tep_mail_html('', 'debug@zootovary.ua', $emailTheme, nl2br(htmlspecialchars($emailBody)), 'DEBUG ZOOTOVARY.UA', 'debug@zootovary.ua');
        }
        
        /**
         * Гарантирует существование каталога логов.
         * При неудаче отправляет письмо и возвращает false.
         */
        private function ensureLogDirOrNotify(string $logDir) : bool {
            clearstatcache(true, $logDir);
            
            if (is_dir($logDir)) {
                return true;
            }
            
            // Пытаемся создать (рекурсивно)
            // Важно: проверяем второй раз после mkdir() — на случай гонки
            $ok = @mkdir($logDir, 0775, true);
            if ( !$ok && !is_dir($logDir)) {
                $err   = error_get_last();
                $msg   = [];
                $msg[] = 'Не удалось создать каталог логов';
                $msg[] = 'Путь: ' . $logDir;
                $msg[] = 'Ошибка: ' . ($err['message'] ?? 'unknown');
                $msg[] = 'Текущая директория: ' . getcwd();
                $msg[] = 'umask: ' . decoct(umask()); // просто для информации
                $body  = implode("\n", $msg);
                
                // ваша почтовая функция
                @tep_mail_html('', 'debug@zootovary.ua', 'FAILED: mkdir for checkout_empty', nl2br(htmlspecialchars($body)), 'DEBUG ZOOTOVARY.UA', 'debug@zootovary.ua');
                
                return false;
            }
            
            // Опционально: привести права (на случай влияния umask)
            @chmod($logDir, 0775);
            
            return true;
        }
        
        function getShopFromZone(int $store_id) {
            $sql = "SELECT * FROM retail_stores WHERE retail_stores_id = '" . $store_id . "' and language_id = '3' limit 1";
            $query = tep_db_query($sql);
            $row = tep_db_fetch_array($query);
            
            if (empty($row)) {
                return '';
            }
            
            return $row['retail_stores_address'];
        }
        
        function getAddressFromZone(int $countryId, int $zoneId, int $searchId, bool $isPoshtomate) {
            
            $field = $isPoshtomate ? 'zone_poshtomats' : 'zone_warehouses';
            
            $sql = "SELECT * FROM zones WHERE zone_country_id = '" . $countryId . "' and zone_id = '" . $zoneId . "' and language_id = '3' limit 1";
            $query = tep_db_query($sql);
            $row = tep_db_fetch_array($query);
            
            if (!$row || empty($row[$field])) {
                return '';
            }
            
            $items = explode('|', $row[$field]);
            
            foreach ($items as $item) {
                $parts = explode(':', $item);
                if (count($parts) !== 2) {
                    continue; // пропускаем, если формат неожиданный
                }
                
                $id = $parts[1];
                $beforeCoords = $parts[0];
                
                // Пробуем разные разделители — ;; и ;
                $subparts = explode(';;', $beforeCoords);
                if (count($subparts) === 1) {
                    $subparts = explode(';', $beforeCoords);
                }
                
                // Всегда берем первую часть как адрес
                $address = trim($subparts[0]);
                
                if ((string)$id === (string)$searchId) {
                    return (string)$address;
                }
            }
            
            return '';
        }
    }
