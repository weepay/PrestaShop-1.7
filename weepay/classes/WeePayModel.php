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

class WeePayModel extends ObjectModel
{

    public static function updateOrderTotal($price, $order_id)
    {

        $tableName = 'orders';
        $order_id = (int) $order_id;

        $sql = 'UPDATE ' . _DB_PREFIX_ . bqSQL($tableName) . '
		    SET `total_paid` = \'' . $price . '\',
		     	`total_paid_tax_incl` = \'' . $price . '\',
		     	`total_paid_tax_excl` = \'' . $price . '\',
		     	`total_paid_real` = \'' . $price . '\'
		    WHERE `id_order` = \'' . $order_id . '\'';

        return Db::getInstance()->execute($sql);
    }

    /**
     * @param $price
     * @param $reference
     * @return mixed
     */
    public static function updateOrderPayment($price, $reference)
    {
        $tableName = 'order_payment';
        $reference = $reference;

        $sql = 'UPDATE ' . _DB_PREFIX_ . bqSQL($tableName) . '
		    SET `amount` = \'' . $price . '\'
		    WHERE `order_reference` = \'' . $reference . '\'';

        return Db::getInstance()->execute($sql);
    }
}
