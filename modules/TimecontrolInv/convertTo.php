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
require_once 'modules/Emails/mail.php';
require_once 'modules/Accounts/Accounts.php';
require_once 'modules/Contacts/Contacts.php';
require_once 'modules/Vendors/Vendors.php';

$smarty = new vtigerCRM_Smarty;

$invprmrs=$adb->query('select * from trcinvoicing');
if ($adb->query_result($invprmrs, 0, 'tcsubject') != '') {
    $reference = $adb->query_result($invprmrs, 0, 'tcsubject');
} else {
    $reference = getTranslatedString('Batch Timecontrol', $currentModule);
}
$invoiceper=$adb->query_result($invprmrs, 0, 'invoiceper');
$taxtype=($adb->query_result($invprmrs, 0, 'taxmode')==0 ? 'group' : 'individual');
//$taxtype = 'group'; // individual
$tcgrouping=$adb->query_result($invprmrs, 0, 'tcgrouping');
$productdesc=$adb->query_result($invprmrs, 0, 'productdesc');
// Assign invoice/salesorder to
$assignto=$adb->query_result($invprmrs, 0, 'assignto');
$toinvoice=explode('##', $adb->query_result($invprmrs, 0, 'invmodules'));
//Related with contact
$rel2contact=$adb->query_result($invprmrs, 0, 'rel2contact');
$bill2contact=$adb->query_result($invprmrs, 0, 'bill2contact');

global $app_strings,$default_theme,$default_charset;
global $current_user,$currentModule,$adb,$log;

