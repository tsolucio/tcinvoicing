<?php
/*************************************************************************************************
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
*  Module       : Timecontrol Invoicing
*  Version      : 1.3
*  Author       : Joe Bordes JPL TSolucio, S. L.
*************************************************************************************************/

require_once 'include/utils/CommonUtils.php';
require_once 'Smarty_setup.php';

$smarty = new vtigerCRM_Smarty;

global $adb, $app_strings, $current_user;

// check for new column datespan
$result = $adb->pquery('show columns from trcinvoicing like ?', array('datespan'));
if (!($adb->num_rows($result))) {
	$adb->pquery("ALTER TABLE trcinvoicing ADD datespan VARCHAR(100)", array());
}

if (isset($_REQUEST['invoiceper']) &&
	isset($_REQUEST['tcgrouping']) &&
	isset($_REQUEST['taxmode']) &&
	isset($_REQUEST['assignto']) &&
	isset($_REQUEST['pdodesc'])
) {
	if (!empty($_REQUEST['toinvoice']) && is_array($_REQUEST['toinvoice'])) {
		$toinvoice=implode('##', $_REQUEST['toinvoice']);
	} else {
		$toinvoice = '';
	}
	$adb->pquery(
		'update trcinvoicing set invoiceper=?,taxmode=?,tcgrouping=?,productdesc=?,invmodules=?,assignto=?,rel2contact=?,bill2contact=?,tcsubject=?,datespan=?',
		array($_REQUEST['invoiceper'], $_REQUEST['taxmode'], $_REQUEST['tcgrouping'], $_REQUEST['pdodesc'], $toinvoice, $_REQUEST['assignto'],
			empty($_REQUEST['rel2contact']) ? '' : $_REQUEST['rel2contact'], empty($_REQUEST['bill2contact']) ? '' : $_REQUEST['bill2contact'],
			$_REQUEST['tcsubject'], $_REQUEST['datespan'])
	);
}

