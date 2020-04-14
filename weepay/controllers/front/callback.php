<?php
/**
 * 2007-2018 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    weepay <destek@weepay.com>
 *  @copyright 2019 weepay
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of weepay
 */

require_once _PS_MODULE_DIR_ . 'weepay/classes/WeePayModel.php';

class WeePayCallBackModuleFrontController extends ModuleFrontController
{

    public function __construct()
    {
        parent::__construct();
        $this->display_column_left = false;
        $this->display_column_right = false;
        $this->context = Context::getContext();
    }

    public function init()
    {
        parent::init();

        try {
            if (empty(Tools::getValue('isSuccessful'))) {
                $errorMessage = $this->l('tokenNotFound');
                throw new \Exception("Token not found");
            }
            $paymentId = Tools::getValue('paymentId');
            $customerId = (int) $this->context->cookie->id_customer;
            $orderId = (int) $this->context->cookie->id_cart;
            $locale = $this->context->language->iso_code;
            $remoteIpAddr = Tools::getRemoteAddr();

            $cart = $this->context->cart;
            $cartTotal = (float) $cart->getOrderTotal(true, Cart::BOTH);
            $customer = new Customer($cart->id_customer);

            $currency = $this->context->currency;
            $shopId = (int) $this->context->shop->id;
            $currenyId = (int) $currency->id;
            $languageId = (int) $this->context->language->id;
            $customerSecureKey = $customer->secure_key;
            $TotalPricePay = $this->context->cookie->totalPrice;
            $token = Tools::getValue('isSuccessful');
            $resultMessage = Tools::getValue('resultMessage');
            if ($token == 'True') {
                $bayiID = Configuration::get('weepay_bayi_id');
                $apiKey = Configuration::get('weepay_api_key');
                $secretKey = Configuration::get('weepay_secret_key');

                $weepayArray['Aut'] = array(
                    'bayi-id' => $bayiID,
                    'api-key' => $apiKey,
                    'secret-key' => $secretKey,
                );
                $weepayArray['Data'] = array(
                    'OrderID' => $paymentId,
                );
                $weepayArray = json_encode($weepayArray, true);
                $weepayEndPoint = "https://api.weepay.co/Payment/GetPaymentDetail";
                $requestResponse = json_decode(weepayRequest::GetPaymentDetail($weepayEndPoint, $weepayArray));

                if ($requestResponse->Data->PaymentDetail->PaymentStatus == 2 && $requestResponse->Data->PaymentDetail->TrxStatus == 1) {

                    if (isset($requestResponse->Data->PaymentDetail->InstallmentNumber) && !empty($requestResponse->Data->PaymentDetail->InstallmentNumber) && $requestResponse->Data->PaymentDetail->InstallmentNumber > 1) {
                        /* Installment Calc and DB Update */

                        $installmentFee = $requestResponse->Data->PaymentDetail->Amount - $TotalPricePay;
                        $this->context->cookie->installmentFee = $installmentFee;

                        $installmentMessage = '<br><br><strong style="color:#000;">Taksitli Alışveriş: </strong>Toplam ödeme tutarınıza <strong style="color:#000">' . $requestResponse->Data->PaymentDetail->InstallmentNumber . ' Taksit </strong> için <strong style="color:red">' . Tools::displayPrice($installmentFee, $currency, false) . '</strong> yansıtılmıştır.<br>';

                        $installmentMessageEmail = '<br><br><strong style="color:#000;">' . $this->l('installmentShopping') . '</strong><br> ' . $this->l('installmentOption') . '<strong style="color:#000"> ' . $requestResponse->Data->PaymentDetail->InstallmentNumber . ' ' . $this->l('InstallmentKey') . '<br></strong>' . $this->l('commissionAmount') . '<strong style="color:red">
             ' . Tools::displayPrice($installmentFee, $currency, false) . '</strong><br>';

                        $extraVars['{total_paid}'] = Tools::displayPrice($requestResponse->Data->PaymentDetail->Amount, $currency, false);
                        $extraVars['{date}'] = Tools::displayDate(date('Y-m-d H:i:s'), null, 1) . $installmentMessageEmail;

                        /* Invoice false */
                        Configuration::updateValue('PS_INVOICE', false);
                    }

                    $this->module->validateOrder($orderId, Configuration::get('PS_OS_PAYMENT'), $cartTotal, $this->module->displayName, $installmentMessage, $extraVars, $currenyId, false, $customerSecureKey);
                    if (isset($requestResponse->Data->PaymentDetail->InstallmentNumber) && !empty($requestResponse->Data->PaymentDetail->InstallmentNumber) && $requestResponse->Data->PaymentDetail->InstallmentNumber > 1) {
                        /* Invoice true */
                        Configuration::updateValue('PS_INVOICE', $orderId);

                        $currentOrderId = (int) $this->module->currentOrder;
                        $order = new Order($currentOrderId);

                        /* Update Total Price and Installment Calc and DB Update  */
                        WeePayModel::updateOrderTotal($requestResponse->Data->PaymentDetail->Amount, $currentOrderId);

                        WeePayModel::updateOrderPayment($requestResponse->Data->PaymentDetail->Amount, $order->reference);

                        /* Open Thread */
                        $customer_thread = new CustomerThread();
                        $customer_thread->id_contact = 0;
                        $customer_thread->id_customer = $customer->id;
                        $customer_thread->id_shop = $shopId;
                        $customer_thread->id_order = $currentOrderId;
                        $customer_thread->id_lang = $languageId;
                        $customer_thread->email = $customer->email;
                        $customer_thread->status = 'open';
                        $customer_thread->token = Tools::passwdGen(12);
                        $customer_thread->add();

                        /* Add Info Message */
                        $customer_message = new CustomerMessage();
                        $customer_message->id_customer_thread = $customer_thread->id;
                        $customer_message->id_employee = 1;
                        $customer_message->message = $installmentMessage;
                        $customer_message->private = 0;
                        $customer_message->add();
                    }

                    Tools::redirect('index.php?controller=order-confirmation&id_cart=' . $orderId . '&id_module=' . (int) $this->module->id . '&id_order=' . $this->module->currentOrder . '&key=' . $customer->secure_key);

                }

            } else {

                throw new Exception($resultMessage);

            }
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();

            $this->context->smarty->assign(array(
                'errorMessage' => $errorMessage,
            ));

            $this->setTemplate('module:weepay/views/templates/front/weepay_error.tpl');
        }
    }

}
