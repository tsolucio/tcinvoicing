{*<!--
/*********************************************************************************
 *************************************************************************************************
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
<link rel="stylesheet" type="text/css" media="all" href="jscalendar/calendar-win2k-cold-1.css">
<script type="text/javascript" src="jscalendar/calendar.js"></script>
<script type="text/javascript" src="jscalendar/lang/calendar-{$CALENDAR_LANG}.js"></script>
<script type="text/javascript" src="jscalendar/calendar-setup.js"></script>
{literal}
<script type="text/javascript">
function setSelect(state) {
	var trows = document.InvoiceLines.totalrows.value;
	for (var i=0;i<trows;i++) {
		var obj = document.getElementById('il_'+i);
		if (!obj.disabled) obj.checked=state;
	}
}
function oneSelected() {
	var trows = document.InvoiceLines.totalrows.value;
	var i=0;
	var found = false;
	while (i<trows && !found) {
		var obj = document.getElementById('il_'+i);
		i++;
		found = obj.checked;
	}
	if (!found) {
		alert(alert_arr.SELECT);
	}
	return found;
}
function showHideStatus(sId,anchorImgId,sImagePath) {
	oObj = eval(document.getElementById(sId));
	if (oObj.style.display == 'block') {
		oObj.style.display = 'none';
		eval(document.getElementById(anchorImgId)).src =  'themes/images/inactivate.gif';
		eval(document.getElementById(anchorImgId)).alt = 'Display';
		eval(document.getElementById(anchorImgId)).title = 'Display';
	} else {
		oObj.style.display = 'block';
		eval(document.getElementById(anchorImgId)).src = 'themes/images/activate.gif';
		eval(document.getElementById(anchorImgId)).alt = 'Hide';
		eval(document.getElementById(anchorImgId)).title = 'Hide';
	}
}
</script>
<!-- overriding the pre-defined #company to avoid clash with vtiger_field in the view -->
<style type='text/css'>
#company {
	height: auto;
	width: 90%;
}
</style>
{/literal}

{include file='Buttons_List1.tpl'}

<form name="EditView" method="POST" action="index.php">
<input type="hidden" name="module" value="{$MODULE}">
<input type="hidden" name="record" value="{$ID}">
<input type="hidden" name="mode" value="{$MODE}">
<input type="hidden" name="action" value="index">
<input type="hidden" name="parenttab" value="{$CATEGORY}">
{*<!-- Contents -->*}
<table border=0 cellspacing=0 cellpadding=0 width=98% align=center>
   <tr>
	<td valign=top><img src="{'showPanelTopLeft.gif'|@vtiger_imageurl:$THEME}"></td>

	<td class="showPanelBg" valign=top width=100%>
		{*<!-- PUBLIC CONTENTS STARTS-->*}
		<div class="small" style="padding:20px">
			<span class="lvtHeaderText">{$MOD.ProcessTitle}</span> <br>
			<hr noshade size=1>
			<br> 
{*<!-- Account details tabs -->*}
<table border=0 cellspacing=0 cellpadding=0 width=95% align=center>
   <tr>
	<td valign=top align=left >
		<table border=0 cellspacing=0 cellpadding=3 width=100% class="dvtContentSpace">
		   <tr>

			<td align=left>
				{*<!-- content cache -->*}
		
				<table border=0 cellspacing=0 cellpadding=0 width=100%>
				   <tr>
					<td id ="autocom"></td>
				   </tr>
				   <tr>
					<td style="padding:10px">
						{assign var=tcheadtext value=$MOD.tcparams|replace:' ':''}
						<table border=0 cellspacing=0 cellpadding=0 width=100% class="small">
					      <tr>{strip}
						     <td colspan=4 class="dvInnerHeader">
							<div style="float:left;font-weight:bold;"><div style="float:left;"><a href="javascript:showHideStatus('tbl{$tcheadtext}','aid{$tcheadtext}','{$IMAGE_PATH}');">
								<img id="aid{$tcheadtext}" src="{'activate.gif'|@vtiger_imageurl:$THEME}" style="border: 0px solid #000000;" alt="Hide" title="Hide"/>
								</a></div><b>&nbsp;
						        	{$MOD.tcparams}
	  			     			</b></div>
						     </td>{/strip}
						  </tr>
				  </table>
		<div style="width:auto;display:block;" id="tbl{$tcheadtext}" >
		  <table border=0 cellspacing=0 cellpadding=0 width="100%" class="small">
	      <tr>
			<td width="20%" class="dvtCellLabel" align=right>
				<font color="red">*</font>{$APP.LBL_START_DATE}
			</td>
			<td width="30%" align=left class="dvtCellInfo">
				<input name="start_date" tabindex="5" id="jscal_field_start_date" type="text" style="border:1px solid #bababa;" size="11" maxlength="10" value="{$start_date_val}">
				<img src="{'btnL3Calendar.gif'|@vtiger_imageurl:$THEME}" id="jscal_trigger_start_date">
				<br><font size=1><em old="(yyyy-mm-dd)">({$dateStr})</em></font>
				<script type="text/javascript" id='massedit_calendar_start_date'>
					Calendar.setup ({ldelim}
						inputField : "jscal_field_start_date", ifFormat : "{$dateFormat}", showsTime : false, button : "jscal_trigger_start_date", singleClick : true, step : 1
					{rdelim})
				</script>
			</td>
			<td width="20%" class="dvtCellLabel" align=right>
				{$APP.LBL_SELECT_USER_BUTTON_LABEL}
			</td>
			<td width="30%" align=left class="dvtCellInfo">
				<select name="assigned_user_id" class="small" tabindex="10">
				{foreach key=key_one item=arr from=$USER_ARRAY}
					{foreach key=sel_value item=value from=$arr}
						<option value="{$key_one}" {$value}>{$sel_value}</option>
					{/foreach}
				{/foreach}
				</select>
			</td>
		  </tr>
	      <tr>
			<td width="20%" class="dvtCellLabel" align=right>
				<font color="red">*</font>{$APP.LBL_END_DATE}
			</td>
			<td width="30%" align=left class="dvtCellInfo">
				<input name="end_date" tabindex="15" id="jscal_field_end_date" type="text" style="border:1px solid #bababa;" size="11" maxlength="10" value="{$end_date_val}">
				<img src="{'btnL3Calendar.gif'|@vtiger_imageurl:$THEME}" id="jscal_trigger_end_date">
				<br><font size=1><em old="(yyyy-mm-dd)">({$dateStr})</em></font>
				<script type="text/javascript" id='massedit_calendar_end_date'>
					Calendar.setup ({ldelim}
						inputField : "jscal_field_end_date", ifFormat : "{$dateFormat}", showsTime : false, button : "jscal_trigger_end_date", singleClick : true, step : 1
					{rdelim})
				</script>
			</td>
			<td width="20%" class="dvtCellLabel" align=right>
				<select id="parentid_type" class="small" name="parentid_type" onChange='document.EditView.parentid_display.value=""; document.EditView.parentid.value="";'>
					<option value="Accounts" {$Accountselected}>{$APP.SINGLE_Accounts}</option>
					<option value="Contacts" {$Contactselected}>{$APP.SINGLE_Contacts}</option>
					<option value="Vendors" {$Vendorsselected}>{$APP.SINGLE_Vendors}</option>
				</select>
			</td>
			<td width="30%" align=left class="dvtCellInfo">
				<input name="parentid" value="{$parentid}" id="parentid" type="hidden">
				<input name="parentid_display" id="parentid_display" readonly="readonly" style="border: 1px solid rgb(186, 186, 186);" value="{$parentid_display}" type="text">&nbsp;
				<img src="{'select.gif'|@vtiger_imageurl:$THEME}" tabindex="20" alt="Select" title="Select" onclick='return window.open("index.php?module="+document.EditView.parentid_type.value+"&action=Popup&html=Popup_picker&form=vtlibPopupView&forfield=parentid","test","width=640,height=602,resizable=0,scrollbars=0,top=150,left=200");' style="cursor: pointer;" align="absmiddle">&nbsp;
				<input src="{'clear_field.gif'|@vtiger_imageurl:$THEME}" alt="Clear" title="Clear" onclick="this.form.parentid.value=''; this.form.parentid_display.value=''; return false;" style="cursor: pointer;" align="absmiddle" type="image">
			</td>
		  </tr>
	      <tr>
			<td width="20%" class="dvtCellLabel" align=right>
				{$MOD.Invoiced}
			</td>
			<td width="30%" align=left class="dvtCellInfo">
				<select name="invoiced" class="small" tabindex="25">
					<option value="0" {$selnotinvoiced}>{$MOD.NotInvoiced}</option>
					<option value="1" {$selinvoiced}>{$MOD.Invoiced}</option>
					<option value="2" {$selbothinvoiced}>{$MOD.BothInvoiced}</option>
				</select>
			</td>
			<td width="20%" class="dvtCellLabel" align=right>
			   {$MOD.ShowInvoiceable}
			</td>
			<td width="30%" align=left class="dvtCellInfo">
			<INPUT TYPE=CHECKBOX NAME="showinvoiceable" {$showinvchecked}>
			</td>
		  </tr>
									   <tr>
										<td  colspan=4 style="padding:5px">
											<div align="center">
			                                	<input title="{$APP.LBL_SEARCH_BUTTON_TITLE}" accessKey="{$APP.LBL_SEARCH_BUTTON_KEY}" class="crmbutton small save" type="submit" name="button" value="  {$APP.LBL_SEARCH_BUTTON_LABEL}  " style="width:70px" >
											</div>
										</td>
									   </tr>
									</table>
								</div>
						{assign var=ivheadtext value=$MOD.invoiceparams|replace:' ':''}
						<table border=0 cellspacing=0 cellpadding=0 width=100% class="small">
						  <tr><td colspan=4>&nbsp;</td></tr>
					      <tr>{strip}
						     <td colspan=4 class="dvInnerHeader">
							<div style="float:left;font-weight:bold;"><div style="float:left;"><a href="javascript:showHideStatus('tbl{$ivheadtext}','aid{$ivheadtext}','{$IMAGE_PATH}');">
							<img id="aid{$ivheadtext}" src="{'inactivate.gif'|@vtiger_imageurl:$THEME}" style="border: 0px solid #000000;" alt="Display" title="Display"/>
								</a></div><b>&nbsp;
						        	{$MOD.invoiceparams}
	  			     			</b></div>
						     </td>{/strip}
						  </tr>
				  </table>
		<div style="width:auto;display:none;" id="tbl{$ivheadtext}" >
		  <table border=0 cellspacing=0 cellpadding=0 width="100%" class="small">
	      <tr>
			<td width="20%" class="dvtCellLabel" align=right>
				{$MOD.Invoiceper}
			</td>
			<td width="30%" align=left class="dvtCellInfo">
				<select name="invoiceper" class="small" tabindex="30">
					<option value="0" {$selinvoicetime}>{$MOD.InvoiceperTime}</option>
					<option value="1" {$selinvoiceunit}>{$MOD.InvoiceperUnit}</option>
					<option value="2" {$selinvoiceboth}>{$MOD.InvoiceperBoth}</option>
				</select>
			</td>
			<td width="20%" class="dvtCellLabel" align=right>
				{$MOD.TCGrouping}
			</td>
			<td width="30%" align=left class="dvtCellInfo">
				<select name="tcgrouping" class="small" tabindex="35">
					<option value="0" {$seltcgrpnone}>{$MOD.tcGrpNone}</option>
					<option value="1" {$seltcgrppdo}>{$MOD.tcGrpPDO}</option>
					<option value="2" {$seltcgrpre}>{$MOD.tcGrpRE}</option>
				</select>
			</td>
		  </tr>
	      <tr>
			<td width="20%" class="dvtCellLabel" align=right>
				{$APP.LBL_TAX_MODE}
			</td>
			<td width="30%" align=left class="dvtCellInfo">
				<select name="taxmode" class="small" tabindex="40">
					<option value="0" {$seltmgrp}>{$APP.group}</option>
					<option value="1" {$seltmind}>{$APP.individual}</option>
				</select>
			</td>
			<td width="20%" class="dvtCellLabel" align=right>
				{$MOD.pdoDesc}
			</td>
			<td width="30%" align=left class="dvtCellInfo">
				<select name="pdodesc" class="small" tabindex="45">
					<option value="0" {$selpdodnn}>{$MOD.pdoDescNone}</option>
					<option value="1" {$selpdodtc}>{$MOD.pdoDescTC}</option>
					<option value="2" {$selpdodre}>{$MOD.pdoDescRE}</option>
				</select>
			</td>
		  </tr>
	      <tr>
			<td width="20%" class="dvtCellLabel" align=right>
				{$MOD.EntitiesInvoiced}
			</td>
			<td width="30%" align=left class="dvtCellInfo">
				<select name="toinvoice[]" class="small" tabindex="50" size=4 multiple="multiple">
					{html_options options=$invModules selected=$invMSelect}
				</select>
			</td>
			<td width="20%" class="dvtCellLabel" align=right>
			  {$MOD.AssignInvoiceTo}
			</td>
			<td width="30%" align=left class="dvtCellInfo">
				<select name="assignto" class="small" tabindex="10">
				{foreach key=key_one item=arr from=$ASSIGNEDTO_ARRAY}
					{foreach key=sel_value item=value from=$arr}
						<option value="{$key_one}" {$value}>{$sel_value}</option>
					{/foreach}
				{/foreach}
				</select>
			</td>
		  </tr>
	      {if $rel2contact neq 'no' && $bill2contact neq 'no'}
	      <tr>
		<td width="20%" class="dvtCellLabel" align=right>
			   {$MOD.rel2contact}
		</td>
		<td width="30%" align=left class="dvtCellInfo">
			<INPUT TYPE=CHECKBOX NAME="rel2contact" {$rel2contact}>
		</td>
		<td width="20%" class="dvtCellLabel" align=right>
			   {$MOD.bill2contact}
		</td>
		<td width="30%" align=left class="dvtCellInfo">
			<INPUT TYPE=CHECKBOX NAME="bill2contact" {$bill2contact}>
		</td>
		
	      </tr>
	      {else}
	      <tr>
		<td width="20%" class="dvtCellLabel" align=right>
			   {$MOD.rel2contact}
		</td>
		<td width="30%" align=left class="dvtCellInfo">
			<INPUT TYPE=CHECKBOX readonly="readonly" NAME="rel2contact" {$rel2contact}>
		</td>
		<td width="20%" class="dvtCellLabel" align=right>
			   {$MOD.bill2contact}
		</td>
		<td width="30%" align=left class="dvtCellInfo">
			<INPUT TYPE=CHECKBOX readonly="readonly" NAME="bill2contact" {$bill2contact}>
		</td>
		
	      </tr>
              {/if}
	      <tr>
		<td width="20%" class="dvtCellLabel" align=right>
			   {$MOD.tcsubject}
		</td>
		<td width="30%" align=left class="dvtCellInfo">
		    <input name="tcsubject" id="tcsubject" style="border: 1px solid rgb(186, 186, 186);" value="{$tcsubject}" type="text">
		</td>
		<td width="20%" class="dvtCellLabel" align=right>
			{'Simple_Time_Filter'|@getTranslatedString:'CustomView'}
		</td>
		<td width="30%" align=left class="dvtCellInfo">
			<select name="datespan" class="small">
				<option value="thisweek" {$seldstw}>{'Current Week'|@getTranslatedString:'CustomView'}</option>
				<option value="lastweek" {$seldslw}>{'Last Week'|@getTranslatedString:'CustomView'}</option>
				<option value="thismonth" {$seldstm}>{'Current Month'|@getTranslatedString:'CustomView'}</option>
				<option value="lastmonth" {$seldslm}>{'Last Month'|@getTranslatedString:'CustomView'}</option>
			</select>
		</td>
	      </tr>
		  <!-- tr>
			<td  colspan=4 style="padding:5px">
			 <div align="center">
               <input title="{$APP.LBL_UPDATE}" accessKey="U" class="crmbutton small save" type="submit" name="button" value="  {$APP.LBL_UPDATE}  " style="width:70px" >
			 </div>
			</td>
		  </tr -->
		  </table>
		  </div>
								</td>
							   </tr>
							</table>
						</td>
					   </tr>
					</table>
				</td>
			   </tr>
			</table>
		</div>
	</td>
	<td align=right valign=top><img src="{'showPanelTopRight.gif'|@vtiger_imageurl:$THEME}"></td>
   </tr>
</table>
</form>

<form name="InvoiceLines" method="POST" action="index.php">
<input type="hidden" name="module" value="{$MODULE}">
<input type="hidden" name="record" value="{$ID}">
<input type="hidden" name="mode" value="{$MODE}">
<input type="hidden" name="action" value="convertTo">
<input type="hidden" name="convertto" value="so">
<input type="hidden" name="parenttab" value="{$CATEGORY}">
<input type="hidden" name="totalrows" value="{$totalrows}">
{*<!-- Account details tabs -->*}
<table border=0 cellspacing=0 cellpadding=0 width=95% align=center>
   <tr>
	<td valign=top align=left >
		<table border=0 cellspacing=0 cellpadding=3 width=100% class="dvtContentSpace">
		   <tr>

			<td align=left>
				{*<!-- content cache -->*}
		
				<table border=0 cellspacing=0 cellpadding=0 width=100%>
				   <tr>
					<td id ="autocom"></td>
				   </tr>
				   <tr>
					<td style="padding:10px">
						<!-- General details -->
						<table border=0 cellspacing=0 cellpadding=0 width=100% class="small">
					      <tr>
							<td class="detailedViewHeader"><b>{$APP.LBL_START_DATE}</b></td>
							<td class="detailedViewHeader"><b>{$MOD.Entity}</b></td>
							<td class="detailedViewHeader"><b>{$MOD.RelatedTo}</b></td>
							<td class="detailedViewHeader"><b>{$APP.SINGLE_Users}</b></td>
							<td class="detailedViewHeader"><b><input name="il_all" type="checkbox" onclick='setSelect(this.checked)'>&nbsp;{$MOD.Timecontrol}</b></td>
							<td class="detailedViewHeader"><b>{$APP.SINGLE_Products}</b></td>
						  </tr>
						  {foreach key=row item=values from=$tcts}
						  <tr bgcolor="{if $values.invoiced}{$ILcolor}{else}{$nonILcolor}{/if}">
							<td class="dvtCellLabel"><b>{$values.fecha}</b></td>
							<td class="dvtCellInfo">{$values.relmod|@getTranslatedString}</td>
							<td class="dvtCellInfo"><a href="index.php?module={$values.relmod}&action=DetailView&record={$values.cuentaid}">{$values.cuenta}</a></td>
							<td class="dvtCellInfo">{$values.usuario}</td>
							<td class="dvtCellInfo"><input name="il_{$row}" id="il_{$row}" type="checkbox" value="{$values.tctsid}" {if !$values.invoiceable}disabled=true{/if}>&nbsp;<a href="index.php?module=Timecontrol&action=DetailView&record={$values.tctsid}">{$values.timeelement}</a></td>
							<td class="dvtCellInfo"><a href="index.php?module={$values.pdomod}&action=DetailView&record={$values.productid}">{$values.product}</a></td>
						  </tr>
						  {/foreach}
						   <tr>
							<td  colspan=6 style="padding:5px">
								<div align="center">
                                	<br>
									{if 'SalesOrder'|vtlib_isModuleActive}
                                	<input title="{$MOD.CONVERT_SALESORDER}" accessKey="S" class="crmbutton small save" type="submit" name="button" value="  {$MOD.CONVERT_SALESORDER}  " onclick="this.form.convertto.value='so'; return oneSelected();">
                                	&nbsp;&nbsp;&nbsp;&nbsp;
									{/if}
									{if 'Invoice'|vtlib_isModuleActive}
                                	<input title="{$MOD.CONVERT_INVOICE}" accessKey="V" class="crmbutton small save" type="submit" name="button" value="  {$MOD.CONVERT_INVOICE}  " onclick="this.form.convertto.value='in'; return oneSelected();">
									&nbsp;&nbsp;&nbsp;&nbsp;
									{/if}
									{if 'PurchaseOrder'|vtlib_isModuleActive}
									<input title="{$MOD.CONVERT_PURCHASEORDER}" accessKey="V" class="crmbutton small save" type="submit" name="button" value="  {$MOD.CONVERT_PURCHASEORDER}  " onclick="this.form.convertto.value='po'; return oneSelected();">
									&nbsp;&nbsp;&nbsp;&nbsp;
									{/if}
									{if 'Issuecards'|vtlib_isModuleActive}
									<input title="{$MOD.CONVERT_ISSUECARDS}" accessKey="V" class="crmbutton small save" type="submit" name="button" value="  {$MOD.CONVERT_ISSUECARDS}  " onclick="this.form.convertto.value='ic'; return oneSelected();">
									&nbsp;&nbsp;&nbsp;&nbsp;
									{/if}
								</div>
							</td>
						   </tr>
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
</form>
