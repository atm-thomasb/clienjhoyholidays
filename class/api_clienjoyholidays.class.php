<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016	Laurent Destailleur		<eldy@users.sourceforge.net>
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

use Luracast\Restler\RestException;

require_once __DIR__ . '/formuledevoyage.class.php';
/**
 * API class for orders
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class CliEnjoyHolidaysAPI extends DolibarrApi
{
	/**
	 * @var Commande $commande {@type Commande}
	 */
	public $formuledevoyage;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		global $db, $conf;
		$this->db = $db;
		$this->formuledevoyage = new FormuleDeVoyage($this->db);
	}

	/**
	 * Get properties of an formule de voyage object by id
	 *
	 * Return an array with formule de voyage informations
	 *
	 * @param       int         $id            ID of formule de voyage
	 * @return	array|mixed data without useless information
	 *
	 * @throws	RestException
	 */
	public function get($id)
	{
		return $this->_fetch($id, '', '', 1);
	}

	/**
	 * Get properties of an order object
	 *
	 * Return an array with order informations
	 *
	 * @param       int         $id				ID of order
	 * @param		string		$ref			Ref of object
	 * @param		string		$ref_ext		External reference of object
	 * @param       int         $contact_list	0: Returned array of contacts/addresses contains all properties, 1: Return array contains just id
	 * @return		Object						Object with cleaned properties
	 *
	 * @throws	RestException
	 */
	private function _fetch($id, $ref = '', $ref_ext = '', $contact_list = 1)
	{
		if (!DolibarrApiAccess::$user->hasRight('clienjoyholidays', 'formuledevoyage', 'read')) {
			throw new RestException(401);
		}

		$result = $this->formuledevoyage->fetch($id, $ref, $ref_ext);
		if (!$result) {
			throw new RestException(404, 'Formule de Voyage not found');
		}

		if (!DolibarrApi::_checkAccessToResource('commande', $this->formuledevoyage->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		$this->formuledevoyage->fetchObjectLinked();

		return $this->_cleanObjectDatas($this->formuledevoyage);
	}

	/**
	 * List formules de voyage
	 *
	 * Get a list of formules de voyage
	 *
	 * @param string	 $sortfield		  Sort field
	 * @param string	 $sortorder		  Sort order
	 * @param int		 $limit			  Limit for list
	 * @param int		 $page			  Page number
	 * @param string     $sqlfilters      Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%')"
	 * @param string	 $properties	  Restrict the data returned to theses properties. Ignored if empty. Comma separated list of properties names
	 * @return  array		              Array of formule de voyage objects
	 *
	 * @throws RestException 404 Not found
	 * @throws RestException 503 Error
	 */
	public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '', $properties = '')
	{
		if (!DolibarrApiAccess::$user->hasRight('clienjoyholidays', 'formuledevoyage', 'read') ) {
			throw new RestException(401);
		}

		$obj_ret = array();

		$sql = "SELECT t.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."clienjoyholidays_formuledevoyage AS t";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."clienjoyholidays_formuledevoyage_extrafields AS ef ON (ef.fk_object = t.rowid)";
		$sql .= "WHERE 1=1";

		// Add sql filters
		if ($sqlfilters) {
			$errormessage = '';
			$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
			if ($errormessage) {
				throw new RestException(400, 'Error when validating parameter sqlfilters -> '.$errormessage);
			}
		}

		// Add limit
		$sql .= $this->db->order($sortfield, $sortorder);
		if ($limit) {
			if ($page < 0) {
				$page = 0;
			}
			$offset = $limit * $page;

			$sql .= $this->db->plimit($limit + 1, $offset);
		}

		dol_syslog("API Rest request");
		$result = $this->db->query($sql);

		if ($result) {
			$num = $this->db->num_rows($result);
			$min = min($num, ($limit <= 0 ? $num : $limit));
			$i = 0;
			while ($i < $min) {
				$obj = $this->db->fetch_object($result);
				$formuledevoyage_static = new FormuleDeVoyage($this->db);
				if ($formuledevoyage_static->fetch($obj->rowid)) {
					// Add external contacts ids
					$tmparray = $formuledevoyage_static->liste_contact(-1, 'external', 1);
					if (is_array($tmparray)) {
						$formuledevoyage_static->contacts_ids = $tmparray;
					}
					// Add online_payment_url, cf #20477
					require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
					$formuledevoyage_static->online_payment_url = getOnlinePaymentUrl(0, 'formuledevoyage', $formuledevoyage_static->ref);

					$obj_ret[] = $this->_filterObjectProperties($this->_cleanObjectDatas($formuledevoyage_static), $properties);
				}
				$i++;
			}
		} else {
			throw new RestException(503, 'Error when retrieve formuledevoyage list : '.$this->db->lasterror());
		}

		return $obj_ret;
	}
}
