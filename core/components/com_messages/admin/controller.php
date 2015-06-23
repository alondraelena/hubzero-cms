<?php
/**
 * @copyright	Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_HZEXEC_') or die();

/**
 * Messages master display controller.
 *
 * @package		Joomla.Administrator
 * @subpackage	com_messages
 * @since		1.6
 */
class MessagesController extends JControllerLegacy
{
	/**
	 * Method to display a view.
	 *
	 * @param	boolean			If true, the view output will be cached
	 * @param	array			An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return	JController		This object to support chaining.
	 * @since	1.5
	 */
	public function display($cachable = false, $urlparams = false)
	{
		require_once JPATH_COMPONENT.'/helpers/messages.php';

		$view		= Request::getCmd('view', 'messages');
		$layout 	= Request::getCmd('layout', 'default');
		$id			= Request::getInt('id');

		// Check for edit form.
		if ($view == 'message' && $layout == 'edit' && !$this->checkEditId('com_messages.edit.message', $id)) {
			// Somehow the person just went to the form - we don't allow that.
			$this->setError(Lang::txt('JLIB_APPLICATION_ERROR_UNHELD_ID', $id));
			$this->setMessage($this->getError(), 'error');
			$this->setRedirect(Route::url('index.php?option=com_messages&view=messages', false));

			return false;
		}

		parent::display();

		// Load the submenu.
		MessagesHelper::addSubmenu(Request::getCmd('view', 'messages'));
	}
}
