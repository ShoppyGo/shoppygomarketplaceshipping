<?php
/**
 * Copyright since 2022 Bwlab of Luigi Massa and Contributors
 * Bwlab of Luigi Massa is an Italy Company
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@shoppygo.io so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade ShoppyGo to newer
 * versions in the future. If you wish to customize ShoppyGo for your
 * needs please refer to https://docs.shoppygo.io/ for more information.
 *
 * @author    Bwlab and Contributors <contact@shoppygo.io>
 * @copyright Since 2022 Bwlab of Luigi Massa and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Shoppygomarketplaceshipping extends CarrierModule
{
    public const PREFIX = 'SHOPPYGOMARKETPLACESHIPPING';
    public static $riepilogo_costi_spedizione = [];
    public static $cost_for_seller = [];
    public $id_carrier;
    protected $config_form = false;
    protected $_hooks = [
        'actionCarrierUpdate',
        'displayAfterCarrier',
    ];
    protected $_carriers = [
        'Marketplace shipping' => 'shoppygomarketplaceshipping',
    ];

    public function __construct()
    {
        $this->name = 'shoppygomarketplaceshipping';
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.0';
        $this->author = 'Bwlab';
        $this->need_instance = 0;

        /*
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Marketplace Shipping');
        $this->description = $this->l('Manage the sipping of marketplace');

        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
    }

    /**
     * @param $params
     * @param $shipping_cost
     *
     * @return void
     *
     * @throws Exception
     */
    public function getOrderShippingCost($params, $shipping_cost)
    {
        // in quanto il metodo getPackageShippingCost esiste, questo metodo non viene richiamato
        // forse per compatibilità con il passato
        throw new \Exception('modulo spediione: richiamato il metodo getOrderShippingCost, ma non doveva essere');
    }

    /**
     * @param array $params
     *
     * @return void
     *
     * @throws Exception
     */
    public function getOrderShippingCostExternal($params)
    {
        // richiamato solo se il carrir $carrier->need_range, ma non deve
        throw new \Exception(
            'modulo spediione: richiamato il metodo getOrderShippingCostExternal, ma non doveva essere'
        );
    }

    /**
     * @param Cart $params
     * @param $shipping_cost
     * @param ProductCore[] $products
     *
     * @return int
     */
    public function getPackageShippingCost($params, $shipping_cost,  $products)
    {xdebug_break();
        $service = $this->getMarketplaceFront();
        self::$cost_for_seller = $service->getTotalShippingBySeller($products);
        $total = 0;
        array_walk(
            self::$cost_for_seller, static function (int $r) use (&$total) {
                $total += $r;
            }
        );

        return $total;
    }

    public function hookActionCarrierUpdate($params)
    {
        if ($params['carrier']->id_reference == Configuration::get(self::PREFIX . 'swipbox_reference')) {
            Configuration::updateValue(self::PREFIX . 'swipbox', $params['carrier']->id);
        }
    }

    /**
     * @param $params
     *
     * @return string
     */
    public function hookDisplayAfterCarrier($params)
    {
        //
        // questo _hook_ viene richiamato nel template
        //  themes/classic/templates/checkout/_partials/steps/shipping.tpls
        //
        // serve a stampare una tabella con il totale dei costi degli spedizionieri
        // per stampare il totale della spedizione servirsi di self::$riepilogo_costi_spedizione che dovrebbe essere
        //  compilato dal metodo getPackageShippingCost
        // $params è array che contiene:
        //  cart => oggetto CartCore()
        // cookie => oggetto Cookie()
        // altern => 1   --> ??????
        //
//        /** @var \PrestaShop\PrestaShop\Adapter\Entity\Cart $cart */
//        $cart = $params['cart'];
//
//        /** @var \PrestaShop\PrestaShop\Adapter\Entity\Cookie $cookie */
//        $cookie = $params['cookie'];

        $service = $this->getMarketplaceFront();
        $cost_list = [];
        foreach (self::$cost_for_seller as $seller => $cost) {
            $name = $service->getSellerName($seller);

            $cost_list[] = ['seller_name' => $name, 'total' => $cost];
        }
        $this->smarty->assign(
            [
                'cost_list' => $cost_list,
            ]
        );

        return $this->fetch('module:shoppygomarketplaceshipping/shipping_cost_detail.tpl');
    }

    public function install()
    {
        if (parent::install()) {
            foreach ($this->_hooks as $hook) {
                if (!$this->registerHook($hook)) {
                    return false;
                }
            }

            if (!$this->createCarriers()) { //function for creating new currier
                return false;
            }

            return true;
        }

        return false;
    }

    public function uninstall()
    {
        if (parent::uninstall()) {
            foreach ($this->_hooks as $hook) {
                if (!$this->unregisterHook($hook)) {
                    return false;
                }
            }

            if (!$this->deleteCarriers()) {
                return false;
            }

            return true;
        }

        return false;
    }

    protected function createCarriers()
    {
        // controllare la documentazione # check http://doc.prestashop.com/display/PS16/Creating+a+carrier+module
        // ed anche  https://belvg.com/blog/how-to-create-shipping-module-for-prestashop.html
        //
        // crezione del carrier base e unico per il marketpace in quanto tutta la logica è gestita dal metodo di calcolo
        // costo dell spedizione
        //
        $carrier = new Carrier();
        $carrier->name = 'marketplace';
        $carrier->active = true;
        $carrier->deleted = 0;
        $carrier->shipping_handling = false;
        $carrier->range_behavior = 0;
        $carrier->delay[Configuration::get('PS_LANG_DEFAULT')] = 1;
        $carrier->shipping_external = true;
        $carrier->is_module = true;
        $carrier->external_module_name = $this->name;
        $carrier->need_range = true;

        $carrier->add();
        Configuration::updateValue('SHOPPYGOMARKETPLACESHIPPING_CARRIER', $carrier->id);

        return true;
    }

    protected function deleteCarriers()
    {
        foreach ($this->_carriers as $value) {
            $tmp_carrier_id = Configuration::get('SHOPPYGOMARKETPLACESHIPPING_CARRIER');
            $carrier = new Carrier($tmp_carrier_id);
            $carrier->delete();
        }

        return true;
    }

    private function getMarketplaceFront(): MarketplaceCoreFront
    {
        return new \MarketplaceCoreFront($this->get('doctrine'), $this->context);
    }
}
