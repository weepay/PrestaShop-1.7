<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
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

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

include_once 'classes/weepayRequest.php';

class WeePay extends PaymentModule
{
    protected $config_form = false;
    public $extra_mail_vars;

    public function __construct()
    {
        $this->name = 'weepay';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'weepay';
        $this->need_instance = 1;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('weepay Payment Module');
        $this->description = $this->l('weepay Payment Gateway for PrestaShop');
        $this->basketItemsNotMatch = $this->l('basketItemsNotMatch');
        $this->uniqError = $this->l('uniqError');
        $this->error3D = $this->l('error3D');
        $this->tokenNotFound = $this->l('tokenNotFound');
        $this->orderNotFound = $this->l('orderNotFound');
        $this->generalError = $this->l('generalError');
        $this->CardFamilyName = $this->l('CardFamilyName');
        $this->InstallmentKey = $this->l('InstallmentKey');
        $this->installmentShopping = $this->l('installmentShopping');
        $this->installmentOption = $this->l('installmentOption');
        $this->commissionAmount = $this->l('commissionAmount');

        $this->confirmUninstall = $this->l('are you sure ?');

        $this->limited_countries = array('TR', 'FR', 'EN');

        $this->limited_currencies = array('TRY', 'EUR', 'USD', 'GBP');

        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);

        $this->extra_mail_vars = array(
            '{instalmentFee}' => '',
        );
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        if (extension_loaded('curl') == false) {
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');
            return false;
        }

        $iso_code = Country::getIsoById(Configuration::get('PS_COUNTRY_DEFAULT'));

        if (in_array($iso_code, $this->limited_countries) == false) {
            $this->_errors[] = $this->l('This module is not available in your country');
            return false;
        }

