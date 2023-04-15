{*
 * Copyright since 2007 Bwlab di Luigi Massa and Contributors
 * Bwlab of Luigi Massa is an Italy Company
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@shoppygo.io so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade ShoppyGo Marketplace to newer
 * versions in the future. If you wish to customize ShoppyGo Marketplace for your
 * needs please refer to https://docs.shoppygo.io/ for more information.
 *
 * @author    Bwlab di Luigi Massa and Contributors <contact@shoppygo.io>
 * @copyright Since 2007 Bwlab di Luigi Massa and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
*}

<div class="shoppygo container">
    {foreach from=$cost_list key=seller_name  item=row}
      <h2>
          {l s="Shipped by" d="Modules.Shoppygomarketplaceshipping.Shop"}
        :{$seller_name} ({$row.seller_products.summary_string})
      </h2>
        {foreach from=$row.seller_products.products item=product}
            {include file='module:shoppygomarketplaceshipping/views/templates/hook/_partials/cart-summary-product-line.tpl'
            product=$product}
        {/foreach}

        {foreach from=$row['carrier_costs'] key=carrier_name item=total}
          <p class="lead">
              {l s="Carrier name"
              d="Modules.Shoppygomarketplaceshipping.Shop"}
              {$carrier_name}
            - {l s="Cost" d="Modules.Shoppygomarketplaceshipping.Shop"}:
              {Tools::displayPrice($total)}
          </p>
        {/foreach}
      <h3>{l s="Return Policy of  %seller_name%"
          sprintf=['%seller_name%'=>$seller_name]
          d="Modules.Shoppygomarketplaceshipping.Shop"}</h3>
      <p>
          {$policies[$seller_name]}
      </p>
    {/foreach}
</div>

<p class="lead">
  {l
  s="The costs shown above are related to the individual shipments of each seller"
  d="Modules.Shoppygomarketplaceshipping.Shop"}

</p>
