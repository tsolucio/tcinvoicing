<?php
/*
+***********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *************************************************************************************/

// Just a bit of HTML formatting
echo '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';

echo '<html><head><title>vtlib Module Script</title>';
echo '<style type="text/css">@import url("themes/softed/style.css");br { display: block; margin: 2px; }</style>';
echo '</head><body class=small style="font-size: 12px; margin: 2px; padding: 2px;">';
echo '<a href="index.php"><img src="themes/softed/images/vtiger-crm.gif" alt="vtiger CRM" title="vtiger CRM" border=0></a><hr style="height: 1px">';

// Turn on debugging level
$Vtiger_Utils_Log = true;
global $adb;

include_once 'vtlib/Vtiger/Module.php';

$module = Vtiger_Module::getInstance('Timecontrol');
$invoiceModule = Vtiger_Module::getInstance('Invoice');
$invoiceModule->setRelatedList($module, 'Invoiced Timecontrol', array());
$adb->query("update vtiger_relatedlists set actions='' where tabid=".$module->id." and related_tabid=23 and label='Invoiced on'");

echo '</body></html>';
?>