        return parent::install() &&
        $this->registerHook('footer') &&
        $this->registerHook('backOfficeHeader') &&
        $this->registerHook('PaymentOptions') &&
        $this->registerHook('paymentReturn');
    }

    public function uninstall()
    {

        return $this->unregisterHook('footer')
        && $this->unregisterHook('backOfficeHeader')
        && $this->unregisterHook('PaymentOptions')
        && $this->unregisterHook('paymentReturn')
        && Configuration::deleteByName('weepay_bayi_id')
        && Configuration::deleteByName('weepay_api_key')
        && Configuration::deleteByName('weepay_secret_key')
        && Configuration::deleteByName('weepay_module_status')
        && Configuration::deleteByName('weepay_option_text')
        && Configuration::deleteByName('weepay_display')
        && parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool) Tools::isSubmit('submitWeePayModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        $this->weepayTitle();

        return $output . $this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->id = 'weepay';
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitWeePayModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
        . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(

                    array(
                        'col' => 4,
                        'type' => 'text',
                        'name' => 'weepay_bayi_id',
                        'required' => true,
                        'label' => $this->l('Bayi ID'),
                    ),
                    array(
                        'col' => 4,
                        'type' => 'text',
                        'name' => 'weepay_api_key',
                        'required' => true,
                        'label' => $this->l('Api Key'),
                    ),
                    array(
                        'col' => 4,
                        'type' => 'text',
                        'name' => 'weepay_secret_key',
                        'required' => true,
                        'label' => $this->l('Secret Key'),
                    ),
                    array(
                        'col' => 9,
                        'type' => 'text',
                        'name' => 'weepay_option_text',
                        'label' => $this->l('Payment Text'),
                    ),
                    array(
                        'type' => 'select',
                        'label' => $this->l('Display Form'),
                        'name' => 'weepay_display',
                        'required' => true,
                        'is_bool' => true,
                        'options' => array(
                            'query' => array(
                                array('id' => 'responsive', 'name' => 'Responsive'),
                                array('id' => 'popup', 'name' => 'Popup'),
                            ),
                            'id' => 'id',
                            'name' => 'name',
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */

    protected function getConfigFormValues()
    {
        return array(
            'weepay_bayi_id' => Configuration::get('weepay_bayi_id', true),
            'weepay_api_type' => Configuration::get('weepay_api_type', true),
            'weepay_api_key' => Configuration::get('weepay_api_key', true),
            'weepay_secret_key' => Configuration::get('weepay_secret_key', true),
            'weepay_module_status' => Configuration::get('weepay_module_status', true),
            'weepay_option_text' => Configuration::get('weepay_option_text', true),
            'weepay_display' => Configuration::get('weepay_display', true),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {

        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }

        $this->weepayTitle();
        $isoCode = $this->context->language->iso_code;
        $apiKey = Tools::getValue('weepay_api_key');
        $secretKey = Tools::getValue('weepay_secret_key');
        $randNumer = rand(100000, 99999999);

    }

    /**
     * @return bool
     */
    private function weepayTitle()
    {
        $title = Configuration::get('weepay_option_text');

        if (!$title) {
            Configuration::updateValue('weepay_option_text', 'tr=Kredi ve Banka Kartı ile Ödeme - weepay|en=Credit and Debit Card weepay');
        }

        return true;
    }
    private function getCurrencyConstant($currencyCode)
    {
        $currency = 'TL';
        switch ($currencyCode) {
            case "TRY":
                $currency = 'TL';
                break;
            case "USD":
                $currency = 'USD';
                break;
            case "GBP":
                $currency = 'GBP';
                break;
            case "EUR":
                $currency = 'EUR';
                break;
        }
        return $currency;
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    // public function hookBackOfficeHeader()
    // {

    //     if (Tools::getValue('configure') == $this->name) {
    //         $this->context->controller->addJS($this->_path . 'views/js/back.js');
    //         $this->context->controller->addCSS($this->_path . 'views/css/back.css');
    //     }
    // }

    public function hookPaymentOptions($params)
    {

        if (!$params['cart']->id_carrier) {
            return $this->paymentOptionResult();
        }

        $weepayCheckoutFormResponse = $this->checkoutFormGenerate($params);

        $phpCheckVersion = $this->versionCheck();

        if ($phpCheckVersion) {
            return $this->errorAssign($phpCheckVersion);
        }

        if (!is_object($weepayCheckoutFormResponse)) {
            return $this->errorAssign($weepayCheckoutFormResponse);
        }

        return $this->successAssign($weepayCheckoutFormResponse);
    }

    /**
     * @param $params
     * @return mixed|string
     */
    public function checkoutFormGenerate($params)
    {
        $this->context->cookie->totalPrice = false;
        $this->context->cookie->installmentFee = false;

        $currency = $this->getCurrency($params['cart']->id_currency);
        $context = $this->context;
        $iso_code = $context->language->iso_code;
        $Locale = ($iso_code == "tr") ? "tr" : "en";
        $order_info_ip = Tools::getRemoteAddr();
        $billingAddress = new Address($params['cart']->id_address_invoice);
        $shippingAddress = new Address($params['cart']->id_address_delivery);
        $billingAddress->email = $params['cookie']->email;
        $shippingAddress->email = $params['cookie']->email;
        $unique_conversation_id = $params['cookie']->id_cart;
        $order_info_firstname = $billingAddress->firstname;
        $order_info_lastname = $billingAddress->lastname;
        $order_info_telephone = $billingAddress->phone;
        $order_info_email = $billingAddress->email;
        $city = $billingAddress->city;
        $currency = new Currency((int) $params['cookie']->id_currency);
        $thisUserCurrency = $currency->iso_code;
        $thisUserCurrency = $this->getCurrencyConstant($thisUserCurrency);
        $bayiID = Configuration::get('weepay_bayi_id');
        $apiKey = Configuration::get('weepay_api_key');
        $secretKey = Configuration::get('weepay_secret_key');
        $rand = rand(100000, 99999999);
        $httpProtocol = !Configuration::get('PS_SSL_ENABLED') ? 'http://' : 'https://';
        $order_amount = (double) number_format($params['cart']->getOrderTotal(true, Cart::BOTH), 2, '.', '');
        $weepayArray['Aut'] = array(
            'bayi-id' => $bayiID,
            'api-key' => $apiKey,
            'secret-key' => $secretKey,
        );
        $weepayArray['Data'] = array(
            'CallBackUrl' => $httpProtocol . htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8') . __PS_BASE_URI__ . 'index.php?module_action=init&fc=module&module=weepay&controller=callback',
            'Price' => $order_amount,
            'Locale' => $Locale,
            'IpAddress' => $order_info_ip,
            'CustomerNameSurname' => $order_info_firstname . ' ' . $order_info_lastname,
            'CustomerPhone' => $order_info_telephone,
            'CustomerEmail' => $order_info_email,
            'OutSourceID' => $unique_conversation_id,
            'Description' => !empty($city) ? $city : "NOT",
            'Currency' => $thisUserCurrency,
            'Channel' => 'Module',
        );

        $endpoint = '';
        $weepayArray = json_encode($weepayArray, true);
        $this->context->cookie->totalPrice = $order_amount;
        $requestResponse = json_decode(weepayRequest::checkoutFormRequest($endpoint, $weepayArray));
        if ($requestResponse->status == "failure") {

            return $requestResponse->message;
        }
        return $requestResponse;
    }

    /**
     * This hook is used to display the order confirmation page.
     */
    public function hookPaymentReturn($params)
    {

        if ($this->active == false) {
            return;
        }

        $order = $params['order'];

        if ($order->getCurrentOrderState()->id != Configuration::get('PS_OS_ERROR')) {
            $this->smarty->assign('status', 'ok');
        }

        $this->smarty->assign(array(
            'id_order' => $order->id,
            'reference' => $order->reference,
            'params' => $params,
            'total' => Tools::displayPrice($this->context->cookie->totalPrice, $this->context->currency, false),
            'installmentFee' => Tools::displayPrice($this->context->cookie->installmentFee, $this->context->currency, false),
        ));

        return $this->display(__FILE__, 'views/templates/front/confirmation.tpl');
    }

    /**
     * @return mixed
     */
    private function getOptionText()
    {
        $title = Configuration::get('weepay_option_text');
        $isoCode = $this->context->language->iso_code;

        $title = $this->weePayLanguageChanger($title, $isoCode);

        return $title;
    }

    /**
     * @return array
     */
    private function paymentOptionResult()
    {
        $title = $this->getOptionText();
        $newOptions = array();

        $newOption = new PaymentOption();
        $newOption->setModuleName($this->name)
            ->setCallToActionText($this->trans($title, array(), 'Modules.WeePay'))
            ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
            ->setAdditionalInformation($this->fetch('module:weepay/views/templates/front/weepay.tpl'));

        $newOptions[] = $newOption;

        return $newOptions;
    }

    /**
     * @param $weepayheckoutFormResponse
     * @return array
     */
    private function successAssign($weepayCheckoutFormResponse)
    {
        $logo = Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/cards.png');

        $title = $this->getOptionText();

        $this->context->smarty->assign('response', $weepayCheckoutFormResponse->CheckoutFormData);
        $this->context->smarty->assign('form_class', Configuration::get('weepay_display'));
        $this->context->smarty->assign('credit_card', $title);
        $this->context->smarty->assign('contract_text', $this->l('Contract approval is required for the payment form to be active.'));
        $this->context->smarty->assign('cards', $logo);
        $this->context->smarty->assign('module_dir', __PS_BASE_URI__);

        return $this->paymentOptionResult();
    }

    /**
     * @param $errorMessage
     * @return array
     */
    private function errorAssign($errorMessage)
    {
        $this->context->smarty->assign('error', $errorMessage);

        return $this->paymentOptionResult();
    }

    /**
     * @return bool|string
     */
    private function versionCheck()
    {
        $phpVersion = phpversion();
        $requiredVersion = 5.4;

        if ($phpVersion < $requiredVersion) {
            return 'Required PHP ' . $requiredVersion . ' and greater for weepay PrestaShop Payment Gateway';
        }

        return false;
    }

    /**
     * @param $title
     * @param $isoCode
     * @return mixed
     */
    private function weePayLanguageChanger($title, $isoCode)
    {
        if ($title) {
            $parser = explode('|', $title);

            if (is_array($parser) && count($parser)) {
                foreach ($parser as $parse) {
                    $result = explode('=', $parse);
                    if ($isoCode == $result[0]) {
                        $title = $result[1];
                        break;
                    }
                }
            }
        }

        return $title;
    }
}
