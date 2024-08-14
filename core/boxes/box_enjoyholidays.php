<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Frederic France      <frederic.france@free.fr>
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
 *		\file       htdocs/core/boxes/box_commandes.php
 *		\ingroup    commande
 *		\brief      Widget for latest sale orders
 */

include_once DOL_DOCUMENT_ROOT.'/core/boxes/modules_boxes.php';

/**
 * Class to manage the box to show last customer orders
 */
class box_enjoyholidays extends ModeleBoxes
{
	public $boxcode  = "enjoyholidaysbox";
	public $boximg   = "object_order";
	public $boxlabel = "EnjoyHolidayBoxLabel";
	public $depends  = array("commande");

	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	public $param;

	public $info_box_head = array();
	public $info_box_contents = array();


	/**
	 *  Constructor
	 *
	 *  @param  DoliDB  $db         Database handler
	 *  @param  string  $param      More parameters
	 */
	public function __construct($db, $param)
	{
		global $user;

		$this->db = $db;

		$this->hidden = empty($user->rights->commande->lire);
	}

	/**
	 *  Load data for box to show them later
	 *
	 *  @param	int		$max        Maximum number of records to load
	 *  @return	void
	 */
	public function loadBox($max = 5)
	{
		global $user, $langs, $conf;
		$langs->load('orders');

		$this->max = $max;
		include_once DOL_DOCUMENT_ROOT.'/custom/clienjoyholidays/class/formuledevoyage.class.php';

		$formuledevoyagestatic = new FormuleDeVoyage($this->db);
		//$societestatic = new Societe($this->db);
		$userstatic = new User($this->db);

		$this->info_box_head = array('text' => $langs->trans("BoxTitle".(getDolGlobalString('MAIN_LASTBOX_ON_OBJECT_DATE') ? "" : "Last")."FormulesDeVoyage", $max));

		if ($user->hasRight('clienjoyholidays', 'formuledevoyage', 'read')) {
			$sql = "SELECT ";
			$sql .= "rowid";
			$sql .= ", c.ref";
			$sql .= ", c.label";
			$sql .= ", c.cost";
			$sql .= " FROM ".$this->db->prefix()."clienjoyholidays_formuledevoyage as c ";
			$sql .= " ORDER BY c.tms DESC, c.ref ASC ";


			$sql .= $this->db->plimit($max, 0);


			$result = $this->db->query($sql);
			if ($result) {
				$num = $this->db->num_rows($result);

				$line = 0;

				while ($line < $num) {
					$objp = $this->db->fetch_object($result);

					$formuledevoyagestatic->id = $objp->rowid;
					$formuledevoyagestatic->ref = $objp->ref;
					$formuledevoyagestatic->label = $objp->label;
					$formuledevoyagestatic->cost = $objp->cost;

					$this->info_box_contents[$line][] = array(
						'td' => 'class="nowraponall"',
						'text' => $formuledevoyagestatic->getNomUrl(1),
						'asis' => 1,
					);


					$this->info_box_contents[$line][] = array(
						'td' => 'class="nowraponall"',
						'text' => $objp->label,
					);

					$this->info_box_contents[$line][] = array(
						'td' => 'class="nowraponall right amount"',
						'text' => price($objp->cost, 0, $langs, 0, -1, -1, $conf->currency),
					);

					if (getDolGlobalString('ORDER_BOX_LAST_ORDERS_SHOW_VALIDATE_USER')) {
						if ($objp->fk_user_valid > 0) {
							$userstatic->fetch($objp->fk_user_valid);
						}
						$this->info_box_contents[$line][] = array(
							'td' => 'class="right"',
							'text' => (($objp->fk_user_valid > 0) ? $userstatic->getNomUrl(1) : ''),
							'asis' => 1,
						);
					}

					$line++;
				}

				if ($num == 0) {
					$this->info_box_contents[$line][0] = array(
						'td' => 'class="center"',
						'text'=> '<span class="opacitymedium">'.$langs->trans("NoRecordedOrders").'</span>'
					);
				}

				$this->db->free($result);
			} else {
				$this->info_box_contents[0][0] = array(
					'td' => '',
					'maxlength'=>500,
					'text' => ($this->db->error().' sql='.$sql),
				);
			}
		} else {
			$this->info_box_contents[0][0] = array(
				'td' => 'class="nohover left"',
				'text' => '<span class="opacitymedium">'.$langs->trans("ReadPermissionNotAllowed").'</span>'
			);

		}
	}

	/**
	 *	Method to show box
	 *
	 *	@param	array	$head       Array with properties of box title
	 *	@param  array	$contents   Array with properties of box lines
	 *  @param	int		$nooutput	No print, only return string
	 *	@return	string
	 */
	public function showBox($head = null, $contents = null, $nooutput = 0)
	{
		return parent::showBox($this->info_box_head, $this->info_box_contents, $nooutput);
	}
}
