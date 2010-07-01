<?php
/**
 * @package		HUBzero CMS
 * @author		Shawn Rice <zooley@purdue.edu>
 * @copyright	Copyright 2005-2009 by Purdue Research Foundation, West Lafayette, IN 47906
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 *
 * Copyright 2005-2009 by Purdue Research Foundation, West Lafayette, IN 47906.
 * All rights reserved.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License,
 * version 2 as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );


class XPollMenu extends JTable 
{
	var $pollid  = NULL; // @var int(11)
	var $menuid  = NULL; // @var int(11)

	//-----------

	public function XPollMenu( &$db ) 
	{
		parent::__construct( '#__xpoll_menu', 'pollid', $db );
	}
	
	//-----------

	public function check() 
	{
		// Check for pollid
		if ($this->menuid == '') {
			$this->setError( JText::_('XPOLL_ERROR_MISSING_MENU_ID') );
			return false;
		}
		
		// Check for pollid
		if ($this->pollid == '') {
			$this->setError( JText::_('XPOLL_ERROR_MISSING_POLL_ID') );
			return false;
		}

		return true;
	}
	
	//-----------
	
	public function deleteEntries( $poll_id=NULL ) 
	{
		if ($poll_id == NULL) {
			$poll_id = $this->pollid;
		}
		
		$this->_db->setQuery( "DELETE FROM $this->_tbl WHERE pollid='$poll_id'" );
		if (!$this->_db->query()) {
			echo $this->_db->getErrorMsg();
			exit();
		}
	}
	
	//-----------
	
	public function insertEntry( $poll_id=NULL, $menu_id=NULL ) 
	{
		if ($poll_id == NULL) {
			$poll_id = $this->pollid;
		}
		if ($menu_id == NULL) {
			$menu_id = $this->menuid;
		}
		
		$this->_db->setQuery( "INSERT INTO $this->_tbl (pollid, menuid) VALUES ($poll_id, $menu_id)" );
		if (!$this->_db->query()) {
			echo $this->_db->getErrorMsg();
			//exit();
		}
	}
	
	//-----------
	
	public function getMenuIds( $poll_id=NULL ) 
	{
		if ($poll_id == NULL) {
			$poll_id = $this->pollid;
		}
		$this->_db->setQuery( "SELECT menuid AS value FROM $this->_tbl WHERE pollid='$poll_id'" );
		return $this->_db->loadObjectList();
	}
}
