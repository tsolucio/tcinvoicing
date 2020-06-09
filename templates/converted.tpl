{*<!--
/*********************************************************************************
 * Copyright 2013 JPL TSolucio, S.L.  --  This file is a part of vtiger CRM TimeControl extension.
 * You can copy, adapt and distribute the work under the "Attribution-NonCommercial-ShareAlike"
 * Vizsage Public License (the "License"). You may not use this file except in compliance with the
 * License. Roughly speaking, non-commercial users may share and modify this code, but must give credit
 * and share improvements. However, for proper details please read the full License, available at
 * http://vizsage.com/license/Vizsage-License-BY-NC-SA.html and the handy reference for understanding
 * the full license at http://vizsage.com/license/Vizsage-Deed-BY-NC-SA.html. Unless required by
 * applicable law or agreed to in writing, any software distributed under the License is distributed
 * on an  "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and limitations under the
 * License terms of Creative Commons Attribution-NonCommercial-ShareAlike 3.0 (the License).
 *************************************************************************************************
 *  Module       : TSolucio Timecontrol Invoicing
 *  Version    : 1.3
 *  Author       : JPL TSolucio, S. L.
 *************************************************************************************************
-->*}
{include file='Buttons_List1.tpl'}

<div class="slds-card slds-card__header" style="width:95%;margin:auto;">
<header class="slds-media slds-media_center slds-has-flexi-truncate slds-m-bottom_small">
	<div class="slds-media__figure">
	<span class="slds-icon_container slds-icon-standard-account" title="money">
		<svg class="slds-icon slds-icon_small" aria-hidden="true">
		<use xlink:href="include/LD/assets/icons/utility-sprite/svg/symbols.svg#moneybag"></use>
		</svg>
		<span class="slds-assistive-text">{'TimecontrolInv'|@getTranslatedString:'TimecontrolInv'}</span>
	</span>
	</div>
	<div class="slds-media__body">
	<h2 class="slds-card__header-title">
		<span>
		{if $convertto == 'SalesOrder'}{$MOD.SO_CREATED}
		{elseif $convertto == 'Invoice'}{$MOD.INV_CREATED}
		{elseif $convertto == 'PurchaseOrder'}{$MOD.PO_CREATED}
		{/if}
		</span>
	</h2>
	</div>
</header>
<table class="slds-table slds-table_cell-buffer slds-table_bordered" style="width:95%;margin:auto;">
<thead>
	<tr>
		<th scope="col"><b>{$APP.Reference}</b></th>
		<th scope="col"><b>{if $convertto eq 'PurchaseOrder'}{$APP.SINGLE_Vendors}{else}{$APP.SINGLE_Accounts}{/if}</b></th>
		<th scope="col"><b>{$APP.LBL_SUB_TOTAL}</b></th>
		<th scope="col"><b>{$APP.LBL_TOTAL}</b></th>
	</tr>
</thead>
<tbody>
	{foreach item=values from=$documents}
	<tr>
		<td><a href="index.php?module={$convertto}&action=DetailView&record={$values.docid}">{$values.title}</a></td>
		{if $convertto eq 'PurchaseOrder'}
			<td><a href="index.php?module=Vendors&action=DetailView&record={$values.vendorid}">{$values.vendorname}</a></td>
		{else}
			<td><a href="index.php?module=Accounts&action=DetailView&record={$values.accountid}">{$values.accountname}</a></td>
		{/if}
		<td>{$values.subtotal}</td>
		<td>{$values.total}</td>
	</tr>
	{/foreach}
</tbody>
</table>
</div>
