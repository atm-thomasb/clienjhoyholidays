<?php

/**
 * Class ActionsSupplierorderfromorder
 *
 * Hook actions
 */

class ActionsClienjoyHolidays
{
	function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager){
		global $langs;
		$url = dol_buildpath('/clienjoyholidays/formuledevoyage_card.php', 1).'?id='.$object->id.'&origin='.$object->element.'&action=create';
		print dolGetButtonAction('', $langs->trans('createFormuleDeVoyageFromPropal'), 'create', $url, '');

		return 0;
	}
}
