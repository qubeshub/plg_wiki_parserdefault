<?php
/**
 * HUBzero CMS
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
 *
 * @package   hubzero-cms
 * @author    Shawn Rice <zooley@purdue.edu>
 * @copyright Copyright 2005-2011 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

/**
 * Wiki macro class for dipslaying a twitter feed
 */
class TwitterMacro extends WikiMacro
{
	/**
	 * Returns description of macro, use, and accepted arguments
	 *
	 * @return     array
	 */
	public function description()
	{
		$txt = array();
		$txt['wiki'] = "Embeds a Twitter Feed into the page";
		$txt['html'] = '<p>Embeds a Twitter Feed into the page. Can be a user feed(@hubzero) or a Twitter widget ID(see below), followed by a comma(,) and then the number of tweets to display.</p>
						<p>A Twitter widget may be set up by going to <a href="https://www.twitter.com/settings/widgets">Twitter\'s widget configuration page</a>.  After setting a widget up, the widget ID can be found as an 18 digit number in the URL of the settings, or within the HTML in the text box labeled "Copy and paste the code into the HTML of your site" as the item data-widget-id="####".</p>
						<p>Examples:</p>
						<ul>
							<li><code>[[Twitter(@hubzeroplatform,2)]]</code></li>
						</ul>
						<p>Displays:</p>
						<a class="twitter-timeline"
							href="https://twitter.com/"
							data-widget-id="346714310770302976"
							data-screen-name="hubzeroplatform"
							data-tweet-limit="2"
							data-chrome=""
							>Loading Tweets...</a>
						<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?\'http\':\'https\';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+"://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>';

		return $txt['html'];
	}

	/**
	 * Generate macro output
	 *
	 * @return     string
	 */
	public function render()
	{
		//get the args passed in
		$args = array_map('trim', explode(',', $this->args));

		//get screen name & num tweets
		$screenName = (isset($args[0])) ? ltrim($args[0], '@') : '';
		$widgetId   = (preg_match('/widgetid="([^"]*)"/', $this->args, $matches)) ? $matches[1] : '';
		$chrome     = (preg_match('/chrome="([^"]*)"/', $this->args, $matches)) ? $matches[1] : '';
		$width      = (preg_match('/width="([^"]*)"/', $this->args, $matches)) ? $matches[1] : '100%';
		$height     = (preg_match('/height="([^"]*)"/', $this->args, $matches)) ? $matches[1] : 500;

		//make sure we have a user name
		if ($screenName == '' && $widgetId == '' || strpos($screenName, '#') !== FALSE)
		{
			return '(Please enter a valid Twitter Username/ID or Widget ID)';
		}

		// code for screename
		$atts   = array();
		$atts[] = 'data-widget-id="346714310770302976"';

		// twitter does not allow numeric usernames, this must be a widget id
		if (is_numeric($screenName))
		{
			$atts = array('data-widget-id="' . $screenName . '"');
			$screenName = '';
		}

		// pass already configured widget
		if ($widgetId)
		{
			$atts = array('data-widget-id="' . $widgetId . '"');
		}
		//no widget id, set up the screen name to show tweets from
		else
		{
			$atts[] = 'href="https://twitter.com/'. $screenName . '"';
			$atts[] = 'data-screen-name="' . $screenName . '"';
		}


		$atts[] = 'width="' . $width . '"';
		$atts[] = 'height="' . $height . '"';
		$atts[] = 'data-chrome="' . $chrome . '"';

		if (isset($args[1]) && is_numeric($args[1]))
		{
			$atts[] = 'data-tweet-limit="' . $args[1] . '"';
		}

		//output embeded timeline
		return '<a class="twitter-timeline" ' . implode(' ', $atts) . '>Loading Tweets...</a>
				<script>!function(d,s,id) {var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?\'http\':\'https\';if (!d.getElementById(id)) {js=d.createElement(s);js.id=id;js.src=p+"://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>';
	}
}