$tcts=array();
for ($i=0; $i<$_REQUEST['totalrows']; $i++) {
    if (!empty($_REQUEST["il_$i"])) {
        $tcts[]=$_REQUEST["il_$i"];
    }
}
$rets = array();
for ($a = 0; $a < $_REQUEST['totalretainers']; $a++) {
    if (!empty($_REQUEST['retainer_' . $a])) { // Only use retainers that were selected
        $rets[] = $_REQUEST['retainer_' . $a];
    }
}
$advs = array();
for ($a = 0; $a < $_REQUEST['totaladvances']; $a++) {
    if (!empty($_REQUEST['advance_' . $a])) { // Only use advances that were selected
        $advs[] = $_REQUEST['advance_' . $a];
    }
}
switch ($_REQUEST['convertto']) {
    case 'so':
        $convertto = 'SalesOrder';
        break;
    case 'in':
        $convertto = 'Invoice';
        break;
    case 'po':
        $convertto = 'PurchaseOrder';
        break;
    case 'ic':
        $convertto = 'Issuecards';
        break;
}
$documents=array();
if (count($tcts) > -1) {
    require_once "modules/$convertto/$convertto.php";
    $focus= new $convertto();
    // Obtain the set of different accounts to invoice to
    $setoftrc= count($tcts) > 0 ? implode(',', $tcts) : 1;
    $setoftrans=implode(',', array_merge($rets, $advs));
    if ($convertto == 'PurchaseOrder') {
        $relaccounts="
            select distinct relatedto as relacc
            from vtiger_timecontrol
            inner join vtiger_crmentity on
            (crmid=relatedto and setype='Vendors' and deleted=0)
            where timecontrolid in ($setoftrc) ";

        $invoiceto=$adb->query($relaccounts);
        while ($ito=$adb->fetch_array($invoiceto)) {
            if (empty($ito['relacc'])) {
                continue;
            }

            $vendorid=$ito['relacc'];
            $focus->column_fields['vendor_id'] = $vendorid;
            $_REQUEST['assigntype'] = 'U';
            $focus->column_fields['description'] = '';
            $date = new DateTimeField();
            $now = $date->getDisplayDate();
            $now = $date->convertToDBFormat($now);
            $focus->column_fields['duedate'] = $now;
            $focus->column_fields['currency_id'] = 1;
            $focus->column_fields['postatus']='Created';
            $cur_sym_rate = getCurrencySymbolandCRate(1);
            $focus->column_fields['conversion_rate'] = $cur_sym_rate['rate'];
            // Change next line for any special logic of PO reference generation, e.g. automatic sequential number
            $focus->column_fields['subject'] = decode_html($reference);
            $vnd_focus = new Vendors();
            $vnd_focus->retrieve_entity_info($vendorid, 'Vendors');
            if ($assignto==0) {  // Use account's user
                $focus->column_fields['assigned_user_id'] = $vnd_focus->column_fields['assigned_user_id'];
            } else {
                $focus->column_fields['assigned_user_id'] = $assignto;
            }
            $focus->column_fields['bill_street'] = (empty($vnd_focus->column_fields['street']) ? '-' : decode_html($vnd_focus->column_fields['street']));
            $focus->column_fields['bill_city'] = decode_html($vnd_focus->column_fields['city']);
            $focus->column_fields['bill_state'] = decode_html($vnd_focus->column_fields['state']);
            $focus->column_fields['bill_code'] = decode_html($vnd_focus->column_fields['postalcode']);
            $focus->column_fields['bill_pobox'] = decode_html($vnd_focus->column_fields['pobox']);
            $focus->column_fields['bill_country'] = decode_html($vnd_focus->column_fields['country']);

            $focus->column_fields['ship_street'] = (empty($vnd_focus->column_fields['street']) ? '-' : decode_html($vnd_focus->column_fields['street']));
            $focus->column_fields['ship_city'] = decode_html($vnd_focus->column_fields['city']);
            $focus->column_fields['ship_state'] = decode_html($vnd_focus->column_fields['state']);
            $focus->column_fields['ship_code'] = decode_html($vnd_focus->column_fields['postalcode']);
            $focus->column_fields['ship_pobox'] = decode_html($vnd_focus->column_fields['pobox']);
            $focus->column_fields['ship_country'] = decode_html($vnd_focus->column_fields['country']);
            $focus->mode = ''; // Creating
            // Lines
            $qcond = "FROM vtiger_timecontrol tc ";
            $qcond .= "INNER JOIN vtiger_crmentity ce ON tc.timecontrolid = ce.crmid ";
            $qcond .= "WHERE ce.deleted = 0 AND tc.timecontrolid in ($setoftrc) and tc.relatedto = $vendorid ";

            switch ($tcgrouping) {
                case 0: // None. Each TRC will be an individual product line
                    $qtcond = "SELECT tc.product_id, time_to_sec(totaltime) as wtsecs, tcunits as totalunits, ce.description, date_start $qcond";
                    break;
                case 1: // Product. Group TRC by product summing time and units for each individual product line
                    $qtcond = "SELECT tc.product_id, sum(time_to_sec(totaltime)) as wtsecs, sum(tcunits) as totalunits $qcond group by tc.product_id";
                    break;
                case 2: // RelatedEntity. Group TRC by related entity AND product summing time and units for each individual product line
                    $qtcond = "SELECT tc.relatedto,tc.product_id, sum(time_to_sec(totaltime)) as wtsecs, sum(tcunits) as totalunits $qcond
                        group by tc.relatedto,tc.product_id";
                    break;
            }

            $tcs=$adb->query($qtcond);
            $_REQUEST['taxtype']=$taxtype;
            $_REQUEST['totalProductCount']=$adb->num_rows($tcs);
            $subtotal = 0;
            $i=1;
            $totalwithtax = 0;
            // Product lines
            while ($tc=$adb->fetch_array($tcs)) {
                $_REQUEST['hdnProductId'.$i]=$tc['product_id'];
                switch ($productdesc) {
                    case 0: // None
                        $_REQUEST['comment'.$i]='';
                        break;
                    case 1: //  TRC desc with datestamp
                        $fecha = new DateTimeField($tc['date_start']);
                        $tcdesc = html_entity_decode($tc['description'], ENT_QUOTES, $default_charset);
                        $tcdesc = rtrim($tcdesc, '</p>');
                        $tcdesc = str_replace('</p>', "\r\n", $tcdesc);
                        $tcdesc = strip_tags($tcdesc);
                        $_REQUEST['comment'.$i]=$fecha->getDisplayDate().' - '.decode_html($tcdesc);
                        break;
                    case 2:  // Related Entity
                        $recom=getEntityName(getSalesEntityType($tc['relatedto']), $tc['relatedto']);
                        $_REQUEST['comment'.$i]=$recom;
                        break;
                }
                if ($invoiceper==0) { // Time
                    $qty = round($tc['wtsecs']/3600, 2);
                } else { // Units
                    $qty = $tc['totalunits'];
                }
                $_REQUEST['qty'.$i]=$qty;
                $setype=getSalesEntityType($tc['product_id']);
                $_REQUEST['listPrice'.$i]=getUnitPrice($tc['product_id'], $setype);

                $subtotal = $subtotal + ($qty * $_REQUEST['listPrice'.$i]);
                if ($taxtype =="individual") {
                    $taxes_for_product = getTaxDetailsForProduct($tc['product_id'], 'all');
                    for ($tax_count=0; $tax_count<count($taxes_for_product); $tax_count++) {
                        $tax_name = $taxes_for_product[$tax_count]['taxname'];
                        $tax_val = $taxes_for_product[$tax_count]['percentage'];
                        $request_tax_name = $tax_name."_percentage".$i;

                        $_REQUEST[$request_tax_name] = $tax_val;
                        $totalwithtax += ($qty * $_REQUEST['listPrice'.$i]) * ($tax_val/100);
                    }
                }
                $i++;
            }
            $_REQUEST['subtotal']=round($subtotal, 2);

            $_REQUEST['total']=round($subtotal, 2);

            $focus->save($convertto);

            $query = "SELECT tc.timecontrolid $qcond";
            $res = $adb->query($query);
            while ($row=$adb->getNextRow($res)) {
                $query = "insert into vtiger_crmentityrel (crmid, module, relcrmid, relmodule) values ({$row['timecontrolid']}, 'Timecontrol', $focus->id, '$convertto')";
                $adb->query($query);
            }

            $vnd=getEntityName('Vendors', array($vendorid));
            $documents[]=array(
                'title'=>$focus->column_fields[strtolower($convertto).'_no'].' '.$reference,
                'docid'=>$focus->id,
                'vendorname'=>$vnd[$vendorid],
                'vendorid'=>$vendorid,
                'subtotal'=>$subtotal,
                'total'=>$_REQUEST['total'],
            );
        }
        $adb->query("update vtiger_timecontrol set invoiced=1 where timecontrolid in ($setoftrc)");
    } else {
        $relaccounts="select distinct relatedto as relacc 
  from vtiger_timecontrol
  inner join vtiger_crmentity on (crmid=relatedto and setype in ('Accounts') and deleted=0)
  where timecontrolid in ($setoftrc) ";
        if (in_array('Contacts', $toinvoice)) {
            $relaccounts.="UNION
  select distinct accountid as relacc
  from vtiger_timecontrol
  inner join vtiger_crmentity on (crmid=relatedto and setype='Contacts' and deleted=0)
  inner join vtiger_contactdetails on relatedto=contactid
  where timecontrolid in ($setoftrc) ";
        }
        if (in_array('HelpDesk', $toinvoice)) {
            $relaccounts.="UNION
  select distinct case when (ptce.setype='Contacts') then vtiger_contactdetails.accountid else parent_id end as relacc
  from vtiger_timecontrol
  inner join vtiger_crmentity on (crmid=relatedto and setype='HelpDesk' and deleted=0)
  inner join vtiger_troubletickets on relatedto=ticketid
  left join vtiger_crmentity ptce on (ptce.crmid=parent_id)
  left join vtiger_contactdetails on parent_id=contactid
  where timecontrolid in ($setoftrc) ";
        }
        if (in_array('Quotes', $toinvoice)) {
            $relaccounts.="UNION
  select distinct accountid as relacc
  from vtiger_timecontrol
  inner join vtiger_crmentity on (crmid=relatedto and setype='Quotes' and deleted=0)
  inner join vtiger_quotes on relatedto=quoteid
  where timecontrolid in ($setoftrc) ";
        }
        if (in_array('SalesOrder', $toinvoice)) {
            $relaccounts.="UNION
  select distinct accountid as relacc
  from vtiger_timecontrol
  inner join vtiger_crmentity on (crmid=relatedto and setype='SalesOrder' and deleted=0)
  inner join vtiger_salesorder on relatedto=salesorderid
  where timecontrolid in ($setoftrc) ";
        }
        if (in_array('Invoice', $toinvoice)) {
            $relaccounts.="UNION
  select distinct accountid as relacc
  from vtiger_timecontrol
  inner join vtiger_crmentity on (crmid=relatedto and setype='Invoice' and deleted=0)
  inner join vtiger_invoice on relatedto=invoiceid
  where timecontrolid in ($setoftrc) ";
        }
        if (in_array('Potentials', $toinvoice)) {
            $relaccounts.="UNION
  select distinct case when (ptce.setype='Contacts') then vtiger_contactdetails.accountid else related_to end as relacc
  from vtiger_timecontrol
  inner join vtiger_crmentity on (crmid=relatedto and setype='Potentials' and deleted=0)
  inner join vtiger_potential on relatedto=potentialid
  left join vtiger_crmentity ptce on (ptce.crmid=related_to)
  left join vtiger_contactdetails on related_to=contactid
  where timecontrolid in ($setoftrc) ";
        }
        if (in_array('ProjectTask', $toinvoice)) {
            $relaccounts .= "UNION
  select distinct case when (pr_ce.setype='Contacts') then vtiger_contactdetails.accountid else pr.linktoaccountscontacts end as relacc
  from vtiger_timecontrol tc
  join vtiger_projecttask prt on prt.projecttaskid=tc.relatedto
  join vtiger_crmentity crm_prt on crm_prt.crmid=prt.projecttaskid and crm_prt.deleted=0
  join vtiger_project pr on pr.projectid=prt.projectid
  left join vtiger_crmentity pr_ce on (pr_ce.crmid=pr.linktoaccountscontacts)
  left join vtiger_contactdetails on pr.linktoaccountscontacts=contactid
  where tc.timecontrolid in ($setoftrc) ";
        }
        if (in_array('ProjectMilestone', $toinvoice)) {
            $relaccounts .= "UNION
      select distinct case when (pm_ce.setype='Contacts') then vtiger_contactdetails.accountid else pr.linktoaccountscontacts end as relacc
      from vtiger_timecontrol tc
      join vtiger_projectmilestone prm on prm.projectmilestoneid=tc.relatedto
      join vtiger_crmentity crm_prm on crm_prm.crmid=prm.projectmilestoneid and crm_prm.deleted=0
      join vtiger_project pr on pr.projectid=prm.projectid
      left join vtiger_crmentity pm_ce on (pm_ce.crmid=pr.linktoaccountscontacts)
      left join vtiger_contactdetails on pr.linktoaccountscontacts=contactid
      where tc.timecontrolid in ($setoftrc) ";
        }
        if (in_array('Project', $toinvoice)) {
            $relaccounts.="UNION
  select distinct case when (prce.setype='Contacts') then vtiger_contactdetails.accountid else linktoaccountscontacts end as relacc
  from vtiger_timecontrol
  inner join vtiger_crmentity on (crmid=relatedto and setype='Project' and deleted=0)
  inner join vtiger_project on relatedto=projectid
  left join vtiger_crmentity prce on (prce.crmid=linktoaccountscontacts)
  left join vtiger_contactdetails on linktoaccountscontacts=contactid
  where timecontrolid in ($setoftrc) ";
        }

        // Majorlabel: Add UNION to query that also selects accounts from transactions
        if ($setoftrans != '') {
            $relaccounts .= "UNION 
                SELECT DISTINCT CASE WHEN coce.setype='Contacts' THEN vtiger_contactdetails.accountid ELSE vtiger_cobropago.parent_id END AS relacc FROM vtiger_cobropago INNER JOIN vtiger_crmentity ON (vtiger_cobropago.parent_id = vtiger_crmentity.crmid AND vtiger_crmentity.deleted = 0) LEFT JOIN vtiger_crmentity coce ON (coce.crmid = vtiger_cobropago.parent_id) LEFT JOIN vtiger_contactdetails ON vtiger_cobropago.parent_id = vtiger_contactdetails.contactid WHERE vtiger_cobropago.cobropagoid IN ($setoftrans) ";
        }

        $invoiceto=$adb->query($relaccounts);
        while ($ito=$adb->fetch_array($invoiceto)) {
            if (empty($ito['relacc'])) {
                continue;
            }
            $accountid=$ito['relacc'];
            $billcontactaddress = false;
            //Check if invoice_ref exist in vtiger_contactdetails
            $repcols=$adb->getColumnNames('vtiger_contactdetails');
            if (in_array('invoice_ref', $repcols)) {
                if ($rel2contact == 'on') {
                    $ress_acc=$adb->query('SELECT vtiger_account.accountid,vtiger_contactdetails.contactid,vtiger_contactdetails.invoice_ref FROM vtiger_account
                        INNER JOIN vtiger_contactdetails on vtiger_contactdetails.accountid = vtiger_account.accountid and vtiger_contactdetails.invoice_ref = 1
                        WHERE vtiger_account.accountid = '.$accountid);
                    if ($adb->num_rows($ress_acc) > 0) {
                        $contactid = $adb->query_result($ress_acc, 0, 'contactid');
                        $focus->column_fields['contact_id'] = $contactid;
                        if ($bill2contact == 'on') {
                            $billcontactaddress = true;
                        }
                    }
                }
            }
            if ($convertto=='Issuecards') {
                $focus->column_fields['accid'] = $accountid;
                $focus->column_fields['issue_date'] = date('Y-m-d');
                $focus->column_fields['deliver_date'] = date('Y-m-d');
                $focus->column_fields['pslip_no'] = $reference;
            } else {
                $focus->column_fields['account_id'] = $accountid;
                $focus->column_fields['duedate'] = date('Y-m-d');
                // Change next line for any special logic of SO reference generation, e.g. automatic sequential number
                $focus->column_fields['subject'] = $reference;
            }

            if ($convertto=='SalesOrder') {
                $focus->column_fields['sostatus']='Created';
            } else {
                $focus->column_fields['invoicestatus']='Created';
                if ($convertto=='Invoice') {
                    $focus->column_fields['invoicedate']=date('Y-m-d');
                }
            }
            $_REQUEST['assigntype'] = 'U';
            $focus->column_fields['description'] = '';
            $focus->column_fields['currency_id'] = 1;
            $cur_sym_rate = getCurrencySymbolandCRate(1);
            $focus->column_fields['conversion_rate'] = $cur_sym_rate['rate'];

            $acct_focus = new Accounts();
            $acct_focus->retrieve_entity_info($accountid, 'Accounts');
            if ($assignto==0) {  // Use account's user
                $focus->column_fields['assigned_user_id'] = $acct_focus->column_fields['assigned_user_id'];
            } else {
                $focus->column_fields['assigned_user_id'] = $assignto;
            }
            if ($billcontactaddress) {//if bill2contact is active we have to write the contact address
                $ct_focus = new Contacts();
                $ct_focus->retrieve_entity_info($contactid, 'Contacts');

                $focus->column_fields['bill_city']    = decode_html($ct_focus->column_fields['mailingcity']);
                $focus->column_fields['bill_street']  = decode_html($ct_focus->column_fields['mailingstreet']);
                $focus->column_fields['bill_state']   = decode_html($ct_focus->column_fields['mailingstate']);
                $focus->column_fields['bill_code']    = decode_html($ct_focus->column_fields['mailingzip']);
                $focus->column_fields['bill_country'] = decode_html($ct_focus->column_fields['mailingcountry']);
                $focus->column_fields['bill_pobox']   = decode_html($ct_focus->column_fields['mailingpobox']);
                $focus->column_fields['ship_city']    = (empty($ct_focus->column_fields['othercity']) ? decode_html($ct_focus->column_fields['mailingcity']) : decode_html($ct_focus->column_fields['othercity']));
                $focus->column_fields['ship_street']  = (empty($ct_focus->column_fields['otherstreet']) ? decode_html($ct_focus->column_fields['mailingstreet']) : decode_html($ct_focus->column_fields['otherstreet']));
                $focus->column_fields['ship_state']   = (empty($ct_focus->column_fields['otherstate']) ? decode_html($ct_focus->column_fields['mailingstate']) : decode_html($ct_focus->column_fields['otherstate']));
                $focus->column_fields['ship_code']    = (empty($ct_focus->column_fields['otherzip']) ? decode_html($ct_focus->column_fields['mailingzip']) : decode_html($ct_focus->column_fields['otherzip']));
                $focus->column_fields['ship_country'] = (empty($ct_focus->column_fields['othercountry']) ? decode_html($ct_focus->column_fields['mailingcountry']) : decode_html($ct_focus->column_fields['othercountry']));
                $focus->column_fields['ship_pobox']   = (empty($ct_focus->column_fields['otherpobox']) ? decode_html($ct_focus->column_fields['mailingpobox']) : decode_html($ct_focus->column_fields['otherpobox']));
            } elseif ($convertto=="Issuecards") {
                $focus->column_fields['poblacion']    = decode_html($acct_focus->column_fields['bill_city']);
                $focus->column_fields['calle']  = decode_html($acct_focus->column_fields['bill_street']);
                $focus->column_fields['provincia']   = decode_html($acct_focus->column_fields['bill_state']);
                $focus->column_fields['cpostal']    = decode_html($acct_focus->column_fields['bill_code']);
                $focus->column_fields['pais'] = decode_html($acct_focus->column_fields['bill_country']);
            } else {
                $focus->column_fields['bill_city']    = decode_html($acct_focus->column_fields['bill_city']);
                $focus->column_fields['bill_street']  = decode_html($acct_focus->column_fields['bill_street']);
                $focus->column_fields['bill_state']   = decode_html($acct_focus->column_fields['bill_state']);
                $focus->column_fields['bill_code']    = decode_html($acct_focus->column_fields['bill_code']);
                $focus->column_fields['bill_country'] = decode_html($acct_focus->column_fields['bill_country']);
                $focus->column_fields['bill_pobox']   = decode_html($acct_focus->column_fields['bill_pobox']);
                $focus->column_fields['ship_city']    = (empty($acct_focus->column_fields['ship_city']) ? decode_html($acct_focus->column_fields['bill_city']) : decode_html($acct_focus->column_fields['ship_city']));
                $focus->column_fields['ship_street']  = (empty($acct_focus->column_fields['ship_street']) ? decode_html($acct_focus->column_fields['bill_street']) : decode_html($acct_focus->column_fields['ship_street']));
                $focus->column_fields['ship_state']   = (empty($acct_focus->column_fields['ship_state']) ? decode_html($acct_focus->column_fields['bill_state']) : decode_html($acct_focus->column_fields['ship_state']));
                $focus->column_fields['ship_code']    = (empty($acct_focus->column_fields['ship_code']) ? decode_html($acct_focus->column_fields['bill_code']) : decode_html($acct_focus->column_fields['ship_code']));
                $focus->column_fields['ship_country'] = (empty($acct_focus->column_fields['ship_country']) ? decode_html($acct_focus->column_fields['bill_country']) : decode_html($acct_focus->column_fields['ship_country']));
                $focus->column_fields['ship_pobox']   = (empty($acct_focus->column_fields['ship_pobox']) ? decode_html($acct_focus->column_fields['bill_pobox']) : decode_html($acct_focus->column_fields['ship_pobox']));
            }
            $focus->column_fields['terms_conditions'] = decode_html(getTermsandConditions($convertto));
            $focus->mode = ''; // Creating
            // Lines
            $qcond = "FROM vtiger_timecontrol tc ";
            $qcond .= "INNER JOIN vtiger_crmentity ce ON tc.timecontrolid = ce.crmid ";
            if (in_array('Contacts', $toinvoice)) {
                $qcond .= "LEFT JOIN vtiger_contactdetails ct ON ct.contactid = tc.relatedto ";
            }
            if (in_array('HelpDesk', $toinvoice)) {
                $qcond .= "LEFT JOIN vtiger_troubletickets tt ON tt.ticketid = tc.relatedto ";
                $qcond .= "LEFT JOIN vtiger_contactdetails ttct ON ttct.contactid = tt.parent_id ";
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
                $qcond .= "LEFT JOIN vtiger_contactdetails ptct ON ptct.contactid = pt.related_to ";
            }
            if (in_array('ProjectTask', $toinvoice)) {
                $qcond .= "LEFT JOIN vtiger_projecttask prt on prt.projecttaskid=tc.relatedto ";
                $qcond .= "LEFT JOIN vtiger_project pr_prt on pr_prt.projectid=prt.projectid ";
                $qcond .= "LEFT JOIN vtiger_contactdetails pr_ct ON pr_ct.contactid = pr_prt.linktoaccountscontacts ";
            }
            if (in_array('ProjectMilestone', $toinvoice)) {
                $qcond .= "LEFT JOIN vtiger_projectmilestone prm on prm.projectmilestoneid=tc.relatedto ";
                $qcond .= "LEFT JOIN vtiger_project pr_prm on pr_prm.projectid=prm.projectid ";
                $qcond .= "LEFT JOIN vtiger_contactdetails prm_ct ON prm_ct.contactid = pr_prm.linktoaccountscontacts ";
            }
            if (in_array('Project', $toinvoice)) {
                $qcond .= "LEFT JOIN vtiger_project pr ON pr.projectid = tc.relatedto ";
                $qcond .= "LEFT JOIN vtiger_contactdetails prct ON prct.contactid = pr.linktoaccountscontacts ";
            }
            $qcond .= "WHERE ce.deleted = 0 AND tc.timecontrolid in ($setoftrc) and (false ";
            if (in_array('Accounts', $toinvoice)) {
                $qcond .= "or tc.relatedto = $accountid ";
            }
            if (in_array('Contacts', $toinvoice)) {
                $qcond .= "or ct.accountid = $accountid ";
            }
            if (in_array('HelpDesk', $toinvoice)) {
                $qcond .= "or tt.parent_id = $accountid or ttct.accountid=$accountid ";
            }
            if (in_array('Quotes', $toinvoice)) {
                $qcond .= "or qt.accountid = $accountid ";
            }
            if (in_array('SalesOrder', $toinvoice)) {
                $qcond .= "or so.accountid = $accountid ";
            }
            if (in_array('Invoice', $toinvoice)) {
                $qcond .= "or iv.accountid = $accountid ";
            }
            if (in_array('Potentials', $toinvoice)) {
                $qcond .= "or pt.related_to = $accountid or ptct.accountid=$accountid ";
            }
            if (in_array('ProjectTask', $toinvoice)) {
                $qcond .= "or pr_prt.linktoaccountscontacts = $accountid or pr_ct.accountid=$accountid ";
            }
            if (in_array('ProjectMilestone', $toinvoice)) {
                $qcond .= "or pr_prm.linktoaccountscontacts = $accountid or prm_ct.accountid=$accountid ";
            }
            if (in_array('Project', $toinvoice)) {
                $qcond .= "or pr.linktoaccountscontacts = $accountid or prct.accountid=$accountid ";
            }
            $qcond .= ")";
            switch ($tcgrouping) {
                case 0: // None. Each TRC will be an individual product line
                    $qtcond = "SELECT tc.product_id, time_to_sec(totaltime) as wtsecs, tcunits as totalunits, ce.description, date_start $qcond";
                    break;
                case 1: // Product. Group TRC by product summing time and units for each individual product line
                    $qtcond = "SELECT tc.product_id, sum(time_to_sec(totaltime)) as wtsecs, sum(tcunits) as totalunits $qcond group by tc.product_id";
                    break;
                case 2: // RelatedEntity. Group TRC by related entity AND product summing time and units for each individual product line
                    $qtcond = "SELECT tc.relatedto,tc.product_id, sum(time_to_sec(totaltime)) as wtsecs, sum(tcunits) as totalunits $qcond
                        group by tc.relatedto,tc.product_id";
                    break;
            }
            $tcs=$adb->query($qtcond);
            $_REQUEST['taxtype']=$taxtype;
            $_REQUEST['totalProductCount']=$adb->num_rows($tcs);
            $subtotal = 0;
            $i=1;
            $totalwithtax = 0;
            // Product lines
            while ($tc=$adb->fetch_array($tcs)) {
                $_REQUEST['hdnProductId'.$i]=$tc['product_id'];
                switch ($productdesc) {
                    case 0: // None
                        $_REQUEST['comment'.$i]='';
                        break;
                    case 1: //  TRC desc with datestamp
                        $fecha = new DateTimeField((isset($tc['date_start']) ? $tc['date_start'] : ''));
                        $tcdesc = (isset($tc['description']) ? html_entity_decode($tc['description'], ENT_QUOTES, $default_charset) : '');
                        $tcdesc = rtrim($tcdesc, '</p>');
                        $tcdesc = str_replace('</p>', "\r\n", $tcdesc);
                        $tcdesc = strip_tags($tcdesc);
                        $_REQUEST['comment'.$i]=$fecha->getDisplayDate().' - '.decode_html($tcdesc);
                        break;
                    case 2:  // Related Entity
                        $recom=getEntityName(getSalesEntityType($tc['relatedto']), $tc['relatedto']);
                        $_REQUEST['comment'.$i]=$recom;
                        break;
                }
                if ($invoiceper==0) { // Time
                    $qty = round($tc['wtsecs']/3600, 2);
                } elseif ($invoiceper==1) { // Units
                    $qty = $tc['totalunits'];
                } else { //Both
                    $prod_serv = $tc['product_id'];
                    if (getSalesEntityType($prod_serv)=="Products") { //Related to Product, use Units
                        $qty = $tc['totalunits'];
                    } else {
                        $qty = round($tc['wtsecs']/3600, 2); //Related to Services, use Time
                    }
                }
                $_REQUEST['qty'.$i]=$qty;
                $setype=getSalesEntityType($tc['product_id']);
                $_REQUEST['listPrice'.$i]=getUnitPrice($tc['product_id'], $setype);
                if (!empty($acct_focus->column_fields['pbookid'])) {
                    $query = "select listprice from vtiger_pricebookproductrel where pricebookid=? and productid=?";
                    $rdopb = $adb->pquery($query, array($acct_focus->column_fields['pbookid'], $tc['product_id']));
                    if ($adb->num_rows($rdopb)>0) {
                        $_REQUEST['listPrice'.$i] = $adb->query_result($rdopb, 0, 'listprice');
                    }
                }
                $subtotal = $subtotal + ($qty * $_REQUEST['listPrice'.$i]);
                if ($taxtype =="individual") {
                    $taxes_for_product = getTaxDetailsForProduct($tc['product_id'], 'all');
                    for ($tax_count=0; $tax_count<count($taxes_for_product); $tax_count++) {
                        $tax_name = $taxes_for_product[$tax_count]['taxname'];
                        $tax_val = $taxes_for_product[$tax_count]['percentage'];
                        $request_tax_name = $tax_name."_percentage".$i;
                        $_REQUEST[$request_tax_name] = $tax_val;
                        $totalwithtax += ($qty * $_REQUEST['listPrice'.$i]) * ($tax_val/100);
                    }
                }
                $_REQUEST['deleted'.$i] = 0;
                $i++;
            }
            if ($convertto == 'Invoice' || $convertto == 'SalesOrder') {
                $focus->column_fields['related_to'] = $_REQUEST['tcinv_origin'];
                // Create lines for the retainers, continuing with the previous $i counter
                // Select the retainers and advances from DB
                $rets_cs = implode(',', $rets);
                $advs_cs = implode(',', $advs);

                $ret_result = $adb->query("SELECT reference, credit, amount, paymentdate, productservice_id FROM vtiger_cobropago WHERE cobropagoid IN ($rets_cs) AND (parent_id = $accountid OR parent_id IN (SELECT contactid FROM vtiger_contactdetails WHERE vtiger_contactdetails.accountid = $accountid))");
                $adv_result = $adb->query("SELECT reference, credit, amount, paymentdate, productservice_id FROM vtiger_cobropago WHERE cobropagoid IN ($advs_cs) AND (parent_id = $accountid OR parent_id IN (SELECT contactid FROM vtiger_contactdetails WHERE vtiger_contactdetails.accountid = $accountid))");

                if ($adb->num_rows($ret_result) > 0) {
                    while ($ret = $adb->fetch_array($ret_result)) {
                        // Add the line to the request
                        $_REQUEST['hdnProductId' . $i] = $ret['productservice_id'];
                        $_REQUEST['comment' . $i] = $ret['reference'];
                        $_REQUEST['qty' . $i] = 1;
                        $_REQUEST['discount_type' . $i] = 'amount';
                        $_REQUEST['discount_amount' . $i] = $ret['amount'] < 0 ? (-1 * abs($ret['amount'])) : $ret['amount'];
                        $_REQUEST['listPrice' . $i] = 0;
                        $_REQUEST['deleted' . $i] = 0;
                        // Add some information for the totals
                        $subtotal = $subtotal - (1 * $_REQUEST['discount_amount' . $i]);
                        if ($taxtype =="individual") {
                            $taxes_for_product = getTaxDetailsForProduct($ret['productservice_id'], 'all');
                            for ($tax_count=0; $tax_count<count($taxes_for_product); $tax_count++) {
                                $tax_name = $taxes_for_product[$tax_count]['taxname'];
                                $tax_val = $taxes_for_product[$tax_count]['percentage'];
                                $request_tax_name = $tax_name."_percentage" . $i;
                                $_REQUEST[$request_tax_name] = $tax_val;
                                $totalwithtax += (1 * $_REQUEST['discount_amount' . $i]) * ($tax_val/100);
                            }
                        }
                        // Update counters
                        $i++;
                        $_REQUEST['totalProductCount']++;
                    }
                }
                if ($adb->num_rows($adv_result) > 0) {
                    while ($adv = $adb->fetch_array($adv_result)) {
                        // Add the line to the request
                        $_REQUEST['hdnProductId' . $i] = $adv['productservice_id'];
                        $_REQUEST['comment' . $i] = $adv['reference'];
                        $_REQUEST['qty' . $i] = 1;
                        $_REQUEST['listPrice' . $i] = $adv['amount'];
                        $_REQUEST['deleted' . $i] = 0;
                        // Add some information for the totals
                        $subtotal = $subtotal + $adv['amount'];
                        if ($taxtype =="individual") {
                            $taxes_for_product = getTaxDetailsForProduct($ret['productservice_id'], 'all');
                            for ($tax_count=0; $tax_count<count($taxes_for_product); $tax_count++) {
                                $tax_name = $taxes_for_product[$tax_count]['taxname'];
                                $tax_val = $taxes_for_product[$tax_count]['percentage'];
                                $request_tax_name = $tax_name."_percentage" . $i;
                                $_REQUEST[$request_tax_name] = $tax_val;
                                $totalwithtax += (1 * $_REQUEST['listPrice' . $i]) * ($tax_val/100);
                            }
                        }
                        // Update counters
                        $i++;
                        $_REQUEST['totalProductCount']++;
                    }
                }
            }
            $_REQUEST['shipping_handling_charge'] = 0;
            $_REQUEST['subtotal']=round($subtotal + $totalwithtax, 2);
            if ($taxtype =="individual") {
                $_REQUEST['total']=round($subtotal+$totalwithtax, 2);
            } else {
                $all_available_taxes = getAllTaxes('available', '');
                $tax_val = 0;
                for ($tax_count=0; $tax_count<count($all_available_taxes); $tax_count++) {
                      $tax_val += $all_available_taxes[$tax_count]['percentage'];
                }
                $_REQUEST['total']=round($subtotal+($subtotal*$tax_val/100), 2);
            }
            // ADJUSTMENT TYPE FOR DUSS.
      //      $ld=substr($_REQUEST['total'],-1);
      //  if ($ld<5) {
      //      $adj="0.0$ld";
      //      $d=0;
      //  } else {
      //      $adj="0.0".($ld-5);
      //      $d=5;
      //  }
      //  $_REQUEST['total']=$_REQUEST['total']-$adj;
      //  $_REQUEST['adjustment']=$adj;
      //  $_REQUEST['adjustmentType']='-';
            if ($convertto == 'Invoice') {
                $focus->column_fields['related_to'] = $_REQUEST['tcinv_origin'];
            }
            $focus->save($convertto);
            if ($convertto=="Issuecards" && $focus->id > 0) {
                $relhelpdesk=$adb->pquery("select distinct relatedto,vtiger_troubletickets.soid
        from vtiger_timecontrol
        inner join vtiger_troubletickets on vtiger_troubletickets.ticketid=vtiger_timecontrol.relatedto 
        where timecontrolid in ($setoftrc)", array());
                $hd=$adb->fetch_array($relhelpdesk);
                //Guardo el parte y el contrato que generan este albarán.
                $adb->pquery(
                    "UPDATE vtiger_issuecards SET iss_ticketid = ?, iss_soid = ? WHERE issuecardid = ?",
                    array($hd['relatedto'],$hd['soid'],$focus->id)
                );
                //Marco el Parte como pasado a albarán.
                $adb->pquery(
                    "UPDATE vtiger_troubletickets SET pasado_a_albaran = ? WHERE ticketid = ?",
                    array('1',$hd['relatedto'])
                );
            }
            $query = "SELECT tc.timecontrolid $qcond";
            $res = $adb->query($query);
            while ($row=$adb->getNextRow($res)) {
                $query = "insert into vtiger_crmentityrel (crmid, module, relcrmid, relmodule) values ({$row['timecontrolid']}, 'Timecontrol', $focus->id, '$convertto')";
                $adb->query($query);
            }
            $ac=getEntityName('Accounts', array($accountid));
            $documents[]=array(
            'title'=>$focus->column_fields[strtolower($convertto).'_no'].' '.$reference,
            'docid'=>$focus->id,
            'accountname'=>$ac[$accountid],
            'accountid'=>$accountid,
            'subtotal'=>$subtotal,
            'total'=>$_REQUEST['total'],
            );
        }
        if ($convertto == 'Invoice') {
            $adb->query("update vtiger_timecontrol set invoiced=1 where timecontrolid in ($setoftrc)");
            $adb->query("UPDATE vtiger_cobropago SET settled=1 WHERE cobropagoid IN ($rets)");
            $adb->query("UPDATE vtiger_cobropago SET settled=1 WHERE cobropagoid IN ($advs)");
        }
    }
}

$smarty->assign('MODULE', $currentModule);
$smarty->assign('SINGLE_MOD', getTranslatedString('SINGLE_'.$currentModule));
$smarty->assign('CATEGORY', '');
$smarty->assign('ID', '');
$smarty->assign('MODE', '');
$smarty->assign('CHECK', Button_Check($currentModule));
$smarty->assign('THEME', $default_theme);
$smarty->assign('IMAGE_PATH', "themes/$default_theme/images/");
$smarty->assign('APP', $app_strings);
$smarty->assign('MOD', $mod_strings);
$smarty->assign('documents', $documents);
$smarty->assign('convertto', $convertto);
$smarty->display("modules/TimecontrolInv/converted.tpl");
?>
