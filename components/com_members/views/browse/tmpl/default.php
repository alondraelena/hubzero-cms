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

$juser =& JFactory::getUser();
?>
<div id="content-header" class="full">
	<h2><?php echo $this->title; ?></h2>
</div><!-- / #content-header -->

<div class="main section">
	<form action="<?php echo JRoute::_('index.php?option='.$this->option); ?>" method="post">
		<div class="aside">
			<fieldset>
<?php if ($this->view != 'contributors') { ?>
				<label>
					<?php echo JText::_('SHOW'); ?>
					<select name="show">
						<option value=""<?php if ($this->filters['show'] != 'contributors') { echo ' selected="selected"'; } ?>><?php echo JText::_('OPTION_ALL'); ?></option>
						<option value="contributors"<?php if ($this->filters['show'] == 'contributors') { echo ' selected="selected"'; } ?>><?php echo JText::_('OPTION_CONTRIBUTORS'); ?></option>
					</select>
				</label>
<?php } ?>
				<label>
					<?php echo JText::_('SORT_BY'); ?>
					<select name="sortby">
						<option value="lname ASC, fname ASC"<?php if ($this->filters['sortby'] == 'lname ASC, fname ASC') { echo ' selected="selected"'; } ?>><?php echo JText::_('Last Name (default)'); ?></option>
						<option value="fname ASC, lname ASC"<?php if ($this->filters['sortby'] == 'fname ASC, lname ASC') { echo ' selected="selected"'; } ?>><?php echo JText::_('First Name'); ?></option>
						<option value="organization"<?php if ($this->filters['sortby'] == 'organization') { echo ' selected="selected"'; } ?>><?php echo JText::_('OPTION_ORGANIZATION'); ?></option>
						<option value="rcount DESC"<?php if ($this->filters['sortby'] == 'rcount DESC') { echo ' selected="selected"'; } ?>><?php echo JText::_('OPTION_CONTRIBUTIONS'); ?></option>
					</select>
				</label>
				<label>
					<?php echo JText::_('SEARCH_NAME'); ?>
					<input type="text" name="search" value="<?php echo $this->filters['search']; ?>" />
				</label>
				<input type="submit" name="go" value="<?php echo JText::_('GO'); ?>" />
				<input type="hidden" name="option" value="<?php echo $this->option; ?>" />
				<input type="hidden" name="index" value="<?php echo $this->filters['index']; ?>" />
			</fieldset>
		</div><!-- / .aside -->
		<div class="subject">
			<p id="letter-index">
<?php 
$qs = array();
foreach ($this->filters as $f=>$v) 
{
	$qs[] = ($v != '' && $f != 'index' && $f != 'authorized' && $f != 'start') ? $f.'='.$v : '';
}
$qs[] = 'limitstart=0';
$qs = implode(a,$qs);

$letters = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');

$url  = 'index.php?option='.$this->option;
$url .= ($qs != '') ? '&'.$qs : '';
$html  = '<a href="'.JRoute::_($url).'"';
if ($this->filters['index'] == '') {
	$html .= ' class="active-index"';
}
$html .= '>'.JText::_('ALL').'</a> ';
foreach ($letters as $letter)
{
	$url  = 'index.php?option='.$this->option.'&index='.strtolower($letter);
	$url .= ($qs != '') ? '&'.$qs : '';
	
	$html .= '<a href="'.JRoute::_($url).'"';
	if ($this->filters['index'] == strtolower($letter)) {
		$html .= ' class="active-index"';
	}
	$html .= '>'.$letter.'</a> ';
}
echo $html;
?>
			</p>
