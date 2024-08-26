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
	public function deleteOldNotValidatedFormules()
	{
		global $user;

		// Check for permissions
		$permissiontodelete = $user->hasRight('clienjoyholidays', 'formuledevoyage', 'delete');
		if (empty($permissiontodelete)) {
			return 0;
		}

		require_once __DIR__.'/formuledevoyage.class.php';

		$object = new FormuleDeVoyage($this->db);
		$error = 0;
		$this->output = '';
		$this->error = '';

		dol_syslog(__METHOD__." start", LOG_INFO);

		$now = dol_now();

		$this->db->begin();





		// Build and execute select
		// --------------------------------------------------------------------
		$sql = "SELECT ";
		$sql .= "t.rowid, t.ref,t.date_creation,t.status";
		$sql .= " FROM ".MAIN_DB_PREFIX."clienjoyholidays_formuledevoyage as t";
		$sql .= " ORDER BY t.rowid ASC;";

		$resql = $this->db->query($sql);

		$i = 0;

		while ($i < min($this->db->num_rows($resql), 100)){
			$obj = $this->db->fetch_object($resql);
			$object->setVarsFromFetchObj($obj);

			// Calculates the date time difference in days
			$datediff = ($now-$object->date_creation)/3600/24;

			if ($object->status != FormuleDeVoyage::STATUS_VALIDATED && $datediff > 21) {
				$object->delete($user);
				var_dump("Formule supprimée ".$object->ref." because old of ".$datediff." days");
			}

			$i++;
		}

		$this->db->commit();

		dol_syslog(__METHOD__." end", LOG_INFO);

		return $error;
	}
}
