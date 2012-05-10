<?php
/**
 * @package     hubzero-cms
 * @author      Shawn Rice <zooley@purdue.edu>
 * @copyright   Copyright 2005-2011 Purdue University. All rights reserved.
 * @license     http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 *
 * Copyright 2005-2011 Purdue University. All rights reserved.
 *
 * This file is part of: The HUBzero(R) Platform for Scientific Collaboration
 *
 * The HUBzero(R) Platform for Scientific Collaboration (HUBzero) is free
 * software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software
 * Foundation, either version 3 of the License, or (at your option) any
 * later version.
 *
 * HUBzero is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * HUBzero is a registered trademark of Purdue University.
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

//-----------

ximport('Hubzero_Plugin');
JPlugin::loadLanguage( 'plg_members_messages' );

//-----------

class plgMembersMessages extends Hubzero_Plugin
{
	public function plgMembersMessages(&$subject, $config)
	{
		parent::__construct($subject, $config);

		// load plugin parameters
		$this->_plugin = JPluginHelper::getPlugin( 'members', 'messages' );
		$this->_params = new JParameter( $this->_plugin->params );
	}
	
	//-----------
	
	public function &onMembersAreas( $user, $member ) 
	{
		//default areas returned to nothing
		$areas = array();
		
		//if this is the logged in user show them
		if($user->get("id") == $member->get("uidNumber"))
		{
			$areas['messages'] = JText::_('PLG_MEMBERS_MESSAGES');
		}
		
		return $areas;
	}

	//-----------

	public function onMembers( $user, $member, $option, $areas )
	{
		$returnhtml = true;
		
		$arr = array(
			'html'=>'',
			'metadata'=>''
		);
		
		/*
		// Is the user logged in?
		if (!$authorized) {
			return $arr;
		}
		*/
		
		// Check if our area is in the array of areas we want to return results for
		if (is_array( $areas )) {
			if (!array_intersect( $areas, $this->onMembersAreas( $user, $member ) ) 
			&& !array_intersect( $areas, array_keys( $this->onMembersAreas( $user, $member ) ) )) {
				$returnhtml = false;
			}
		}
		
		// Get our database object
		$database =& JFactory::getDBO();

		// Load some needed libraries
		ximport('Hubzero_Message');
		
		// Are we returning HTML?
		if ($returnhtml) {
			ximport('Hubzero_Document');
			Hubzero_Document::addPluginStylesheet('members', 'messages');
			
			$task = JRequest::getVar('action','');
			if (!$task) {
				$task = JRequest::getVar('inaction','');
			}
			
			$mid = JRequest::getInt('msg',0);
			if ($mid) {
				$task = 'view';
			}
			
			if(!$task) {
				$task = "inbox";
			}
			
			switch ($task) 
			{
				case 'sendtoarchive': 	$body = $this->sendtoarchive($database, $option, $member); break;
				case 'sendtotrash':   	$body = $this->sendtotrash($database, $option, $member);   break;
				case 'sendtoinbox':   	$body = $this->sendtoinbox($database, $option, $member);   break;
				case 'markasread':    	$body = $this->markasread($database, $option, $member);    break;
				case 'markasunread':    $body = $this->markasunread($database, $option, $member);  break;
				case 'savesettings':  	$body = $this->savesettings($database, $option, $member);  break;
				case 'emptytrash':    	$body = $this->emptytrash($database, $option, $member);    break;
				case 'delete':        	$body = $this->delete($database, $option, $member);        break;
				
				case 'send':         	$body = $this->send($database, $option, $member);          break;
				case 'new':           	$body = $this->create($database, $option, $member);        break;
				
				case 'view':          	$body = $this->view($database, $option, $member, $mid);    break;
				case 'sent':          	$body = $this->sent($database, $option, $member);          break;
				case 'settings':      	$body = $this->settings($database, $option, $member);      break;
				case 'archive':       	$body = $this->archive($database, $option, $member);       break;
				case 'trash':         	$body = $this->trash($database, $option, $member);         break;
				case 'inbox': 
				default: 			  	$body = $this->inbox($database, $option, $member); 		   break;
			}
			
			
			//html for the messages
			ximport('Hubzero_Plugin_View');
			$view = new Hubzero_Plugin_View(
				array(
					'folder'=>'members',
					'element'=>'messages',
					'name'=>'default'
				)
			);
			$view->option = $option;
			$view->member = $member;
			$view->task = $task;

			$view->body = $body;
			$view->notifications = ($this->getPluginMessage()) ? $this->getPluginMessage() : array();
			$arr['html'] = $view->loadTemplate();
		}
		
		//get meta
		$arr['metadata'] = array();               
		
		//get the number of unread messages
		$recipient = new Hubzero_Message_Recipient( $database );
		$messages = $recipient->getMessages( $member->get('uidNumber') ); 
		$umessages = $recipient->getUnreadMessages( $member->get('uidNumber'), 0 ); 
		
		//return total message count
		$arr['metadata']['count'] = count($messages);
		
		//if we have unread messages show alert
		if(count($umessages) > 0) 
		{
			$title = "I have " . count($umessages) . " unread message(s).";
			$link = JRoute::_('index.php?option=com_members&id='.$member->get("uidNumber").'&active=messages');
			$arr['metadata']['alert'] = "<a class=\"alrt\" href=\"{$link}\"><span><h5>Messages Alert</h5>{$title}</span></a>";
		}
		
		// Return data
		return $arr;
	}
	
	
	//------------------------------------------
	//	Views
	//------------------------------------------
	
	public function inbox($database, $option, $member) 
	{
		// Push some scripts to the template
		Hubzero_Document::addPluginScript("members","messages");
		
		// Filters for returning results
		$filters = array();
		$filters['limit'] = JRequest::getInt('limit', 10);
		$filters['start'] = JRequest::getInt('limitstart', 0);
		$filters['state'] = 0;
		$filter = JRequest::getVar('filter', '');
		$filters['filter'] = ($filter) ? 'com_'.$filter : '';
		
		$recipient = new Hubzero_Message_Recipient( $database );
		
		$total = $recipient->getMessagesCount( $member->get('uidNumber'), $filters );
		
		$rows = $recipient->getMessages( $member->get('uidNumber'), $filters );

		jimport('joomla.html.pagination');
		$pageNav = new JPagination( $total, $filters['start'], $filters['limit'] );

		$xmc = new Hubzero_Message_Component( $database );
		$components = $xmc->getComponents();

		$pagenavhtml = $pageNav->getListFooter();
		$pagenavhtml = str_replace('members?','members/'.$member->get('uidNumber').'/messages/inbox/?',$pagenavhtml);
		$pagenavhtml = str_replace('members/?','members/'.$member->get('uidNumber').'/messages/inbox/?',$pagenavhtml);
		$pagenavhtml = str_replace('action=inbox','',$pagenavhtml);
		$pagenavhtml = str_replace('&amp;&amp;','&amp;',$pagenavhtml);
		$pagenavhtml = str_replace('?&amp;','?',$pagenavhtml);
		$pagenavhtml = str_replace('href="<li class=limit','href="members/'.$member->get('uidNumber').'/messages/inbox/?limit',$pagenavhtml);
		$pagenavhtml = str_replace('messages?','messages/inbox?',$pagenavhtml);

		ximport('Hubzero_Plugin_View');
		$view = new Hubzero_Plugin_View(
			array(
				'folder'=>'members',
				'element'=>'messages',
				'name'=>'default',
				'layout' => 'inbox'
			)
		);
		$view->option = $option;
		$view->member = $member;
		$view->components = $components;
		$view->rows = $rows;
		$view->pagenavhtml = $pagenavhtml;
		$view->filter = $filter;
		return $view->loadTemplate();
	}
	
	//-----------
	
	public function archive($database, $option, $member) 
	{
		// Push some scripts to the template
		$document =& JFactory::getDocument();
		if (is_file(JPATH_ROOT.DS.'plugins'.DS.'members'.DS.'messages'.DS.'messages.js')) {
			$document->addScript('plugins'.DS.'members'.DS.'messages'.DS.'messages.js');
		}
		
		// Filters for returning results
		$filters = array();
		$filters['limit'] = JRequest::getInt('limit', 10);
		$filters['start'] = JRequest::getInt('limitstart', 0);
		$filters['state'] = 1;
		$filter = JRequest::getVar('filter', '');
		$filters['filter'] = ($filter) ? 'com_'.$filter : '';
		
		$recipient = new Hubzero_Message_Recipient( $database );
		
		$total = $recipient->getMessagesCount( $member->get('uidNumber'), $filters );
		
		$rows = $recipient->getMessages( $member->get('uidNumber'), $filters );

		jimport('joomla.html.pagination');
		$pageNav = new JPagination( $total, $filters['start'], $filters['limit'] );

		$xmc = new Hubzero_Message_Component( $database );
		$components = $xmc->getComponents();

		$pagenavhtml = $pageNav->getListFooter();
		$pagenavhtml = str_replace('members?','members/'.$member->get('uidNumber').'/messages/archive/?',$pagenavhtml);
		$pagenavhtml = str_replace('members/?','members/'.$member->get('uidNumber').'/messages/archive/?',$pagenavhtml);
		$pagenavhtml = str_replace('action=archive','',$pagenavhtml);
		$pagenavhtml = str_replace('&amp;&amp;','&amp;',$pagenavhtml);
		$pagenavhtml = str_replace('?&amp;','?',$pagenavhtml);
		$pagenavhtml = str_replace('href="<li class=limit','href="members/'.$member->get('uidNumber').'/messages/inbox/?limit',$pagenavhtml);
		$pagenavhtml = str_replace('messages?','messages/archive?',$pagenavhtml);

		ximport('Hubzero_Plugin_View');
		$view = new Hubzero_Plugin_View(
			array(
				'folder'=>'members',
				'element'=>'messages',
				'name'=>'default',
				'layout'=>'archive'
			)
		);
		$view->option = $option;
		$view->member = $member;
		$view->components = $components;
		$view->rows = $rows;
		$view->pagenavhtml = $pagenavhtml;
		$view->filter = $filter;
		return $view->loadTemplate();
	}

	//-----------
	
	public function trash($database, $option, $member) 
	{
		// Push some scripts to the template
		$document =& JFactory::getDocument();
		if (is_file(JPATH_ROOT.DS.'plugins'.DS.'members'.DS.'messages'.DS.'messages.js')) {
			$document->addScript('plugins'.DS.'members'.DS.'messages'.DS.'messages.js');
		}
		
		// Filters for returning results
		$filters = array();
		$filters['limit'] = JRequest::getInt('limit', 10);
		$filters['start'] = JRequest::getInt('limitstart', 0);
		$filters['state'] = 2;
		$filter = JRequest::getVar('filter', '');
		$filters['filter'] = ($filter) ? 'com_'.$filter : '';
		
		$recipient = new Hubzero_Message_Recipient( $database );
		
		$total = $recipient->getMessagesCount( $member->get('uidNumber'), $filters );
		
		$rows = $recipient->getMessages( $member->get('uidNumber'), $filters );

		jimport('joomla.html.pagination');
		$pageNav = new JPagination( $total, $filters['start'], $filters['limit'] );

		$xmc = new Hubzero_Message_Component( $database );
		$components = $xmc->getComponents();

		$pagenavhtml = $pageNav->getListFooter();
		$pagenavhtml = str_replace('members?','members/'.$member->get('uidNumber').'/messages/trash/?',$pagenavhtml);
		$pagenavhtml = str_replace('members/?','members/'.$member->get('uidNumber').'/messages/trash/?',$pagenavhtml);
		$pagenavhtml = str_replace('action=trash','',$pagenavhtml);
		$pagenavhtml = str_replace('&amp;&amp;','&amp;',$pagenavhtml);
		$pagenavhtml = str_replace('?&amp;','?',$pagenavhtml);
		$pagenavhtml = str_replace('href="<li class=limit','href="members/'.$member->get('uidNumber').'/messages/inbox/?limit',$pagenavhtml);
		$pagenavhtml = str_replace('messages?','messages/trash?',$pagenavhtml);

		ximport('Hubzero_Plugin_View');
		$view = new Hubzero_Plugin_View(
			array(
				'folder'=>'members',
				'element'=>'messages',
				'name'=>'default',
				'layout'=>'trash'
			)
		);
		$view->option = $option;
		$view->member = $member;
		$view->components = $components;
		$view->rows = $rows;
		$view->pagenavhtml = $pagenavhtml;
		$view->filter = $filter;
		if ($this->getError()) {
			$view->setError( $this->getError() );
		}
		return $view->loadTemplate();
	}

	//-----------
	
	public function sent($database, $option, $member) 
	{
		// Push some scripts to the template
		$document =& JFactory::getDocument();
		if (is_file(JPATH_ROOT.DS.'plugins'.DS.'members'.DS.'messages'.DS.'messages.js')) {
			$document->addScript('plugins'.DS.'members'.DS.'messages'.DS.'messages.js');
		}
		
		// Filters for returning results
		$filters = array();
		$filters['limit'] = JRequest::getInt('limit', 10);
		$filters['start'] = JRequest::getInt('limitstart', 0);
		$filters['created_by'] = $member->get('uidNumber');
		
		$recipient = new Hubzero_Message_Message( $database );
		
		$total = $recipient->getSentMessagesCount( $filters );
		
		$rows = $recipient->getSentMessages( $filters );

		jimport('joomla.html.pagination');
		$pageNav = new JPagination( $total, $filters['start'], $filters['limit'] );

		$pagenavhtml = $pageNav->getListFooter();
		$pagenavhtml = str_replace('members?','members/'.$member->get('uidNumber').'/messages/sent/?',$pagenavhtml);
		$pagenavhtml = str_replace('members/?','members/'.$member->get('uidNumber').'/messages/sent/?',$pagenavhtml);
		$pagenavhtml = str_replace('action=sent','',$pagenavhtml);
		$pagenavhtml = str_replace('&amp;&amp;','&amp;',$pagenavhtml);
		$pagenavhtml = str_replace('?&amp;','?',$pagenavhtml);
		$pagenavhtml = str_replace('href="<li class=limit','href="members/'.$member->get('uidNumber').'/messages/inbox/?limit',$pagenavhtml);
		$pagenavhtml = str_replace('messages?','messages/sent?',$pagenavhtml);
		
		ximport('Hubzero_Plugin_View');
		$view = new Hubzero_Plugin_View(
			array(
				'folder'=>'members',
				'element'=>'messages',
				'name'=>'default',
				'layout'=>'sent'
			)
		);
		$view->option = $option;
		$view->member = $member;
		$view->rows = $rows;
		$view->pagenavhtml = $pagenavhtml;
		if ($this->getError()) {
			$view->setError( $this->getError() );
		}
		return $view->loadTemplate();
	}

	//-----------
	
	public function settings($database, $option, $member) 
	{
		// Push some scripts to the template
		$document =& JFactory::getDocument();
		if (is_file(JPATH_ROOT.DS.'plugins'.DS.'members'.DS.'messages'.DS.'messages.js')) {
			$document->addScript('plugins'.DS.'members'.DS.'messages'.DS.'messages.js');
		}
		
		$xmc = new Hubzero_Message_Component( $database );
		$components = $xmc->getRecords();
		
		ximport('Hubzero_Plugin_View');
		$view = new Hubzero_Plugin_View(
			array(
				'folder'=>'members',
				'element'=>'messages',
				'name'=>'default',
				'layout'=>'settings'
			)
		);
		$view->option = $option;
		$view->member = $member;
		$view->components = $components;
		if (!$components) {
			if ($this->getError()) {
				$view->setError( $this->getError() );
			}
			return $view->loadTemplate();
		}
		
		$settings = array();
		foreach ($components as $component) 
		{
			$settings[$component->action] = array();
		}
		
		// Load plugins
		JPluginHelper::importPlugin( 'xmessage' );
		$dispatcher =& JDispatcher::getInstance();
		
		// Fetch message methods
		$notimethods = $dispatcher->trigger( 'onMessageMethods', array() );
		
		// A var for storing the default notification method
		$default_method = null;
		
		// Instantiate our notify object
		$notify = new Hubzero_Message_Notify( $database );
		
		// Get the user's selected methods
		$methods = $notify->getRecords( $member->get('uidNumber') );
		if ($methods) {
			foreach ($methods as $method) 
			{
				$settings[$method->type]['methods'][] = $method->method;
				$settings[$method->type]['ids'][$method->method] = $method->id;
			}
		} else {
			$default_method = $this->_params->get('default_method');
		}

		// Fill in any settings that weren't set.
		foreach ($settings as $key=>$val)
		{
			if (count($val) <= 0) {
				// If the user has never changed their settings, set up the defaults
				if ($default_method !== null) {
					$settings[$key]['methods'][] = 'internal';
					$settings[$key]['methods'][] = $default_method;
					$settings[$key]['ids']['internal'] = 0;
					$settings[$key]['ids'][$default_method] = 0;
				} else {
					$settings[$key]['methods'] = array();
					$settings[$key]['ids'] = array();
				}
			}
		}
		
		$view->settings = $settings;
		$view->notimethods = $notimethods;
		return $view->loadTemplate();
	}
	
	//-----------
	
	private function create($database, $option, $member) 
	{
		ximport('Hubzero_Plugin_View');
		$view = new Hubzero_Plugin_View(
			array(
				'folder'=>'members',
				'element'=>'messages',
				'name'=>'default',
				'layout'=>'create'
			)
		);
		
		//list of message to's
		$tos = array();
		
		//get members name and id
		$mbrs = JRequest::getVar("to", array());
		foreach($mbrs as $mbr)
		{
			$mem =& JUser::getInstance( $mbr );
			$tos[] = $mem->get("name") . " (" . $mem->get("id") . ")";
		}
		
		$view->option = $option;
		$view->member = $member;
		$view->tos = implode(",", $tos);
		$view->no_html = JRequest::getInt( 'no_html', 0 );
		if ($this->getError()) {
			$view->setError( $this->getError() );
		}
		return $view->loadTemplate();
	}
	
	//-----------
	
	public function view($database, $option, $member, $mid) 
	{
		$xmessage = new Hubzero_Message_Message( $database );
		$xmessage->load( $mid );
		$xmessage->message = stripslashes($xmessage->message);
		
		$xmr = new Hubzero_Message_Recipient( $database );
		$xmr->loadRecord( $mid, $member->get('uidNumber') );

		$xmessage->message = str_replace("\n","\n ",$xmessage->message);
		$UrlPtrn  = "[^=\"\'](https?:|mailto:|ftp:|gopher:|news:|file:)" . "([^ |\\/\"\']*\\/)*([^ |\\t\\n\\/\"\']*[A-Za-z0-9\\/?=&~_])";
		$xmessage->message = preg_replace_callback("/$UrlPtrn/", array('plgMembersMessages','autolink'), $xmessage->message);
		$xmessage->message = nl2br($xmessage->message);
		$xmessage->message = str_replace("\t",'&nbsp;&nbsp;&nbsp;&nbsp;',$xmessage->message);
		
		if (substr($xmessage->component,0,4) == 'com_') {
			$xmessage->component = substr($xmessage->component,4);
		}
		
		$xseen = new Hubzero_Message_Seen( $database );
		$xseen->mid = $mid;
		$xseen->uid = $member->get('uidNumber');
		$xseen->loadRecord();
		$juser =& JFactory::getUser();
		if ($juser->get('id') == $member->get('uidNumber')) {
			if ($xseen->whenseen == '' || $xseen->whenseen == '0000-00-00 00:00:00' || $xseen->whenseen == NULL) {
				$xseen->whenseen = date( 'Y-m-d H:i:s', time() );
				$xseen->store( true );
			}
		}
		
		if (substr($xmessage->type, -8) == '_message') {
			$u =& JUser::getInstance($xmessage->created_by);
			$from = '<a href="'.JRoute::_('index.php?option='.$option.'&id='.$u->get('id')).'">'.$u->get('name').'</a>'.n;
		} else {
			$from = JText::sprintf('PLG_MEMBERS_MESSAGES_SYSTEM', $xmessage->component);
		}
		
		
		ximport('Hubzero_Plugin_View');
		$view = new Hubzero_Plugin_View(
			array(
				'folder'=>'members',
				'element'=>'messages',
				'name'=>'default',
				'layout'=>'message'
			)
		);
		$view->option = $option;
		$view->member = $member;
		$view->xmr = $xmr;
		$view->xmessage = $xmessage;
		$view->from = $from;
		if ($this->getError()) {
			$view->setError( $this->getError() );
		}
		return $view->loadTemplate();
	}


	//------------------------------------------
	//	Functions
	//------------------------------------------
	
	public function sendtoarchive($database, $option, $member) 
	{
		$limit = JRequest::getInt('limit', 20);
		$start = JRequest::getInt('limitstart', 0);
		$mids = JRequest::getVar('mid',array(0));
		
		if (count($mids) > 0) {
			foreach ($mids as $mid) 
			{
				$recipient = new Hubzero_Message_Recipient( $database );
				$recipient->mid = $mid;
				$recipient->uid = $member->get('uidNumber');
				$recipient->loadRecord();
				$recipient->state = 1;
				if (!$recipient->store()) {
					$this->setError( $recipient->getError() );
				}
				
				$xseen = new Hubzero_Message_Seen( $database );
				$xseen->mid = $mid;
				$xseen->uid = $member->get('uidNumber');
				$xseen->loadRecord();
				if ($xseen->whenseen == '' || $xseen->whenseen == '0000-00-00 00:00:00' || $xseen->whenseen == NULL) {
					$xseen->whenseen = date( 'Y-m-d H:i:s', time() );
					$xseen->store( true );
				}
			}
		}
		
		$this->addPluginMessage("You have successfully moved <b><u>" . count($mids) . "</u></b> message(s) to your archive.","passed");
		return $this->redirect( JRoute::_('index.php?option=com_members&id='.$member->get('uidNumber').'&active=messages&task=archive&start='.$start.'&limit='.$limit) );
	}

	//-----------
	
	public function sendtoinbox($database, $option, $member) 
	{
		$limit = JRequest::getInt('limit', 20);
		$start = JRequest::getInt('limitstart', 0);
		$mids = JRequest::getVar('mid',array(0));
		
		if (count($mids) > 0) {
			foreach ($mids as $mid) 
			{
				$recipient = new Hubzero_Message_Recipient( $database );
				$recipient->mid = $mid;
				$recipient->uid = $member->get('uidNumber');
				$recipient->loadRecord();
				$recipient->state = 0;
				if (!$recipient->store()) {
					$this->setError( $recipient->getError() );
				}
			}
		}
		
		$this->addPluginMessage("You have successfully moved <b><u>" . count($mids) . "</u></b> message(s) to your inbox.","passed");
		return $this->redirect( JRoute::_('index.php?option=com_members&id='.$member->get('uidNumber').'&active=messages&task=inbox&start='.$start.'&limit='.$limit) );
	}

	//-----------
	
	public function sendtotrash($database, $option, $member) 
	{
		$limit = JRequest::getInt('limit', 20);
		$start = JRequest::getInt('limitstart', 0);
		$mids = JRequest::getVar('mid',array(0));
		
		if (count($mids) > 0) {
			foreach ($mids as $mid) 
			{
				$recipient = new Hubzero_Message_Recipient( $database );
				$recipient->mid = $mid;
				$recipient->uid = $member->get('uidNumber');
				$recipient->loadRecord();
				
				$xseen = new Hubzero_Message_Seen( $database );
				$xseen->mid = $mid;
				$xseen->uid = $member->get('uidNumber');
				$xseen->loadRecord();
				if ($xseen->whenseen == '' || $xseen->whenseen == '0000-00-00 00:00:00' || $xseen->whenseen == NULL) {
					$xseen->whenseen = date( 'Y-m-d H:i:s', time() );
					$xseen->store( true );
				}
				
				$recipient->state = 2;
				$recipient->expires = date( 'Y-m-d H:i:s', time()+(10*60*60*60) );
				if (!$recipient->store()) {
					$this->setError( $recipient->getError() );
				}
			}
		}
		
		$this->addPluginMessage("You have successfully moved <b><u>" . count($mids) . "</u></b> message(s) to your trash.","passed");
		return $this->redirect( JRoute::_('index.php?option=com_members&id='.$member->get('uidNumber').'&active=messages&task=trash&start='.$start.'&limit='.$limit) );
	}
	
	//-----------
	
	public function emptytrash($database, $option, $member) 
	{
		$recipient = new Hubzero_Message_Recipient( $database );
		$recipient->uid = $member->get('uidNumber');
		if (!$recipient->deleteTrash()) {
			$this->setError( $recipient->getError() );
		}
		
		return $this->trash($database, $option, $member);
	}
	
	//-----------
	
	public function delete($database, $option, $member) 
	{
		$limit = JRequest::getInt('limit', 20);
		$start = JRequest::getInt('limitstart', 0);
		$mids = JRequest::getVar('mid',array(0));
		
		if (count($mids) > 0) {
			foreach ($mids as $mid) 
			{
				$recipient = new Hubzero_Message_Recipient( $database );
				$recipient->mid = $mid;
				$recipient->uid = $member->get('uidNumber');
				$recipient->loadRecord();
				if (!$recipient->delete()) {
					$this->setError( $recipient->getError() );
				}
			}
		}
		
		$this->addPluginMessage("You have successfully deleted <b><u>" . count($mids) . "</u></b> message(s).","passed");
		return $this->redirect( JRoute::_('index.php?option=com_members&id='.$member->get('uidNumber').'&active=messages&task=trash&start='.$start.'&limit='.$limit) );
	}
	
	//-----------
	
	public function markasread($database, $option, $member) 
	{
		$limit = JRequest::getInt('limit', 20);
		$start = JRequest::getInt('limitstart', 0);
		$ids = JRequest::getVar('mid',array(0));
		
		if (count($ids) > 0) {
			foreach ($ids as $mid) 
			{
				$xseen = new Hubzero_Message_Seen( $database );
				$xseen->mid = $mid;
				$xseen->uid = $member->get('uidNumber');
				$xseen->loadRecord();
				if ($xseen->whenseen == '' || $xseen->whenseen == '0000-00-00 00:00:00' || $xseen->whenseen == NULL) {
					$xseen->whenseen = date( 'Y-m-d H:i:s', time() );
					$xseen->store( true );
				}
			}
		}
		
		$this->addPluginMessage("You have successfully marked <b><u>" . count($ids) . "</u></b> message(s) as read.","passed");
		return $this->redirect( JRoute::_('index.php?option=com_members&id='.$member->get('uidNumber').'&active=messages&task=inbox&start='.$start.'&limit='.$limit) );
	}
	
	//-----------
	
	public function markasunread($database, $option, $member) 
	{
		$limit = JRequest::getInt('limit', 20);
		$start = JRequest::getInt('limitstart', 0);
		$ids = JRequest::getVar('mid',array(0));
		
		if (count($ids) > 0) {
			$sql = "DELETE FROM #__xmessage_seen WHERE `uid`=".$member->get('uidNumber')." AND `mid` IN(".implode(",",$ids).")";
			$database =& JFactory::getDBO();
			$database->setQuery($sql);
			$database->query();
		}
		
		$this->addPluginMessage("You have successfully marked <b><u>" . count($ids) . "</u></b> message(s) as unread.","passed");
		return $this->redirect( JRoute::_('index.php?option=com_members&id='.$member->get('uidNumber').'&active=messages&task=inbox&start='.$start.'&limit='.$limit) );
	}
	
	//-----------

	public function savesettings($database, $option, $member) 
	{
		// Incoming
		//$override = JRequest::getInt('override',0);
		$settings = JRequest::getVar('settings',array());
		$ids = JRequest::getVar('ids',array());
		
		// Ensure we have data to work with
		if ($settings && count($settings) > 0) {
			// Loop through each setting
			foreach ($settings as $key=>$value) 
			{
				foreach ($value as $v) 
				{
					if ($v) {
						// Instantiate a Notify object and set its values
						$notify = new Hubzero_Message_Notify( $database );
						$notify->uid = $member->get('uidNumber');
						$notify->method = $v;
						$notify->type = $key;
						$notify->priority = 1;
						// Do we have an ID for this setting?
						// Determines if the store() method is going to INSERT or UPDATE
						if ($ids[$key][$v] > 0) {
							$notify->id = $ids[$key][$v];
							$ids[$key][$v] = -1;
							//echo 'updated: '.$key.':'.$v.'<br />';
						//} else {
							//echo 'created: '.$key.':'.$v.'<br />';
						}
						// Save
						if (!$notify->store()) {
							$this->setError( JText::sprintf('PLG_MEMBERS_MESSAGES_ERROR_NOTIFY_FAILED', $notify->method) );
						}
					}
				}
			}

			$notify = new Hubzero_Message_Notify( $database );
			foreach ($ids as $key=>$value) 
			{
				foreach ($value as $k=>$v) 
				{
					if ($v > 0) {
						$notify->delete( $v );
						//echo 'deleted: '.$v.'<br />';
					}
				}
			}
			
			// If they previously had everything turned off, we need to remove that entry saying so
			$records = $notify->getRecords( $member->get('uidNumber'), 'all' );
			if ($records) {
				foreach ($records as $record) 
				{
					$notify->delete( $record->id );
				}
			}
		} else {
			// This creates a single entry to let the system know that the user has explicitly chosen "none" for all options
			// It ensures we can know the difference between someone who has never changed their settings (thus, no database entries) 
			// and someone who purposely wants everything turned off.
			$notify = new Hubzero_Message_Notify( $database );
			$notify->uid = $member->get('uidNumber');
			
			$records = $notify->getRecords( $member->get('uidNumber'), 'all' );
			if (!$records) {
				$notify->clearAll();
				$notify->method = 'none';
				$notify->type = 'all';
				$notify->priority = 1;
				if (!$notify->store()) {
					$this->setError( JText::sprintf('PLG_MEMBERS_MESSAGES_ERROR_NOTIFY_FAILED', $notify->method) );
				}
			}
		}
		
		// Push through to the settings view
		$this->addPluginMessage("You have successfully saved your message settings.","passed");	
		return $this->redirect( JRoute::_('index.php?option=com_members&id='.$member->get('uidNumber').'&active=messages&action=settings') );
	}
	
	//-----------
	
	public function send($database, $option, $member)
	{
		// Ensure the user is logged in
		$juser =& JFactory::getUser();
		if ($juser->get('guest')) {
			return false;
		}
		
		// Incoming array of users to message
		$mbrs = array_map("trim", explode(",", JRequest::getVar( 'mbrs', array(), 'post' )));
		
		//array to hold members
		$email_users = array();
		
		//
		foreach($mbrs as $mbr)
		{
			if(is_numeric($mbr))
			{
				$email_users[] = $mbr;
			}
			else
			{
				preg_match("/\((\d+)\)/", $mbr, $matches);
				$email_users[] = $matches[1];
 			}
		}
		
		// Incoming message and subject
		$subject = JRequest::getVar( 'subject', JText::_('PLG_MEMBERS_MESSAGES_SUBJECT_MESSAGE') );
		$message = JRequest::getVar( 'message', '' );
		$no_html = JRequest::getInt( 'no_html', 0 );
		
		if (!$subject || !$message) {
			if(!$no_html) {
				$this->addPluginMessage("You must select a message recipient and enter a message.","error");	
				return $this->redirect( JRoute::_('index.php?option=com_members&id='.$member->get('uidNumber').'&active=messages&action=new') );
			}
			return JError::raiseError(500,"You must select a message recipient and enter a message.");
		}
		
		// Build the "from" data for the e-mail
		$from = array();
		$from['name']  = $member->get('name');
		$from['email'] = $member->get('email');
		
		// Send the message
		JPluginHelper::importPlugin( 'xmessage' );
		$dispatcher =& JDispatcher::getInstance();
		if (!$dispatcher->trigger( 'onSendMessage', array( 'member_message', $subject, $message, $from, $email_users, $option ))) {
			$this->setError( JText::_('PLG_MEMBERS_MESSAGES_ERROR_MSG_USER_FAILED') );
		}
		
		// Determine if we're returning HTML or not
		// (if no - this is an AJAX call)
		if (!$no_html) {
			$this->addPluginMessage("You have successfully sent a message.","passed");	
			return $this->redirect( JRoute::_('index.php?option=com_members&id='.$member->get('uidNumber').'&active=messages&task=inbox') );
		}
	}
	
	
	//------------------------------------------
	//	Utilities
	//------------------------------------------

	public function autolink($matches) 
	{
		$href = $matches[0];

		if (substr($href, 0, 1) == '!') {
			return substr($href, 1);
		}
		
		$href = str_replace('"','',$href);
		$href = str_replace("'",'',$href);
		$href = str_replace('&#8221','',$href);
		
		$h = array('h','m','f','g','n');
		if (!in_array(substr($href,0,1), $h)) {
			$href = substr($href, 1);
		}
		$name = trim($href);
		if (substr($name, 0, 7) == 'mailto:') {
			$name = substr($name, 7, strlen($name));
			$name = plgMembersMessages::obfuscate($name);
			//$href = Eventshtml::obfuscate($href);
			$href = 'mailto:'.$name;
		}
		$l = sprintf(
			' <a class="ext-link" href="%s" rel="external">%s</a>',$href,$name
		);
		return $l;
	}
	
	//-----------
	
	public function obfuscate( $email )
	{
		$length = strlen($email);
		$obfuscatedEmail = '';
		for ($i = 0; $i < $length; $i++) 
		{
			$obfuscatedEmail .= '&#'. ord($email[$i]) .';';
		}
		
		return $obfuscatedEmail;
	}
	
	//-----------

	public function selectMethod($notimethods, $name, $values=array(), $ids=array())
	{
		$out = '';
		$i = 0;
		foreach ($notimethods as $notimethod) 
		{
			$out .= t.t.t.t.t.'<td>'.n;
			$out .= t.t.t.t.t.t.'<input type="checkbox" name="settings['.$name.'][]" class="opt-'.$notimethod.'" value="'.$notimethod.'"';
			$out .= (in_array($notimethod, $values))
						  ? ' checked="checked"'
						  : '';
			$out .= ' />'.n;
			$out .= t.t.t.t.t.t.'<input type="hidden" name="ids['.$name.']['.$notimethod.']" value="';
			if (isset($ids[$notimethod])) {
				$out .= $ids[$notimethod];
			} else {
				$out .= '0';
			}
			$out .= '" />'.n;
			$out .= t.t.t.t.t.'</td>'.n;
			$i++;
		}
		return $out;
	}
	
	//-----------
	
}

