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
 *  @copyright 2018 weepay
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of weepay
 */

class weepayRequest
{
    public static function checkoutFormRequest($endpoint, $data)
    {
        $endpoint = 'https://api.weepay.co/Payment/PaymentCheckoutFormCreate/';

        return weepayRequest::curlPost($data, $endpoint);
    }

    public static function GetPaymentDetail($endpoint, $data)
    {
        $endpoint = 'https://api.weepay.co/Payment/GetPaymentDetail';

        return weepayRequest::curlPost($data, $endpoint);
    }

    public static function curlPost($data, $endpoint, $json = false)
    {
        $ch = curl_init(); // initialize curl handle
        curl_setopt($ch, CURLOPT_URL, $endpoint); // set url to post to
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // return into a variable
        if ($json) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // times out after 4s
        curl_setopt($ch, CURLOPT_POST, 1); // set POST method
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data); // add POST fields
        if ($result = curl_exec($ch)) { // run the whole process
            curl_close($ch);
            return $result;
        }
    }
}
