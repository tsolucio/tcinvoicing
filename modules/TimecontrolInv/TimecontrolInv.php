<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
require_once 'data/CRMEntity.php';
require_once 'data/Tracker.php';

class TimecontrolInv extends CRMEntity {
	public $moduleIcon = array('library' => 'standard', 'containerClass' => 'slds-icon_container slds-icon-standard-account', 'class' => 'slds-icon', 'icon'=>'timesheet_entry');

	/**
	 * Invoked when special actions are performed on the module.
	 * @param String Module name
	 * @param String Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
	 */
	public function vtlib_handler($modulename, $event_type) {
		if ($event_type == 'module.postinstall') {
			// TODO Handle post installation actions
			global $adb;
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
					$field10->typeofdata = 'C~O';
					$blockInstance->addField($field10);
					echo '<br><b>Added Field to Contacts module.</b>';
				} else {
					echo '<b>Failed to find Contacts module block</b>';
				}
			} else {
				echo '<b>Failed to find Contacts module.</b><br>';
			}
			$helpdeskModule = Vtiger_Module::getInstance('HelpDesk');
			if ($helpdeskModule) {
				$helpdeskModule->addLink('DETAILVIEWBASIC', 'Convert Invoice', 'index.php?module=TimecontrolInv&action=index&onlyttid=$RECORD$');
			}
			$module = Vtiger_Module::getInstance('Timecontrol');
			$invoiceModule = Vtiger_Module::getInstance('Invoice');
			if ($module && $invoiceModule) {
				$invoiceModule->setRelatedList($module, 'Invoiced Timecontrol', array());
			}
			$adb->query("update vtiger_relatedlists set actions='' where tabid=".$module->id." and related_tabid=".$invoiceModule->id." and label='Invoiced on'");
		} elseif ($event_type == 'module.disabled') {
			// TODO Handle actions when this module is disabled.
		} elseif ($event_type == 'module.enabled') {
			// TODO Handle actions when this module is enabled.
		} elseif ($event_type == 'module.preuninstall') {
			// TODO Handle actions when this module is about to be deleted.
		} elseif ($event_type == 'module.preupdate') {
			// TODO Handle actions before this module is updated.
		} elseif ($event_type == 'module.postupdate') {
			// TODO Handle actions after this module is updated.
		}
	}
}
?>
