<?php
	if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
        require_once('includes/application_top_min.php');
        require_once(DIR_WS_FUNCTIONS . 'html_output.php');
        require_once(DIR_WS_FUNCTIONS . 'redemptions.php');
        require_once(DIR_WS_FUNCTIONS . 'shops.php');
        require_once(DIR_WS_CLASSES . 'language.php');

		if (isset($_POST['switch_lng']) && $_POST['switch_lng'] == 'true') {
			$switch_lng = 'true';
			tep_session_register('switch_lng');
			echo 'success';
			die();
		}

		$_GET['language'] = tep_getcookie('language') ?? $_GET['language'] ?? 'uk';
		$lng = new language();
		$lng->set_language($lng->catalog_languages[$_GET['language']]['directory']);
		$language = $lng->language['directory'];
		$languages_id = $lng->language['id'];
		$_SESSION['languages_id'] = $languages_id;
        require_once(DIR_WS_LANGUAGES . $language . '.php');
        
        require_once(DIR_WS_CLASSES . 'currencies.php');
		$currencies = new currencies();
        
        require_once(DIR_WS_CLASSES . 'shopping_cart.php');
		$cart = new shoppingCart;
        
        require_once(DIR_WS_CLASSES . 'navigation_history.php');
		$navigation = new navigationHistory;
        
        require_once(DIR_WS_CLASSES . 'wishlist.php');
		$wishList = new wishlist;

		$easyDiscountData = checkEasyDiscountData();
		if (!empty($easyDiscountData)) {
            require_once(DIR_WS_CLASSES . 'easy_discount.php');
		}

		if (!empty($easyDiscountData)) {
			if (!tep_session_is_registered('easy_discount')) {
				tep_session_register('easy_discount');
			}
			$easy_discount = new easy_discount;
			$easy_discount->set($easyDiscountData['code'], $easyDiscountData['description'], $easyDiscountData['discount']);
			define('GLOBAL_DISCOUNT_TEXT', ' ' . $easyDiscountData['text']);
		} else {
			define('GLOBAL_DISCOUNT_TEXT', '');
		}

		$cookie_domain = HTTPS_COOKIE_DOMAIN;
		$cookie_path = HTTPS_COOKIE_PATH;
		$Lifetime = defined('USERSESSION_LIFETIME') ? USERSESSION_LIFETIME : 100800;

		tep_session_name(COOKIE_PREFIX . 'ztRsid');
		tep_session_save_path(SESSION_WRITE_DIRECTORY);

		// set the session cookie parameters
		if (function_exists('session_set_cookie_params')) {
			session_set_cookie_params(0, $cookie_path, $cookie_domain);
		} elseif (function_exists('ini_set')) {
			ini_set('session.cookie_lifetime', '0');
			ini_set('session.cookie_path', $cookie_path);
			ini_set('session.cookie_domain', $cookie_domain);
		}

		$session_directory = getenv('SESSION_DIRECTORY');  // папка сессий

		if (isset($session_directory) && tep_not_null($session_directory) && file_exists($session_directory)) {
			$DirectoryPath = $session_directory;
		} elseif (file_exists(SESSION_WRITE_DIRECTORY)) {
			$DirectoryPath = SESSION_WRITE_DIRECTORY;
		} else {
			//оставил на случай если по пути из админки не будет папки... $path определяется в configure.php
			$DirectoryPath = str_replace('\\', '/', $path . 'tmp');
			if (!file_exists($DirectoryPath)) {
				mkdir($DirectoryPath);
			}
		}

		ini_set("session.gc_maxlifetime", $Lifetime);
		ini_set("session.gc_divisor", "1");
		ini_set("session.gc_probability", "1");
		ini_set("session.cookie_lifetime", "0");
		ini_set("session.save_path", $DirectoryPath);
		tep_session_start();

		if (empty($_SESSION)) {
			tep_session_register('currencies');
			tep_session_register('cart');
			tep_session_register('navigation');
			tep_session_register('wishList');
			tep_session_register('valid_coupons');
			tep_session_register('sendto');
			tep_session_register('onepage');
		}

		$cart = $_SESSION['cart'] ?? $cart;
		$wishList = $_SESSION['wishList'] ?? $wishList;
		$currency = $_SESSION['currency'] ?? DEFAULT_CURRENCY;
		$customer_id = $_SESSION['customer_id'] ?? null;
		$valid_coupons = $_SESSION['valid_coupons'] ?? [];
		$customer_shopping_points_spending = $_SESSION['customer_shopping_points_spending'] ?? null;
		$customers_discount_value = $_SESSION['customers_discount_value'] ?? null;
		$sendto = $_SESSION['sendto'] ?? null;
		$onepage = $_SESSION['onepage'] ?? null;
		$shipping = $_SESSION['shipping'] ?? null;
		$customer_have_orders = tep_count_customer_orders() > 0;
		$products_excludes_array = tep_fill_products_excludes();
		$products_coupons_array = tep_fill_products_coupons();
		$products_special_price = tep_fill_products_special_price();
		$products_special_percent = tep_fill_products_special_percent();

		////////////////////////////////////////////////////////////////////////////////
	} else {
        require_once('includes/application_top.php');
	}
    require_once(DIR_WS_CLASSES . 'http_client.php');

    if (ONEPAGE_LOGIN_REQUIRED == 'true') {
        if (!tep_session_is_registered('customer_id')) {
            tep_redirect(tep_href_link(FILENAME_LOGIN));
        }
    }

    if (isset($_GET['rType'])) {
        header('content-type: text/html; charset=utf-8');
    }
    
    require_once(DIR_WS_LANGUAGES . $language . '/' . FILENAME_CHECKOUT);

    if (isset($_REQUEST['coupon']) && tep_not_null($_REQUEST['coupon']) && $_REQUEST['coupon'] == 'redeem code') {
        $_REQUEST['coupon'] = '';
        $_POST['coupon'] = '';
    }
    
    require_once(DIR_WS_CLASSES . 'onepage_checkout.php');
    $onePageCheckout = new osC_onePageCheckout();

    if (!empty(tep_session_is_registered('customer_default_address_id')) && !empty($_SESSION['customer_id'])) {
        $check_customer_query = tep_db_query("select customers_default_address_id from " . TABLE_CUSTOMERS . " where customers_id = '" . $_SESSION['customer_id'] . "'");
        $check_customer = tep_db_fetch_array($check_customer_query);
        $customer_default_address_id = $check_customer['customers_default_address_id'];
        tep_session_register('customer_default_address_id');
    }

    if (!isset($_GET['rType']) && !isset($_GET['action']) && !isset($_POST['action']) && !isset($_GET['error_message']) && !isset($_GET['payment_error'])) {
        $onePageCheckout->init();
    }

    //BOF KGT
    if (MODULE_ORDER_TOTAL_DISCOUNT_COUPON_STATUS == 'true') {
        if (isset($_POST['code'])) {
            if (!tep_session_is_registered('coupon')) {
                tep_session_register('coupon');
            }
            $coupon = $_POST['code'];
        }

        if (isset($_POST['gv_redeem_code'])) {
            if (!tep_session_is_registered('coupon')) {
                tep_session_register('coupon');
            }
            $_SESSION['coupon'] = tep_db_prepare_input($_POST['gv_redeem_code']);
            $coupon = $_POST['gv_redeem_code'];
			if (!isset($valid_coupons)) {
				$valid_coupons = [];
			}
        }

        if (isset($_POST['customer_shopping_points_spending'])) {
            $spending = isset($_POST['customer_shopping_points_spending']) ? (int)$_POST['customer_shopping_points_spending'] : 0;
            $max_points = defined('POINTS_MAX_VALUE') ? (int)POINTS_MAX_VALUE : 0;
            $customer_points = isset($_SESSION['customers_shopping_points']) ? (int)$_SESSION['customers_shopping_points'] : 0;
            
            if ($spending <= $max_points && $spending <= $customer_points) {
                if ( !tep_session_is_registered('customer_shopping_points_spending')) {
                    tep_session_register('customer_shopping_points_spending');
                }
                $_SESSION['customer_shopping_points_spending'] = $spending;
                $customer_shopping_points_spending             = $spending;
            } else {
                unset($_SESSION['add_bonus'], $_POST['add_bonus']);
                
                if ($spending > $max_points) {
                    if (!tep_session_is_registered('points_max_exceeded')) {
                        tep_session_register('points_max_exceeded');
                    }
                } else if ($spending > $customer_points) {
                    if (!tep_session_is_registered('points_customer_exceeded')) {
                        tep_session_register('points_customer_exceeded');
                    }
                }
            }
        }

        if (isset($_POST['add_bonus']) && !tep_session_is_registered('points_max_exceeded') && !tep_session_is_registered('points_customer_exceeded')) {
            if (!tep_session_is_registered('add_bonus')) {
                tep_session_register('add_bonus');
            }
            $_SESSION['add_bonus'] = tep_db_prepare_input($_POST['add_bonus']);
            $add_bonus = tep_db_prepare_input($_POST['add_bonus']);
        }
    }

    //EOF KGT
    ClassLoader::load('order', DIR_WS_CLASSES . 'order.php');
    $order = new order;

    $onePageCheckout->loadSessionVars();

    // register a random ID in the session to check throughout the checkout procedure
    // against alterations in the shopping cart contents
    if (!tep_session_is_registered('cartID')) {
        tep_session_register('cartID');
    }
    $cartID = $cart->cartID;

    // if the order contains only virtual products, forward the customer to the billing page as
    // a shipping address is not needed

    if (!isset($_GET['action']) && !isset($_POST['action'])) {
        if (isset($order->content_type) && ($order->content_type == 'virtual' || $order->content_type == 'virtual_weight')) {
//            $shipping = false;
            $sendto = false;
        }
    } else {
        // if there is nothing in the customers cart, redirect them to the shopping cart page
        if (!in_array($_POST['action'], ['checkPhone', 'checkDuplicatePhoneRegistration']) && $cart->count_contents() < 1) {
			if (isAjax()) {
                $arr = [
                    'checkCart' => true,
                    'location' => tep_href_link(FILENAME_DEFAULT)
                ];
                echo json_encode($arr);
                exit();
            }

			tep_redirect(tep_href_link(FILENAME_DEFAULT));
        }
    }

