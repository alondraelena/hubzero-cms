<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2015 HUBzero Foundation, LLC.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * HUBzero is a registered trademark of Purdue University.
 *
 * @package   hubzero-cms
 * @author    Shawn Rice <zooley@purdue.edu>
 * @copyright Copyright 2005-2015 HUBzero Foundation, LLC.
 * @license   http://opensource.org/licenses/MIT MIT
 */

namespace Components\Events\Admin\Controllers;

use Components\Events\Tables\Page;
use Components\Events\Tables\Event;
use Hubzero\Component\AdminController;
use Exception;

/**
 * Events controller for pages
 */
class Pages extends AdminController
{
	/**
	 * Display a list of entries for an event
	 *
	 * @return     void
	 */
	public function displayTask()
	{
		$ids = Request::getVar('id', array(0));
		$ids = (!is_array($ids) ? array($ids) : $ids);
		if (count($ids) < 1)
		{
			App::redirect(
				Route::url('index.php?option=' . $this->_option, false)
			);
			return;
		}

		// Get filters
		$this->view->filters = array();
		$this->view->filters['event_id'] = $ids[0];
		$this->view->filters['search']   = urldecode(Request::getState(
			$this->_option . '.' . $this->_controller . '.search',
			'search',
			''
		));
		$this->view->filters['limit']    = Request::getState(
			$this->_option . '.' . $this->_controller . '.limit',
			'limit',
			Config::get('list_limit'),
			'int'
		);
		$this->view->filters['start']    = Request::getState(
			$this->_option . '.' . $this->_controller . '.limitstart',
			'limitstart',
			0,
			'int'
		);

		$obj = new Page($this->database);

		// Get a record count
		$this->view->total = $obj->getCount($this->view->filters);

		// Get records
		$this->view->rows  = $obj->getRecords($this->view->filters);

		$this->view->event = new Event($this->database);
		$this->view->event->load($ids[0]);

		// Output the HTML
		$this->view->display();
	}

	/**
	 * Show a form for adding an entry
	 *
	 * @return     void
	 */
	public function addTask()
	{
		$id = Request::getVar('id', array());
		if (is_array($id))
		{
			$id = (!empty($id)) ? $id[0] : 0;
		}

		Request::setVar('id', array());
		$this->editTask($id);
	}

	/**
	 * Show a form for editing an entry
	 *
	 * @param      integer $eid Event ID
	 * @return     void
	 */
	public function editTask($eid=null)
	{
		$this->view->setLayout('edit');

		// Incoming
		$eid = ($eid) ? $eid : Request::getInt('event', 0);

		$id = Request::getVar('id', array());
		if (is_array($id))
		{
			$id = (!empty($id)) ? $id[0] : 0;
		}

		// Initiate database class and load info
		$this->view->page = new Page($this->database);
		$this->view->page->load($id);

		$this->view->event = new Event($this->database);
		$this->view->event->load($eid);

		// Set any errors
		foreach ($this->getErrors() as $error)
		{
			$this->view->setError($error);
		}

		// Output the HTML
		$this->view->display();
	}

	/**
	 * Save an entry
	 *
	 * @return     void
	 */
	public function saveTask()
	{
		// Check for request forgeries
		Request::checkToken();

		// Bind incoming data to object
		$row = new Page($this->database);
		if (!$row->bind($_POST))
		{
			throw new Exception($row->getError(), 500);
		}

		if (!$row->alias)
		{
			$row->alias = $row->title;
		}

		$row->event_id = Request::getInt('event', 0);
		$row->alias = preg_replace("/[^a-zA-Z0-9]/", '', $row->alias);
		$row->alias = strtolower($row->alias);

		//set created date and user
		if ($row->id == NULL || $row->id == '' || $row->id == 0)
		{
			$row->created = \Date::toSql();
			$row->created_by = User::get('id');
		}

		//set modified date and user
		$row->modified = \Date::toSql();
		$row->modified_by = User::get('id');

		// Check content for missing required data
		if (!$row->check())
		{
			throw new Exception($row->getError(), 500);
		}

		// Store new content
		if (!$row->store())
		{
			throw new Exception($row->getError(), 500);
		}

		// Redirect
		App::redirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&id[]=' . $row->event_id, false),
			Lang::txt('COM_EVENTS_PAGE_SAVED')
		);
	}

	/**
	 * Remove one or more entries for an event
	 *
	 * @return     void
	 */
	public function removeTask()
	{
		// Check for request forgeries
		Request::checkToken();

		// Incoming
		$event = Request::getInt('event', 0);
		$ids   = Request::getVar('id', array(0));

		// Get the single ID we're working with
		if (!is_array($ids))
		{
			$ids = array();
		}

		// Do we have any IDs?
		if (!empty($ids))
		{
			$page = new Page($this->database);

			// Loop through each ID and delete the necessary items
			foreach ($ids as $id)
			{
				// Remove the profile
				$page->delete($id);
			}
		}

		// Output messsage and redirect
		App::redirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&id[]=' . $event, false),
			Lang::txt('EVENTS_PAGES_REMOVED')
		);
	}

	/**
	 * Move an item up one in the ordering
	 *
	 * @return     void
	 */
	public function orderupTask()
	{
		$this->reorderTask('up');
	}

	/**
	 * Move an item down one in the ordering
	 *
	 * @return     void
	 */
	public function orderdownTask()
	{
		$this->reorderTask('down');
	}

	/**
	 * Move an item one down or own up int he ordering
	 *
	 * @param      string $move Direction to move
	 * @return     void
	 */
	protected function reorderTask($move='down')
	{
		// Check for request forgeries
		Request::checkToken(['get', 'post']);

		// Incoming
		$id = Request::getVar('id', array());
		$id = $id[0];
		$pid = Request::getInt('event', 0);

		// Ensure we have an ID to work with
		if (!$id)
		{
			App::redirect(
				Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller, false),
				Lang::txt('COM_EVENTS_PAGE_NO_ID'),
				'error'
			);
			return;
		}

		// Ensure we have a parent ID to work with
		if (!$pid)
		{
			App::redirect(
				Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller, false),
				Lang::txt('COM_EVENTS_PAGE_NO_EVENT_ID'),
				'error'
			);
			return;
		}

		// Get the element moving down - item 1
		$page1 = new Page($this->database);
		$page1->load($id);

		// Get the element directly after it in ordering - item 2
		$page2 = clone($page1);
		$page2->getNeighbor($this->_task);

		switch ($move)
		{
			case 'up':
				// Switch places: give item 1 the position of item 2, vice versa
				$orderup = $page2->ordering;
				$orderdn = $page1->ordering;

				$page1->ordering = $orderup;
				$page2->ordering = $orderdn;
			break;

			case 'down':
				// Switch places: give item 1 the position of item 2, vice versa
				$orderup = $page1->ordering;
				$orderdn = $page2->ordering;

				$page1->ordering = $orderdn;
				$page2->ordering = $orderup;
			break;
		}

		// Save changes
		$page1->store();
		$page2->store();

		// Redirect
		App::redirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&id[]=' . $pid, false)
		);
	}

	/**
	 * Cancel a task by redirecting to main page
	 *
	 * @return     void
	 */
	public function cancelTask()
	{
		App::redirect(
			Route::url('index.php?option=' . $this->_option . '&controller=' . $this->_controller . '&id[]=' . Request::getInt('event', 0), false)
		);
	}
}