$invprmrs=$adb->query('select * from trcinvoicing');
$invoiceper=$adb->query_result($invprmrs, 0, 'invoiceper');
$taxmode=$adb->query_result($invprmrs, 0, 'taxmode');
$tcgrouping=$adb->query_result($invprmrs, 0, 'tcgrouping');
$productdesc=$adb->query_result($invprmrs, 0, 'productdesc');
$assignto=$adb->query_result($invprmrs, 0, 'assignto');
$rel2contact=$adb->query_result($invprmrs, 0, 'rel2contact');
$bill2contact=$adb->query_result($invprmrs, 0, 'bill2contact');
$tcsubject=$adb->query_result($invprmrs, 0, 'tcsubject');
$datespan=$adb->query_result($invprmrs, 0, 'datespan');
$toinvoice=explode('##', $adb->query_result($invprmrs, 0, 'invmodules'));
// If we are coming from HelpDesk/Project with ttid to invoice we activate that module
$setype = '';
if (!empty($_REQUEST['onlyttid'])) {
	$setype = getSalesEntityType($_REQUEST['onlyttid']);
	if (!in_array($setype, $toinvoice)) {
		$toinvoice[]=$setype;
		if ($setype!='HelpDesk') {
			if (!in_array('ProjectTask', $toinvoice)) {
				$toinvoice[]='ProjectTask';
			}
			if (!in_array('ProjectMilestone', $toinvoice)) {
				$toinvoice[]='ProjectMilestone';
			}
		}
	}
}
if (!empty($_REQUEST['onlyttid'])) {
	// all Timecontrol records since createdtime of TT to today
	$ttctime=$adb->getOne('select DATE_FORMAT(createdtime,\'%Y-%m-%d\') from vtiger_crmentity where crmid='.$_REQUEST['onlyttid']);
	$date = new DateTimeField($ttctime);
	$_REQUEST["start_date"] = $date->getDisplayDate($current_user); // createdtime
	$date = new DateTimeField(date('Y-m-d'));
	$_REQUEST["end_date"] = $date->getDisplayDate($current_user);  // today
	// Timecontrol for all users
	$_REQUEST["assigned_user_id"]='0';
}
$smarty->assign('selinvoicetime', ($invoiceper==0 ? 'selected' : ''));
$smarty->assign('selinvoiceunit', ($invoiceper==1 ? 'selected' : ''));
$smarty->assign('selinvoiceboth', ($invoiceper==2 ? 'selected' : ''));
$smarty->assign('seltcgrpnone', ($tcgrouping==0 ? 'selected' : ''));
$smarty->assign('seltcgrppdo', ($tcgrouping==1 ? 'selected' : ''));
$smarty->assign('seltcgrpre', ($tcgrouping==2 ? 'selected' : ''));
$smarty->assign('seltmgrp', ($taxmode==0 ? 'selected' : ''));
$smarty->assign('seltmind', ($taxmode==1 ? 'selected' : ''));
$smarty->assign('selpdodnn', ($productdesc==0 ? 'selected' : ''));
$smarty->assign('selpdodtc', ($productdesc==1 ? 'selected' : ''));
$smarty->assign('selpdodre', ($productdesc==2 ? 'selected' : ''));
$smarty->assign('seldstw', ($datespan=='thisweek' ? 'selected' : ''));
$smarty->assign('seldslw', ($datespan=='lastweek' ? 'selected' : ''));
$smarty->assign('seldstm', ($datespan=='thismonth' ? 'selected' : ''));
$smarty->assign('seldslm', ($datespan=='lastmonth' ? 'selected' : ''));
$smarty->assign('tcsubject', $tcsubject);
//Check if invoice_ref exist in vtiger_contactdetails
$repcols=$adb->getColumnNames('vtiger_contactdetails');
if (in_array('invoice_ref', $repcols)) {
	$smarty->assign('rel2contact', ($rel2contact !=null ? 'checked' : ''));
	$smarty->assign('bill2contact', ($bill2contact !=null ? 'checked' : ''));
} else {
	$smarty->assign('rel2contact', 'no');
	$smarty->assign('bill2contact', 'no');
}
$invModules=  array (
	'Contacts' => getTranslatedString('Contacts', 'Contacts'),
	'Accounts' => getTranslatedString('Accounts', 'Accounts'),
	'Vendors' => getTranslatedString('Vendors', 'Vendors'),
	'HelpDesk' => getTranslatedString('HelpDesk', 'HelpDesk'),
	'Project' => getTranslatedString('Project', 'Project'),
	'ProjectTask' => getTranslatedString('ProjectTask', 'ProjectTask'),
	'ProjectMilestone' => getTranslatedString('ProjectMilestone', 'ProjectMilestone'),
	'Quotes' => getTranslatedString('Quotes', 'Quotes'),
	'SalesOrder' => getTranslatedString('SalesOrder', 'SalesOrder'),
	'Invoice' => getTranslatedString('Invoice', 'Invoice'),
	'Potentials' => getTranslatedString('Potentials', 'Potentials'),
);
$smarty->assign('invModules', $invModules);
$smarty->assign('invMSelect', $toinvoice);

$_REQUEST['invoiced'] = (int)GlobalVariable::getVariable('TCInv_Default_Invoiced_State', 2);
$_REQUEST['showinvoiceable'] = (int)GlobalVariable::getVariable('TCInv_Default_Show_Invoiceable', 0);

$startdate = isset($_REQUEST["start_date"]) ? $_REQUEST["start_date"] : '';
$enddate = isset($_REQUEST["end_date"]) ? $_REQUEST["end_date"] : '';
$userid = isset($_REQUEST["assigned_user_id"]) ? $_REQUEST["assigned_user_id"] : '0';  // default all
$parentid = isset($_REQUEST["parentid"]) ? $_REQUEST["parentid"] : '';
$parent = isset($_REQUEST["parentid_type"]) ? $_REQUEST["parentid_type"] : '';
$parent_display = isset($_REQUEST["parentid_display"]) ? $_REQUEST["parentid_display"] : '';
$smarty->assign('Accountselected', ($parent=='Accounts' ? 'selected' : ''));
$smarty->assign('Contactselected', ($parent=='Contacts' ? 'selected' : ''));
$smarty->assign('Vendorsselected', ($parent=='Vendors' ? 'selected' : ''));
$smarty->assign('parentid', $parentid);
$smarty->assign('parentid_display', $parent_display);
$invoiced = isset($_REQUEST["invoiced"]) ? $_REQUEST["invoiced"] : 2;  // By default both=2
$smarty->assign('selnotinvoiced', ($invoiced==0 ? 'selected' : ''));
$smarty->assign('selinvoiced', ($invoiced==1 ? 'selected' : ''));
$smarty->assign('selbothinvoiced', ($invoiced==2 ? 'selected' : ''));
$showinvchecked = empty($_REQUEST["showinvoiceable"]) ? 0 : 1;
$smarty->assign('showinvchecked', ($showinvchecked!=0 ? 'checked' : ''));