<?php
if (count($this->rows) > 0) {
	// Get plugins
	JPluginHelper::importPlugin( 'members' );
	$dispatcher =& JDispatcher::getInstance();
	
	$areas = array();
	$activeareas = $dispatcher->trigger( 'onMembersContributionsAreas', array($this->authorized) );
	foreach ($activeareas as $area) 
	{
		$areas = array_merge( $areas, $area );
	}
	
	$cols = 2;
?>
			<table id="members" summary="<?php echo JText::_('TABLE_SUMMARY'); ?>">
<?php 
				/*<thead>
					<tr>
						<th scope="col"><?php echo JText::_('COL_NAME'); ?></th>
						<th scope="col"><?php echo JText::_('COL_ORGANIZATION'); ?></th>
						<th scope="col"><?php echo JText::_('COL_CONTRIBUTIONS'); ?></th>
					</tr>
				</thead>*/ 
?>
				<tbody>
<?php
	$cls = 'even';

	// Default thumbnail
	$config =& JComponentHelper::getParams( 'com_members' );
	$thumb = $config->get('webpath');
	if (substr($thumb, 0, 1) != DS) {
		$thumb = DS.$thumb;
	}
	if (substr($thumb, -1, 1) == DS) {
		$thumb = substr($thumb, 0, (strlen($thumb) - 1));
	}
	$dfthumb = $config->get('defaultpic');
	if (substr($dfthumb, 0, 1) != DS) {
		$dfthumb = DS.$dfthumb;
	}
	$dfthumb = Hubzero_View_Helper_Html::thumbit($dfthumb);

	foreach ($this->rows as $row)
	{
		$cls = ($cls == 'odd') ? 'even' : 'odd';
		if ($row->public != 1) {
			$prvt = ' private';
		} else {
			$prvt = '';
		}
		
		$row->name = stripslashes($row->name);
		$row->surname = stripslashes($row->surname);
		$row->givenName = stripslashes($row->givenName);
		$row->middelName = stripslashes($row->middleName);
		
		if (!$row->surname) {
			$bits = explode(' ', $row->name);
			$row->surname = array_pop($bits);
			if (count($bits) >= 1) {
				$row->givenName = array_shift($bits);
			}
			if (count($bits) >= 1) {
				$row->middleName = implode(' ',$bits);
			}
		}
		
		// Get the search result totals
		$totals = $dispatcher->trigger( 'onMembersContributions', array(
				$row,
				$this->option,
				$this->authorized,
				0,
				-1, 
				NULL,
				NULL,
				$areas)
			);

		// Get the total results found (sum of all categories)
		$i = 0;
		$total = 0;
		$cats = array();
		foreach ($areas as $c=>$t) 
		{
			$cats[$i]['category'] = $c;

			// Do sub-categories exist?
			if (is_array($t) && !empty($t)) {
				// They do - do some processing
				$cats[$i]['title'] = ucfirst($c);
				$cats[$i]['total'] = 0;
				$cats[$i]['_sub'] = array();
				$z = 0;
				// Loop through each sub-category
				foreach ($t as $s=>$st) 
				{
					// Ensure a matching array of totals exist
					if (is_array($totals[$i]) && !empty($totals[$i]) && isset($totals[$i][$z])) {
						// Add to the parent category's total
						$cats[$i]['total'] = $cats[$i]['total'] + $totals[$i][$z];
						// Get some info for each sub-category
						$cats[$i]['_sub'][$z]['category'] = $s;
						$cats[$i]['_sub'][$z]['title'] = $st;
						$cats[$i]['_sub'][$z]['total'] = $totals[$i][$z];
					}
					$z++;
				}
			} else {
				// No sub-categories - this should be easy
				$cats[$i]['title'] = $t;
				$cats[$i]['total'] = (!is_array($totals[$i])) ? $totals[$i] : 0;
			}

			// Add to the overall total
			$total = $total + intval($cats[$i]['total']);
			$i++;
		}

		$tt = array();
		foreach ($cats as $cat) 
		{
			$tt[] = $cat['total'].' '.$cat['title'];
		}

		if ($row->uidNumber < 0) {
			$id = 'n' . -$row->uidNumber;
		} else {
			$id = $row->uidNumber;
		}
		
		if ($this->filters['sortby'] == 'fname ASC, lname ASC') {
			$name  = ($row->givenName) ? stripslashes($row->givenName) : '';
			$name .= ($row->middleName) ? ' '.stripslashes($row->middleName) : '';
			$name .= ($row->surname) ? ' '.stripslashes($row->surname) : '';
		} else {
			$name = ($row->surname) ? stripslashes($row->surname) : '';
			if ($row->givenName) {
				$name .= ($row->surname) ? ', ' : '';
				$name .= stripslashes($row->givenName);
				$name .= ($row->middleName) ? ' '.stripslashes($row->middleName) : '';
			}
		}
		if (!trim($name)) {
			$name = 'Unknown ('.$row->username.')';
		}
		
		$uthumb = '';
		if ($row->picture) {
			$uthumb = $thumb.DS.Hubzero_View_Helper_Html::niceidformat($row->uidNumber).DS.$row->picture;
			$uthumb = Hubzero_View_Helper_Html::thumbit($uthumb);
		}

		if ($uthumb && is_file(JPATH_ROOT.$uthumb)) {
			$p = $uthumb;
		} else {
			$p = $dfthumb;
		}
?>
					<tr class="<?php echo $cls.$prvt; ?>">
						<td class="photo"><img width="50" height="50" src="<?php echo $p; ?>" alt="Photo for <?php echo htmlentities($name,ENT_COMPAT,'UTF-8'); ?>" /></td>
						<td>
							<span class="name"><a href="<?php echo JRoute::_('index.php?option='.$this->option.'&id='.$id); ?>"><?php echo $name; ?></a></span><br />
							<span class="organization"><?php echo Hubzero_View_Helper_Html::xhtml(stripslashes($row->organization)); ?></span>
						</td>
						<td><span class="activity"><?php echo implode(', ',$tt); ?></span></td>
<?php
if (!$juser->get('guest') && $row->uidNumber > 0 && $row->uidNumber != $juser->get('id')) {
	echo "\t\t\t\t".'<td class="message-member"><a class="message tooltips" href="'.JRoute::_('index.php?option='.$this->option.'&id='.$juser->get('id').'&active=messages&task=new&to='.$row->uidNumber).'" title="Message :: Send a message to '.htmlentities($name,ENT_COMPAT,'UTF-8').'">'.JText::_('Send a message to '.htmlentities($name,ENT_COMPAT,'UTF-8')).'</a></td>'."\n";
} else {
	echo "\t\t\t\t".'<td class="message-member"> </td>'."\n";
}
?>
					</tr>
<?php
	}
?>
				</tbody>
			</table>
<?php
	$pn = $this->pageNav->getListFooter();
	$pn = str_replace('/?/&amp;','/?',$pn);
	$f = '';
	foreach ($this->filters as $k=>$v) 
	{
		$f .= ($v && $k != 'authorized' && $k != 'limit' && $k != 'start') ? $k.'='.$v.'&amp;' : '';
	}
	$pn = str_replace('?','?'.$f,$pn);
	$pn = str_replace('&amp;&amp;','&amp;',$pn);
	echo $pn;
} else { ?>
	<p class="warning"><?php echo JText::_('NO_MEMBERS_FOUND'); ?></p>
<?php } ?>
		</div><!-- / .subject -->
	</form>
</div><!-- / .main section -->