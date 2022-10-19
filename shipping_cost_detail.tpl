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

<table class="table table-striped table-sm">
    <thead>
    <tr>
        <th scope="col">Spedito da</th>
        <th scope="col" class="text-xl-center">Costo spedizione</th>
    </tr>
    </thead>
    <tbody>
    {foreach from=$cost_list item=row}
        <tr>
            <td scope="row">{$row.seller_name}</td>
            <td class="text-xl-center">{Tools::displayPrice($row.total)}</td>
        </tr>
    {/foreach}
    </tbody>
    <caption>i costi esposti sopra sono relativi alle singole spedizioni di ogni venditore</caption>
</table>