$starttime = 0;
$endtime = 0;

if ($startdate == "") {
	switch ($datespan) {
		case 'thismonth':
			$starttime = mktime(0, 0, 0, date("m"), "01", date("Y"));
			break;
		case 'lastmonth':
			$starttime = mktime(0, 0, 0, date("m")-1, "01", date("Y"));
			break;
		case 'lastweek':
			$starttime = (date('w')==1 ? strtotime("last monday") : strtotime("-1 week monday"));
			break;
		default: // this week
			$starttime = (date('w')==1 ? strtotime("today") : strtotime("last monday"));
			break;
	}
	$date = new DateTimeField(date('Y-m-d', $starttime));
	$startdate = $date->getDisplayDate($current_user);
} else {
	list($y,$m,$d)=explode('-', getValidDBInsertDateValue($startdate));
	$starttime = mktime(0, 0, 0, $m, $d, $y);
}
if ($enddate == "") {
	switch ($datespan) {
		case 'thismonth':
			$endtime = mktime(0, 0, 0, date("m"), date("t"), date("Y"));
			break;
		case 'lastmonth':
			$endtime = mktime(0, 0, 0, date("m")-1, date("t", strtotime("last month")), date("Y"));
			break;
		case 'lastweek':
			$endtime = (date('w')==0 ? strtotime("last sunday") : strtotime("-1 week sunday"));
			break;
		default: // this week
			$endtime = (date('w')==0 ? strtotime("today") : strtotime("next sunday"));
			break;
	}
	$date = new DateTimeField(date('Y-m-d', $endtime));
	$enddate = $date->getDisplayDate($current_user);
} else {
	list($y,$m,$d)=explode('-', getValidDBInsertDateValue($enddate));
	$endtime = mktime(0, 0, 0, $m, $d, $y);
}
if ($userid == "" || !preg_match("/^[0-9]+$/", $userid)) {
	$userid = $current_user->id;
}

$query="select * from vtiger_users where deleted=0";
$result = $adb->pquery($query, array());
$num_rows=$adb->num_rows($result);
$user_details=array(0=>array(getTranslatedString('SHOW_ALL')=>''));
$assignedto_details=array(0=>array(getTranslatedString('AccountUser', 'Timecontrol')=>''));
for ($i=0; $i<$num_rows; $i++) {
	$user=$adb->query_result($result, $i, 'id');
	$username=getUserFullName($user).' ('.$adb->query_result($result, $i, 'user_name').')';
	$user_details[$user]=array($username=>($userid==$user ? 'selected' : ''));
	$assignedto_details[$user]=array($username=>($assignto==$user ? 'selected' : ''));
}

