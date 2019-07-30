<?php
/*+***********************************************************************************
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

$module = Vtiger_Module::getInstance('Contacts');
if ($module) {
    $blockInstance = Vtiger_Block::getInstance('LBL_CONTACT_INFORMATION', $module);

    if ($blockInstance) {
        $field10 = new Vtiger_Field();
        $field10->name = 'invoice_ref';
        $field10->label= 'Invoice Reference';
        $field10->table = 'vtiger_contactdetails';
        $field10->column = 'invoice_ref';
        $field10->columntype = 'VARCHAR(3)';
        $field10->uitype = 56;
        $field10->presence = 0;
        $field10->displaytype = 1;
        $field10->typeofdata = 'V~O';
        $blockInstance->addField($field10);
        echo "<br><b>Added Field to Contacts module.</b>";
    } else {
        echo "<b>Failed to find Contacts module block</b>";
    }
} else {
    echo "<b>Failed to find Contacts module.</b><br>";
}
//Not uncomment this lines, the manifes.xml have this querys
/*$adb->query("ALTER TABLE `trcinvoicing` ADD `rel2contact` VARCHAR( 3 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '0' AFTER `assignto`;");

$adb->query("ALTER TABLE `trcinvoicing` ADD `bill2contact` VARCHAR( 3 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT '0' AFTER `rel2contact`;");

$adb->query("ALTER TABLE `trcinvoicing` ADD `tcsubject` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL AFTER `bill2contact`;");*/

echo '</body></html>';
?>
