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

use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Presenter\Cart\CartPresenter;
use PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductListingPresenter;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever;
use PrestaShop\PrestaShop\Core\Product\ProductPresentationSettings;
use ShoppyGo\MarketplaceBundle\Presenter\MarketplaceCartPresenter;

class Shoppygomarketplaceshipping extends CarrierModule
{
    public const PREFIX = 'SHOPPYGOMARKETPLACESHIPPING';
    public static $riepilogo_costi_spedizione = [];
    public static $cost_for_seller = [];
    public $id_carrier;
    protected $config_form = false;
    protected $_hooks = [
        'displayHeader',
        'actionCarrierUpdate',
        'displayAfterCarrier',
        'actionFrontControllerSetMedia',
    ];
    protected $_carriers = [
        'Marketplace shipping' => 'shoppygomarketplaceshipping',
    ];

    public function __construct()
    {
        $this->name = 'shoppygomarketplaceshipping';
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.0';
        $this->author = 'ShoppyGo';
        $this->need_instance = 0;

        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('Marketplace Shipping', [], 'Modules.Shoppygomarketplaceshipping.Admin');
        $this->description = $this->trans(
            'Display list of seller shipping cost in checkout',
            [],
            'Modules.Shoppygomarketplaceshipping.Admin'
        );

        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];
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
    public function getPackageShippingCost($params, $shipping_cost, $products)
    {
        $service = $this->getMarketplaceFront();
        self::$cost_for_seller = $service->getTotalShippingBySeller($products);
        $total = 0;
        array_walk_recursive(
            self::$cost_for_seller, static function (int $r) use (&$total) {
            $total += $r;
        }
        );

        return $total;
    }

    public function hookActionCarrierUpdate($params)
    {
        if ($params['carrier']->id_reference == Configuration::get(self::PREFIX.'swipbox_reference')) {
            Configuration::updateValue(self::PREFIX.'swipbox', $params['carrier']->id);
        }
    }

    public function hookActionFrontControllerSetMedia()
    {
        $this->context->controller->registerStylesheet(
            $this->name.'-css-'.$this->version,
            $this->_path.'views/css/shoppygo.css'
        );
    }

    /**
     * @param $params
     *
     * @return string
     */
    public function hookDisplayAfterCarrier($params)
    {
        //
        // colled in  template
        //  themes/classic/templates/checkout/_partials/steps/shipping.tpls
        // print a table with seller shipping costs
        // to print use self::$riepilogo_costi_spedizione compiled from method getPackageShippingCost

        $service = $this->getMarketplaceFront();
        $cost_list = [];
        $cart_products = $this->context->cart->getProducts();
        $seller_product = $service->getSellersProduct($cart_products);
        $cloned_cart = clone $this->context->cart;
        $policies = [];
        foreach (self::$cost_for_seller as $id_seller => $cost) {
            $seller_id_products = array_map(static function ($item) use ($id_seller) {
                if ($item['id_seller'] == $id_seller) {
                    return $item['id_product'];
                }
            }, $seller_product);
            $seller_name = $service->getSellerName($id_seller);

            if (false === array_key_exists($seller_name, $policies)) {
                $policies[$seller_name] = $service->getMarketplaceSellerData($id_seller)
                    ->getReturnPolicy()
                    ?: $this->trans(
                        'No return policy. Please contact the Marketplace Support. Thanks in advance',
                        [],
                        'Modules.Shoppygomarketplaceshipping.Shop'
                    );
            }
            $products = static function ($item) use ($seller_id_products) {
                return in_array($item['id_product'], $seller_id_products);
            };
            // access protected method _products
            $this->addProductToCart($cloned_cart, array_filter($cart_products, $products));

            $cost_list[$seller_name]['carrier_costs'] = $cost;
            $cost_list[$seller_name]['seller_products'] = (new MarketplaceCartPresenter())->present(
                cart: $cloned_cart,
                refresh: false
            );
        }
        $this->smarty->assign(
            [
                'cost_list' => $cost_list,
                'policies'  => $policies,
            ]
        );

        return $this->fetch('module:shoppygomarketplaceshipping/views/templates/hook/displayAfterCarrier.tpl');
    }

    public function install()
    {
        if (parent::install()) {
            foreach ($this->_hooks as $hook) {
                if (!$this->registerHook($hook)) {
                    return false;
                }
            }

            if (!$this->createCarriers()) { //new carrier
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

    private function addProductToCart(
        ?Cart $cloned_cart,
        array $products
    ): void {
        $reflector = new ReflectionClass($cloned_cart);
        $reflector_property = $reflector->getProperty('_products');
        $reflector_property->setAccessible(true);;
        $reflector_property->setValue(
            $cloned_cart,
            $products
        );
        $reflector_property->setAccessible(false);
    }

    private function getMarketplaceFront(): MarketplaceCoreFront
    {
        return new \MarketplaceCoreFront($this->get('doctrine'), $this->context);
    }

    private function getPresenterSettings()
    {
        $settings = new ProductPresentationSettings();

        $settings->catalog_mode = Configuration::isCatalogMode();
        $settings->catalog_mode_with_prices = (int)Configuration::get('PS_CATALOG_MODE_WITH_PRICES');
        $settings->include_taxes = (new TaxConfiguration())->includeTaxes();
        $settings->allow_add_variant_to_cart_from_listing = (int)Configuration::get('PS_ATTRIBUTE_CATEGORY_DISPLAY');
        $settings->stock_management_enabled = Configuration::get('PS_STOCK_MANAGEMENT');
        $settings->showPrices = Configuration::showPrices();
        $settings->lastRemainingItems = Configuration::get('PS_LAST_QTIES');
        $settings->showLabelOOSListingPages = (bool)Configuration::get('PS_SHOW_LABEL_OOS_LISTING_PAGES');

        return $settings;
    }
}