$query  = "SELECT tc.*, u.id, ce.description ";
$qcond  = "FROM vtiger_timecontrol tc ";
$qcond .= "INNER JOIN vtiger_crmentity ce ON tc.timecontrolid = ce.crmid ";
$qcond .= "LEFT JOIN vtiger_users u ON ce.smownerid = u.id ";
$qcond .= "LEFT JOIN vtiger_account a ON a.accountid = tc.relatedto ";
$qcond .= "LEFT JOIN vtiger_contactdetails c ON c.contactid = tc.relatedto ";
$qcond .= "LEFT JOIN vtiger_vendor ON vtiger_vendor.vendorid = tc.relatedto ";
if (in_array('HelpDesk', $toinvoice)) {
	$qcond .= "LEFT JOIN vtiger_troubletickets tt ON tt.ticketid = tc.relatedto ";
}
if (in_array('Quotes', $toinvoice)) {
	$qcond .= "LEFT JOIN vtiger_quotes qt ON qt.quoteid = tc.relatedto ";
}
if (in_array('SalesOrder', $toinvoice)) {
	$qcond .= "LEFT JOIN vtiger_salesorder so ON so.salesorderid = tc.relatedto ";
}
if (in_array('Invoice', $toinvoice)) {
	$qcond .= "LEFT JOIN vtiger_invoice iv ON iv.invoiceid = tc.relatedto ";
}
if (in_array('Potentials', $toinvoice)) {
	$qcond .= "LEFT JOIN vtiger_potential pt ON pt.potentialid = tc.relatedto ";
}
if (in_array('Project', $toinvoice)) {
	$qcond .= "LEFT JOIN vtiger_project pr ON pr.projectid = tc.relatedto ";
}
if (in_array('ProjectTask', $toinvoice)) {
	$qcond .= "LEFT JOIN vtiger_projecttask prt ON prt.projecttaskid = tc.relatedto ";
	$qcond .= "LEFT JOIN vtiger_project pr_pt ON pr_pt.projectid = prt.projectid ";
}
if (in_array('ProjectMilestone', $toinvoice)) {
	$qcond .= "LEFT JOIN vtiger_projectmilestone prm ON prm.projectmilestoneid = tc.relatedto ";
	$qcond .= "LEFT JOIN vtiger_project pr_pm ON pr_pm.projectid = prm.projectid ";
}
$qcond .= "WHERE ce.deleted = 0 ";
$qcond .= "AND tc.date_start between '".date("Y-m-d", $starttime)."' and '".date("Y-m-d", $endtime)."' ";
if ($userid > 0) {
	$qcond .= " AND ce.smownerid = '".$userid."' ";
}
if (!empty($parentid)) {
	if ($parent == 'Accounts') {
		$qcond .= " AND (a.accountid = '$parentid' or a.accountname='$parent_display' or c.accountid=$parentid ";
		if (in_array('HelpDesk', $toinvoice)) {
			$qcond .= "or tt.parent_id=$parentid ";
		}
		if (in_array('Quotes', $toinvoice)) {
			$qcond .= "or qt.accountid=$parentid ";
		}
		if (in_array('SalesOrder', $toinvoice)) {
			$qcond .= "or so.accountid=$parentid ";
		}
		if (in_array('Invoice', $toinvoice)) {
			$qcond .= "or iv.accountid=$parentid ";
		}
		if (in_array('Potentials', $toinvoice)) {
			$qcond .= "or pt.related_to=$parentid ";
		}
		if (in_array('Project', $toinvoice)) {
			$qcond .= "or pr.linktoaccountscontacts=$parentid ";
		}
		if (in_array('ProjectTask', $toinvoice)) {
			$qcond .= "or pr_pt.linktoaccountscontacts=$parentid ";
		}
		if (in_array('ProjectMilestone', $toinvoice)) {
			$qcond .= "or pr_pm.linktoaccountscontacts=$parentid ";
		}
		$qcond .= ') ';
	} elseif ($parent == 'Vendors') {
		$qcond .= " AND (vtiger_vendor.vendorid = '$parentid' or vtiger_vendor.vendorname='$parent_display') ";
	} else {
		$qcond .= " AND (c.contactid = '$parentid' or c.lastname='$parent_display' ";
		if (in_array('HelpDesk', $toinvoice)) {
			$qcond .= "or tt.parent_id=$parentid ";
		}
		if (in_array('Quotes', $toinvoice)) {
			$qcond .= "or qt.contactid=$parentid ";
		}
		if (in_array('SalesOrder', $toinvoice)) {
			$qcond .= "or so.contactid=$parentid ";
		}
		if (in_array('Invoice', $toinvoice)) {
			$qcond .= "or iv.contactid=$parentid ";
		}
		if (in_array('Potentials', $toinvoice)) {
			$qcond .= "or pt.related_to=$parentid ";
		}
		if (in_array('Project', $toinvoice)) {
			$qcond .= "or pr.linktoaccountscontacts=$parentid ";
		}
		if (in_array('ProjectTask', $toinvoice)) {
			$qcond .= "or pr_pt.linktoaccountscontacts=$parentid ";
		}
		if (in_array('ProjectMilestone', $toinvoice)) {
			$qcond .= "or pr_pm.linktoaccountscontacts=$parentid ";
		}
		$qcond .= ') ';
	}
}
if ($invoiced==0) {
	$qcond .= " AND (tc.invoiced=0 or tc.invoiced is null) ";
} elseif ($invoiced==1) {
	$qcond .= " AND tc.invoiced=1 ";
}
// Invoice only this ticketid
if ($setype == 'HelpDesk' && !empty($_REQUEST['onlyttid'])) {
	$qcond .= " AND tt.ticketid=".$_REQUEST['onlyttid'];
}
if ($setype == 'Project' && !empty($_REQUEST['onlyttid'])) {
	$qcond .= " AND (pr.projectid=".$_REQUEST['onlyttid'].' or prm.projectid='.$_REQUEST['onlyttid'].' or prt.projectid='.$_REQUEST['onlyttid'].')';
}
$query .= $qcond." ORDER BY date_start,accountname,c.lastname,c.firstname,u.last_name,u.first_name,vtiger_vendor.vendorname";
$result = $adb->query($query);
$total = 0;
$tcts = array();
while ($result && ($row = $adb->fetch_array($result))) {
	//$invoiceable = (!is_null($row['accountid']) || !is_null($row['contactid']) || !is_null($row['ticketid'])) && $row['product_id']!=0 && $row['totaltime']!='';
	$relmod=getSalesEntityType($row['relatedto']);
	$invoiceable = in_array($relmod, $toinvoice) && $row['product_id']!=0 && ($row['totaltime']!='' || $row['tcunits'] > 0);
	if ($showinvchecked && !$invoiceable) {
		continue;
	}
	$dt = $row['date_end'];
	$te = $row['time_end'];
	$time_end = DateTimeField::convertToUserTimeZone($dt.' '.DateTimeField::sanitizeTime($te));
	$time_end = $time_end->format('H:i:s');
	$dt = $row['date_start'];
	$ts = $row['time_start'];
	$time_start = DateTimeField::convertToUserTimeZone($dt.' '.DateTimeField::sanitizeTime($ts));
	$time_start = $time_start->format('H:i:s');
	$date = new DateTimeField($row['date_start']);
	$data = array(
		'invoiceable' => $invoiceable,
		'invoiced' => $row['invoiced'],
		'fecha' => $date->getDisplayDate($current_user),
		'cuentaid' => $row['relatedto'],
		'usuario' => getUserFullName($row['id']),
		'tctsid' => $row['timecontrolid'],
		'timeelement' => $time_start.' - '.$time_end.'='.$row['totaltime'].' / '.$row['tcunits'],
	);
	if ($row['product_id']) {
		$pdomod=getSalesEntityType($row['product_id']);
		$prod=getEntityName($pdomod, array($row['product_id']));
		$data['productid'] = $row['product_id'];
		$data['pdomod'] = $pdomod;
		$data['product'] = $prod[$row['product_id']];
	}
	if ($row['relatedto']) {
		$isdel=$adb->getone('select deleted from vtiger_crmobject where crmid='.$row['relatedto']);
		if ($isdel) {
			$ename='<font color=red>DELETED!!!</font>';
		} else {
			$seqfld=getModuleSequenceField($relmod);
			$relm = CRMEntity::getInstance($relmod);
			$relm->retrieve_entity_info($row['relatedto'], $relmod);
			$enum=$relm->column_fields[$seqfld['column']];
			$ename=getEntityName($relmod, array($row['relatedto']));
			$ename=$ename[$row['relatedto']];
		}
		$data['relmod'] = $relmod;
		$data['cuenta'] = $ename;
	}
	$tcts[] = $data;
}

