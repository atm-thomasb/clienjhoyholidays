<?php
/* Copyright (C) 2017  Laurent Destailleur      <eldy@users.sourceforge.net>
 * Copyright (C) 2023  Frédéric France          <frederic.france@netlogic.fr>
 * Copyright (C) 2024 SuperAdmin
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file        class/cron_formuledevoyage.class.php
 * \ingroup     clienjoyholidays
 * \brief       This file is for the CRON of Formule de Voyage
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

/**
 * Class for cron_FormuleDeVoyage
 */
class cron_FormuleDeVoyage extends CommonObject
{

	public $output;
	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db){
		$this->db = $db;
	}

	/**
	 * Action executed by scheduler (cron task)
	 * Deletes every day all Formule de Voyage older than 3 weeks that aren't validated
	 *
	 * @return	int			0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
	 */
	public function deleteOldNotValidatedFormules() :int
	{
		global $user, $langs;

		$error = 0;
		$this->output = '';
		$this->error = '';

		// Check for permissions
		$permissiontodelete = $user->hasRight('clienjoyholidays', 'formuledevoyage', 'delete');
		if (empty($permissiontodelete)) {
			$this->output = $langs->trans("CronErrorUserUnallowed");
			return 0;
		}

		require_once __DIR__.'/formuledevoyage.class.php';

		$object = new FormuleDeVoyage($this->db);


		dol_syslog(__METHOD__." start", LOG_INFO);

		$this->db->begin();

		$formulesDeleted = "";
		$Tformules = $object->fetchAll('', '',0, 0, ["customsql" => "DATEDIFF(NOW(), t.date_creation) > 21 AND status != ".FormuleDeVoyage::STATUS_VALIDATED]);
		if ($Tformules < 0){
			$error = 1;
			dol_syslog(get_class($this)."::deleteOldNotValidatedFormules ".$object->error, LOG_ERR);
			$this->output = ($langs->trans("CronErrorFetchingFormulesDeVoyage"));
		} else {
			foreach($Tformules as $obj){
				if ($obj->delete($user) < 0){
					$error = 1;
					dol_syslog(get_class($this)."::deleteOldNotValidatedFormules ".$object->error, LOG_ERR);
					$this->output = ($langs->trans("CronErrorDeletingObject", $object->ref));
				}
				$formulesDeleted .= $obj->ref.", ";
			}

			if (!empty($formulesDeleted)){
				$formulesDeleted = substr_replace($formulesDeleted, ".",-2);
				$this->output = ($langs->trans("CronDeleteFormuleDeVoyage")).$formulesDeleted;
			} else {
				$this->output = ($langs->trans("CronSuccesButNoDeletion"));
			}

		}

		$error==0 ? $this->db->commit() : $this->db->rollback();

		return $error;
	}
}