//    $total_weight = $cart->show_weight();
    $total_weight = 0;
    $total_count = $cart->count_contents();
    if (method_exists($cart, 'count_contents_virtual')) {
        $total_count = $cart->count_contents_virtual();
    }

    if (isset($_POST['action']) && $_POST['action'] == 'countBeforePurchased' && tep_not_null($_POST['phone_number']) && tep_not_null($_POST['pid'])) {
		$pid_data = preg_split("/[{}]+/", $_POST['pid'], -1, PREG_SPLIT_NO_EMPTY);
		echo $cart->count_before_purchased($pid_data, $_POST['phone_number']);
		exit();
    }

    if (isset($_POST['action']) && isset($_POST['setPaymentMethod']) && $_POST['setPaymentMethod'] == 'setPaymentMethod') {
        $onePageCheckout->setCheckoutAddress('setSendTo');
    }

    $action = $_POST['action'] ?? '';

    // load all enabled shipping modules
    include(DIR_WS_CLASSES . 'novaposhta.php');
    $NP = new NP;

    $excludeActionLoadShipping = [
        'setPaymentMethod',
        'updatePaymentMethods',
    ];
    if (!in_array($action, $excludeActionLoadShipping)) {
        require(DIR_WS_CLASSES . 'shipping.php');
        $shipping_modules = new shipping;
    }

    // load all enabled payment modules
    $excludeActionLoadPayment = [
        'setShippingMethod',
        'updateShippingMethods',
        'setSendTo',
        'getWarehouses',
        'getPoshtomates',
    ];
    if (!in_array($action, $excludeActionLoadPayment)) {
        require(DIR_WS_CLASSES . 'payment.php');
        $payment_modules = new payment;
    }

    require(DIR_WS_CLASSES . 'order_total.php');
    $order_total_modules = new order_total;

    if (isset($_POST['updateQuantities_x'])) {
        $action = 'updateQuantities';
    }
    if (isset($_GET['action']) && $_GET['action'] == 'process_confirm') {
        $action = 'process_confirm';
    }
    if (tep_not_null($action)) {
        ob_start();
        if (isset($_POST) && is_array($_POST)) {
			$onePageCheckout->decode_post_vars();
		}

        switch ($action) {
            case 'process_confirm':
			case 'processLogin':
			case 'removeProduct':
			case 'updateQuantities':
			case 'setGV':
			case 'clearPoints':
			case 'redeemPoints':
			case 'updateCartView':
			case 'setMembershipPlan':
			case 'countrySelect':
                break;
            case 'process':
                if (defined(DEBUG_LOG_CHECKOUT) && DEBUG_LOG_CHECKOUT == 'true') {
                    if (!$onePageCheckout->formCheck($_POST)) {
                        echo $onePageCheckout->processCheckout();
                    }
                } else {
                    echo $onePageCheckout->processCheckout();
                }
                break;
            case 'save_add_address_info':
                echo $onePageCheckout->saveAddAddressInfo();
                break;
			case 'setPaymentMethod':
                echo $onePageCheckout->setPaymentMethod($_POST['method']);
                break;
			case 'setShippingMethod':
                echo $onePageCheckout->setShippingMethod($_POST['method']);
                break;
            case 'setSendTo':
            case 'setBillTo':
                echo $onePageCheckout->setCheckoutAddress($action);
                break;
            case 'render_address':
                echo $onePageCheckout->renderAddress();
                break;
            case 'setDepartments':
                echo $onePageCheckout->setDepartments();
                break;
            case 'checkEmailAddress':
                echo $onePageCheckout->checkEmailAddress($_POST['emailAddress']);
                break;
			case 'checkDuplicatePhoneRegistration':
                echo $onePageCheckout->checkDuplicatePhoneRegistration($_POST['phone_number'], $_POST['need_authorisation']);
                break;
            case 'checkPhone':
                echo $onePageCheckout->checkPhone($_POST['phone_number']);
                break;
            case 'saveAddress':
            case 'addNewAddress':
                echo $onePageCheckout->saveAddress($action);
                break;
            case 'saveNewAddress':
                echo $onePageCheckout->saveNewAddress($action);
                break;
            case 'selectAddress':
                echo $onePageCheckout->setAddress($_POST['address_type'], $_POST['address']);
                break;
            case 'setLocalAddress':
                echo $onePageCheckout->setLocalAddress();
                break;
            case 'redeemVoucher':
                echo $onePageCheckout->redeemCoupon();
                break;
			case "someCheckoutAction":
                require_once DIR_WS_TEMPLATES . TEMPLATE_NAME . '/checkout/checkout_cart.php';
                die;
            case 'updatePoints':
            case 'updateShippingMethods':
                include(DIR_WS_INCLUDES . 'checkout/shipping_method.php');
                break;
            case 'updateSchedule':
                include(DIR_WS_INCLUDES . 'checkout/delivery_time.php');
                break;
            case 'updatePaymentMethods':
                include(DIR_WS_INCLUDES . 'checkout/payment_method.php');
                break;
            case 'getOrderTotals':
                if (MODULE_ORDER_TOTAL_INSTALLED) {
                    $order_total_modules->process();
					$updateCartContent = false;

					if (!empty($order->coupons)) {
						foreach ($order->coupons as $coupon) {
							if (isset($coupon->coupon['coupons_current_discount_logic']) &&
								$coupon->coupon['coupons_current_discount_logic'] == 'stupid_ignore')
							{
								$updateCartContent = true;
								break;
							}
						}
					}

					$response = [
						'html'              => $order_total_modules->output('front'),
						'shipMsg'           => $onePageCheckout->checkFreeShipForKiev(),
						'minOrder'          => $onePageCheckout->checkMinOrderForKiev(),
						'shipFree'          => $onePageCheckout->checkFreeShipForNP(),
						'orderWeight'       => $onePageCheckout->getOrderWeight(),
						'orderWidth'        => $onePageCheckout->getMaxWidth(),
						'promoCodes'        => count($order->coupons),
						'updateCartContent' => $updateCartContent,
					];

					if (in_array($_SERVER['REMOTE_ADDR'], $admins_ip)) {
						$response['debug'] = [
							'promoCodes' => $order->coupons ?? null,
							'onepage'    => [
								'delivery' => $onepage['delivery'] ?? null
							],
							'session'    => [
								'onepage' => $_SESSION['onepage'] ?? null,
								'sendto'  => $_SESSION['sendto'] ?? null,
							]
						];
					}

					echo json_encode($response);
                }
                break;
            case 'updateRadiosforTotal':
                $order_total_modules->output();
                echo $order->info['total'];
                break;
            case 'getProductsFinal':
                include(DIR_WS_INCLUDES . 'checkout/products_final.php');
                break;
            case 'getNewAddressForm':
            case 'getAddressBook':
                $addresses_count = tep_count_customer_address_book_entries();
                if ($action == 'getAddressBook') {
                    $addressType = $_POST['addressType'];
                    include(DIR_WS_INCLUDES . 'checkout/address_book.php');
                } else {
                    include(DIR_WS_INCLUDES . 'checkout/new_address.php');
                }
                break;
            case 'getEditAddressForm':
                $aID = tep_db_prepare_input($_POST['addressID']);
                $Qaddress = tep_db_query('select * from ' . TABLE_ADDRESS_BOOK . ' where customers_id = "' . $customer_id . '" and address_book_id = "' . $aID . '"');
                $address = tep_db_fetch_array($Qaddress);
                include(DIR_WS_INCLUDES . 'checkout/edit_address.php');
                break;
            case 'getBillingAddress':
                include(DIR_WS_INCLUDES . 'checkout/billing_address.php');
                break;
            case 'getShippingAddress':
                include(DIR_WS_INCLUDES . 'checkout/shipping_address.php');
                break;
            case 'getWarehouses':
				echo $onePageCheckout->getWarehouses();
                break;
            case 'getPoshtomates':
				echo $onePageCheckout->getPoshtomates();
                break;
        }

        $content = ob_get_contents();
        ob_end_clean();
		echo $content;
        tep_session_close();
        tep_exit();
    }

    function fixSeoLink($url) {
        return str_replace('&amp;', '&', $url);
    }

    //$breadcrumb->add(NAVBAR_TITLE, tep_href_link(FILENAME_CHECKOUT_ONEPAGE));

    $content = CONTENT_CHECKOUT_ONEPAGE;
    $javascript = 'onepagecheckout.js.php';
    $separate_css = 'checkout.css';
    $javascriptFile = 'checkout.init.' . ($useMinify ? CSS_BROWSER_CACHE_UPDATE_DATE . '.min.js' : 'js');

    require(DIR_WS_TEMPLATES . TEMPLATE_NAME . '/' . TEMPLATENAME_MAIN_PAGE);
    require(DIR_WS_INCLUDES . 'application_bottom.php');