$smarty->assign('MODULE', $currentModule);
$smarty->assign('SINGLE_MOD', getTranslatedString('SINGLE_'.$currentModule));
$smarty->assign('CATEGORY', '');
$smarty->assign('ID', '');
$smarty->assign('MODE', '');
$smarty->assign('CHECK', Button_Check($currentModule));
$smarty->assign('THEME', $theme);
$smarty->assign('IMAGE_PATH', "themes/$theme/images/");
$smarty->assign("USER_ARRAY", $user_details);
$smarty->assign("ASSIGNEDTO_ARRAY", $assignedto_details);
$smarty->assign("start_date_val", $startdate);
$smarty->assign("end_date_val", $enddate);
$dat_fmt = $current_user->date_format;
$smarty->assign("dateStr", $dat_fmt);
$smarty->assign("dateFormat", (($dat_fmt == 'dd-mm-yyyy')?'%d-%m-%Y':(($dat_fmt == 'mm-dd-yyyy')?'%m-%d-%Y':(($dat_fmt == 'yyyy-mm-dd')?'%Y-%m-%d':''))));
$smarty->assign("CALENDAR_LANG", $app_strings['LBL_JSCALENDAR_LANG']);
$smarty->assign("MOD", $mod_strings);
$smarty->assign("APP", $app_strings);
$smarty->assign('ILcolor', '#e1ffd7');
$smarty->assign('nonILcolor', '#ffdbdb');
$smarty->assign('tcts', $tcts);
$smarty->assign('totalrows', count($tcts));
$smarty->assign('tcinv_origin', !empty($_REQUEST['onlyttid']) ? $_REQUEST['onlyttid'] : '');

$smarty->display("modules/TimecontrolInv/invoicing.tpl");
?>
