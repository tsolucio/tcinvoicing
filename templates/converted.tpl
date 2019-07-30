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

<table border=0 cellspacing=0 cellpadding=0 width=95% align=center>
   <tr>
	<td valign=top align=left >
		<table border=0 cellspacing=0 cellpadding=3 width=100% class="dvtContentSpace">
		   <tr>
			<td align=left>
				<table border=0 cellspacing=0 cellpadding=0 width=100%>
				   <tr>
					<td class="dvtSelectedCell" nowrap>
						{if $convertto == 'SalesOrder'}{$MOD.SO_CREATED}
						{elseif $convertto == 'Invoice'}{$MOD.INV_CREATED}
						{elseif $convertto == 'PurchaseOrder'}{$MOD.PO_CREATED}
						{/if}
					</td>
				   </tr>
				   <tr>
					<td style="padding:10px">
						<!-- General details -->
						<table border=0 cellspacing=0 cellpadding=0 width=100% class="small">
					      <tr>
							<td class="detailedViewHeader"><b>{$APP.Reference}</b></td>
							{if $convertto eq 'PurchaseOrder'}
								<td class="detailedViewHeader"><b>{$APP.SINGLE_Vendors}</b></td>
							{else}
								<td class="detailedViewHeader"><b>{$APP.SINGLE_Accounts}</b></td>
							{/if}
							<td class="detailedViewHeader"><b>{$APP.LBL_SUB_TOTAL}</b></td>
							<td class="detailedViewHeader"><b>{$APP.LBL_TOTAL}</b></td>
						  </tr>
						  {foreach item=values from=$documents}
						  <tr>
							<td class="dvtCellInfo"><a href="index.php?module={$convertto}&action=DetailView&record={$values.docid}">{$values.title}</a></td>
							{if $convertto eq 'PurchaseOrder'}
								<td class="dvtCellInfo"><a href="index.php?module=Vendors&action=DetailView&record={$values.vendorid}">{$values.vendorname}</a></td>
							{else}
								<td class="dvtCellInfo"><a href="index.php?module=Accounts&action=DetailView&record={$values.accountid}">{$values.accountname}</a></td>
							{/if}
							<td class="dvtCellInfo">{$values.subtotal}</td>
							<td class="dvtCellInfo">{$values.total}</td>
						  </tr>
						  {/foreach}
						</table>
					</td>
				   </tr>
				</table>
			</td>
		   </tr>
		</table>
	</td>
   </tr>
</table>
