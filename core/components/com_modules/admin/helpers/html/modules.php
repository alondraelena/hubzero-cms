<?php
/**
 * @copyright	Copyright (C) 2005 - 2014 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_HZEXEC_') or die();

/**
 * @package		Joomla.Administrator
 * @subpackage	com_modules
 * @since		1.6
 */
abstract class JHtmlModules
{
	/**
	 * @param	int $clientId	The client id
	 * @param	string $state 	The state of the template
	 */
	static public function templates($clientId = 0, $state = '')
	{
		$templates = ModulesHelper::getTemplates($clientId, $state);
		foreach ($templates as $template)
		{
			$options[] = Html::select('option', $template->element, $template->name);
		}
		return $options;
	}

	/**
	 */
	static public function types()
	{
		$options = array();
		$options[] = Html::select('option', 'user', 'COM_MODULES_OPTION_POSITION_USER_DEFINED');
		$options[] = Html::select('option', 'template', 'COM_MODULES_OPTION_POSITION_TEMPLATE_DEFINED');
		return $options;
	}

	/**
	 */
	static public function templateStates()
	{
		$options = array();
		$options[] = Html::select('option', '1', 'JENABLED');
		$options[] = Html::select('option', '0', 'JDISABLED');
		return $options;
	}

	/**
	 * Returns a published state on a grid
	 *
	 * @param   integer       $value			The state value.
	 * @param   integer       $i				The row index
	 * @param   boolean       $enabled			An optional setting for access control on the action.
	 * @param   string        $checkbox			An optional prefix for checkboxes.
	 *
	 * @return  string        The Html code
	 *
	 * @see JHtmlJGrid::state
	 *
	 * @since   1.7.1
	 */
	public static function state($value, $i, $enabled = true, $checkbox = 'cb')
	{
		$states	= array(
			1	=> array(
				'unpublish',
				'COM_MODULES_EXTENSION_PUBLISHED_ENABLED',
				'COM_MODULES_HTML_UNPUBLISH_ENABLED',
				'COM_MODULES_EXTENSION_PUBLISHED_ENABLED',
				true,
				'publish',
				'publish'
			),
			0	=> array(
				'publish',
				'COM_MODULES_EXTENSION_UNPUBLISHED_ENABLED',
				'COM_MODULES_HTML_PUBLISH_ENABLED',
				'COM_MODULES_EXTENSION_UNPUBLISHED_ENABLED',
				true,
				'unpublish',
				'unpublish'
			),
			-1	=> array(
				'unpublish',
				'COM_MODULES_EXTENSION_PUBLISHED_DISABLED',
				'COM_MODULES_HTML_UNPUBLISH_DISABLED',
				'COM_MODULES_EXTENSION_PUBLISHED_DISABLED',
				true,
				'warning',
				'warning'
			),
			-2	=> array(
				'publish',
				'COM_MODULES_EXTENSION_UNPUBLISHED_DISABLED',
				'COM_MODULES_HTML_PUBLISH_DISABLED',
				'COM_MODULES_EXTENSION_UNPUBLISHED_DISABLED',
				true,
				'unpublish',
				'unpublish'
			),
		);

		return Html::grid('state', $states, $value, $i, 'modules.', $enabled, true, $checkbox);
	}

	/**
	 * Display a batch widget for the module position selector.
	 *
	 * @param   integer  $clientId  The client ID
	 *
	 * @return  string  The necessary HTML for the widget.
	 *
	 * @since   2.5
	 */
	public static function positions($clientId)
	{
		// Create the copy/move options.
		$options = array(
			Html::select('option', 'c', Lang::txt('JLIB_HTML_BATCH_COPY')),
			Html::select('option', 'm', Lang::txt('JLIB_HTML_BATCH_MOVE'))
		);

		// Create the batch selector to change select the category by which to move or copy.
		$lines = array(
			'<label id="batch-choose-action-lbl" for="batch-choose-action">',
			Lang::txt('COM_MODULES_BATCH_POSITION_LABEL'),
			'</label>',
			'<fieldset id="batch-choose-action" class="combo">',
			'<select name="batch[position_id]" class="inputbox" id="batch-position-id">',
			'<option value="">' . Lang::txt('JSELECT') . '</option>',
			'<option value="nochange">' . Lang::txt('COM_MODULES_BATCH_POSITION_NOCHANGE') . '</option>',
			'<option value="noposition">' . Lang::txt('COM_MODULES_BATCH_POSITION_NOPOSITION') . '</option>',
			Html::select('options',	self::positionList($clientId)),
			'</select>',
			Html::select('radiolist', $options, 'batch[move_copy]', '', 'value', 'text', 'm'),
			'</fieldset>'
		);

		return implode("\n", $lines);
	}

	/**
	 * Method to get the field options.
	 *
	 * @param   integer  $clientId  The client ID
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   2.5
	 */
	public static function positionList($clientId = 0)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select('DISTINCT(position) as value');
		$query->select('position as text');
		$query->from($db->quoteName('#__modules'));
		$query->where($db->quoteName('client_id') . ' = ' . (int) $clientId);
		$query->order('position');

		// Get the options.
		$db->setQuery($query);

		$options = $db->loadObjectList();

		// Check for a database error.
		if ($db->getErrorNum())
		{
			throw new Exception($db->getErrorMsg(), 500);
		}

		// Pop the first item off the array if it's blank
		if (strlen($options[0]->text) < 1)
		{
			array_shift($options);
		}

		return $options;
	}
}
