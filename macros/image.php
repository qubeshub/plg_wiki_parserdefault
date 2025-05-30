<?php
/**
 * @package    hubzero-cms
 * @copyright  Copyright (c) 2005-2020 The Regents of the University of California.
 * @license    http://opensource.org/licenses/MIT MIT
 */

// No direct access
defined('_HZEXEC_') or die();

/**
 * A wiki macro for embedding images
 */
class ImageMacro extends WikiMacro
{
	/**
	 * Allow macro in partial parsing?
	 *
	 * @var string
	 */
	public $allowPartial = true;

	/**
	 * Container for element attributes
	 *
	 * @var array
	 */
	private $attr = array();

	/**
	 * Returns description of macro, use, and accepted arguments
	 *
	 * @return     string
	 */
	public function description()
	{
		$txt = "Embed an image in wiki-formatted text.

The first argument is the file specification. The file specification may reference attachments or files in three ways:
 * `module:id:file`, where module can be either '''wiki''' or '''ticket''', to refer to the attachment named ''file'' of the specified wiki page or ticket.
 * `id:file`: same as above, but id is either a ticket shorthand or a Wiki page name.
 * `file` to refer to a local attachment named 'file'. This only works from within that wiki page or a ticket.

Also, the file specification may refer to repository files, using the `source:file` syntax (`source:file@rev` works also).

The remaining arguments are optional and allow configuring the attributes and style of the rendered `<img>` element:
 * digits and unit are interpreted as the size (ex. 120, 25%) for the image
 * `right`, `left`, `top` or `bottom` are interpreted as the alignment for the image
 * `link=some TracLinks...` replaces the link to the image source by the one specified using a TracLinks. If no value is specified, the link is simply removed.
 * `nolink` means without link to image source (deprecated, use `link=`)
 * `key=value` style are interpreted as HTML attributes or CSS style indications for the image. Valid keys are:
 * align, border, width, height, alt, title, longdesc, class, id and usemap
 * `border` can only be a number

Examples:
{{{
[[Image(photo.jpg)]]               # simplest
[[Image(photo.jpg, 120px)]]	       # with image width size
[[Image(photo.jpg, right)]]        # aligned by keyword
[[Image(photo.jpg, nolink)]]       # without link to source
[[Image(photo.jpg, align=right)]]  # aligned by attribute
[[Image(photo.jpg, 120px, class=mypic)]]  # with image width size and a CSS class
}}}

You can use image from other page, other ticket or other module.
{{{
[[Image(OtherPage:foo.bmp)]]       # if current module is wiki
[[Image(base/sub:bar.bmp)]]        # from hierarchical wiki page
[[Image(#3:baz.bmp)]]		       # if in a ticket, point to #3
[[Image(ticket:36:boo.jpg)]]
[[Image(source:/images/bee.jpg)]]  # straight from the repository!
[[Image(htdocs:foo/bar.png)]]      # image file in project htdocs dir.
}}}
";
		$txt = array();
		$txt['wiki'] = "Embed an image in wiki-formatted text. The first argument is the file specification, either as `file` for the current page or `Page:file` for `file` located on the wiki page with name `Page`. The remaining arguments are optional and allow configuring the attributes and style of the rendered `img` element:
 * digits and unit are interpreted as the size (ex. 120, 25%) for the image
 * `right`, `left`, `top` or `bottom` are interpreted as the alignment for the image
 * `link=some Link...` replaces the link to the image source by the one specified using Link. If no value is specified, the link is simply removed.
 * `nolink` means without link to image source (deprecated, use `link=`)
 * `nofigure` means no border or clickable image
 * `key=value` style are interpreted as HTML attributes or CSS style indications for the image. Valid keys are:
 * align, border, width, height, alt, title, longdesc, class, id and usemap
 * `border` can only be a number

Examples:
 * Image(photo.jpg) # simplest
 * Image(OtherPage:foo.bmp) # use file from OtherPage
 * Image(photo.jpg, 120px) # with image width size
 * Image(photo.jpg, right) # aligned by keyword
 * Image(photo.jpg, nolink)       # without link to source
 * Image(photo.jpg, nofigure) # non-clickable image
 * Image(photo.jpg, align=right)  # aligned by attribute
 * Image(photo.jpg, 120px, class=mypic) # with image width size and a CSS class
";
$txt['html'] = '<p>Embed an image in wiki-formatted text. The first argument is the file specification, either as <code>file</code> for the current page or <code>Page:file</code> for <code>file</code> located on the wiki page with name <code>Page</code>. The remaining arguments are optional and allow configuring the attributes and style of the rendered <code>&lt;img&gt;</code> element:</p>
<ul>
<li>digits and unit are interpreted as the size (ex. 120, 25%) for the image</li>
<li><code>right</code>, <code>left</code>, <code>top</code> or <code>bottom</code> are interpreted as the alignment for the image</li>
<li><code>link=some Link...</code> replaces the link to the image source by the one specified using Link. If no value is specified, the link is simply removed.</li>
<li><code>nolink</code> means without link to image source (deprecated, use <code>link=</code>)</li>
<li><code>nofigure</code> means no border or clickable image</li>
<li><code>key=value</code> style are interpreted as HTML attributes or CSS style indications for the image. Valid keys are:</li>
<li>align, border, width, height, alt, title, longdesc, class, id and usemap</li>
<li><code>border</code> can only be a number</li>
</ul>
<p>Examples:</p>
<ul>
<li><code>[[Image(photo.jpg)]]</code> # simplest</li>
<li><code>[[Image(OtherPage:foo.bmp)]]</code> # use file from OtherPage</li>
<li><code>[[Image(photo.jpg, desc="My caption here")]]</code> # caption text</li>
<li><code>[[Image(photo.jpg, 120px)]]</code> # with image width size</li>
<li><code>[[Image(photo.jpg, right)]]</code> # aligned by keyword</li>
<li><code>[[Image(photo.jpg, nolink)]]</code>       # without link to source</li>
<li><code>[[Image(photo.jpg, nofigure)]]</code> # non-clickable image</li>
<li><code>[[Image(photo.jpg, align=right)]]</code>  # aligned by attribute</li>
<li><code>[[Image(photo.jpg, 120px, class=mypic)]]</code> # with image width size and a CSS class</li>
</ul>';

		return $txt['html'];
	}

	/**
	 * Generate macro output based on passed arguments
	 *
	 * @return     string HTML image tag on success or error message on failure
	 */
	public function render()
	{
		$content = $this->args;

		// args will be null if the macro is called without parenthesis.
		if (!$content)
		{
			return '';
		}

		// Parse arguments
		// We expect the 1st argument to be a filename
		$args   = explode(',', $content);
		$file   = array_shift($args);

		$this->attr   = array();
		$this->attr['href']  = '';
		$this->attr['style'] = array();

		// Get single attributes
		// EX: [[Image(myimage.png, nolink, right)]]
		$argues = preg_replace_callback('/[, ](left|right|top|center|bottom|nofigure|[0-9]+(px|%|em)?)(?:[, ]|$)/i', array(&$this, 'parseSingleAttribute'), $content);
		// Get quoted attribute/value pairs
		// EX: [[Image(myimage.png, desc="My description, contains, commas")]]
		$argues = preg_replace_callback('/[, ](alt|altimage|desc|title|width|height|align|border|longdesc|class|id|usemap|link)=(?:["\'])([^"]*)(?:["\'])/i', array(&$this, 'parseAttributeValuePair'), $content);
		// Get non-quoted attribute/value pairs
		// EX: [[Image(myimage.png, width=100)]]
		$argues = preg_replace_callback('/[, ](alt|altimage|desc|title|width|height|align|border|longdesc|class|id|usemap|link)=([^"\',]*)(?:[, ]|$)/i', array(&$this, 'parseAttributeValuePair'), $content);

		$attr = $this->attr;

		// Get wiki config
		$this->config = Component::params('com_wiki');
		if ($this->filepath != '')
		{
			$this->config->set('filepath', $this->filepath);
		}

		$this->imgs = array('jpg', 'jpeg', 'jpe', 'bmp', 'tif', 'tiff', 'png', 'gif', 'jpeg2', 'jpe2', 'jp2', 'jpg2', 'svg');

		$ret = false;
		// Is it numeric?
		if (is_numeric($file))
		{
			// Get resource by ID
			$attach = \Components\Wiki\Models\Attachment::oneOrNew(intval($file));

			// Check for file existence
			if ($attach->get('filename') && file_exists($this->_path($attach->get('filename')))
			 || file_exists($this->_path($attach->get('filename'), true)))
			{
				$attr['desc'] = (isset($attr['desc'])) ? $attr['desc'] : '';
				if (!$attr['desc'])
				{
					$attr['desc'] = $attach->get('description', '');
				}

				$ret = true;
			}
		}
		else if (preg_match("/^(https?:|mailto:|ftp:|gopher:|news:|file:)/", $file))
		{
			$attr['desc'] = (isset($attr['desc'])) ? $attr['desc'] : '';

			$ret = true;
		}
		// Check for file existence
		else 
		{
			$bits = explode(':', $file);
			if (isset($bits[1])) {
				$page = \Components\Wiki\Models\Page::oneByTitle($bits[0]);
				if (!$page) {
					return '(Image failed - Wiki page not found)';
				}
				$this->pageid = $page->get('id');
				$file = $bits[1];
			}
			if (file_exists($this->_path($file)) || file_exists($this->_path($file, true))) {
				$attr['desc'] = (isset($attr['desc'])) ? $attr['desc'] : '';

				$ret = true;
			}
		}

		// Does the file exist?
		if ($ret)
		{
			if (!in_array(strtolower(Filesystem::extension($file)), $this->imgs))
			{
				return '(Image(' . $content . ') failed - File provided is not an allowed image type)';
			}

			// Return HTML
			return $this->_embed($file, $attr);
		}

		// Return error message
		return '(Image(' . $content . ') failed - File not found)';
	}

	/**
	 * Parse attribute=value pairs
	 * EX: [[Image(myimage.png, desc="My description, contains, commas", width=200)]]
	 *
	 * @param   array  $matches  Values matching attr=val pairs
	 * @return  void
	 */
	public function parseAttributeValuePair($matches)
	{
		$key = strtolower(trim($matches[1]));
		$val = trim($matches[2]);

		$size   = '/^[0-9]+(%|px|em)+$/';
		$attrs  = '/(alt|altimage|desc|title|width|height|align|border|longdesc|class|id|usemap)=(.+)/';
		$quoted = "/(?:[\"'])(.*)(?:[\"'])$/";

		// Set width if just a pixel size is given
		// e.g., [[File(myfile.jpg, width=120px)]]
		if (preg_match($size, $val, $matches) && $key != 'border')
		{
			if ($matches[0])
			{
				$this->attr['style']['width'] = $val;
				$this->attr['width'] = intval($val);
				return;
			}
		}

		if (is_numeric($val))
		{
			$this->attr['style']['width'] = $val . 'px';
			$this->attr['width'] = $val;
		}
		// Specific call to NOT link an image
		// Links images by default
		if ($key == 'nolink')
		{
			$this->attr['href'] = 'none';
			return;
		}
		// Check for a specific link given
		if ($key == 'link')
		{
			$this->attr['href'] = 'none';

			if ($val)
			{
				$this->attr['href'] = $val;

				$urlPtrn  = "[^=\"\']*(https?:|mailto:|ftp:|gopher:|news:|file:)" . "([^ |\\/\"\']*\\/)*([^ |\\t\\n\\/\"\']*[A-Za-z0-9\\/?=&~_])";
				if (preg_match("/$urlPtrn/", $val))
				{
					$this->attr['rel']  = 'external';
				}
			}
			return;
		}
		// Check for alignment, no key given
		// e.g., [[File(myfile.jpg, left)]]
		if (in_array($key, array('left', 'right', 'top', 'bottom', 'center')))
		{
			if ($key == 'center')
			{
				$this->attr['style']['display'] = 'block';
				$this->attr['style']['margin-right'] = 'auto';
				$this->attr['style']['margin-left'] = 'auto';
			}
			else
			{
				$this->attr['style']['float'] = $key;
				if ($key == 'left')
				{
					$this->attr['style']['margin-right'] = '1em';
				}
				else if ($key == 'right')
				{
					$this->attr['style']['margin-left'] = '1em';
				}
			}
			return;
		}

		// Look for any other attributes
		if ($key == 'align')
		{
			if ($val == 'center')
			{
				$this->attr['style']['display'] = 'block';
				$this->attr['style']['margin-right'] = 'auto';
				$this->attr['style']['margin-left'] = 'auto';
			}
			else
			{
				$this->attr['style']['float'] = $val;
				if ($val == 'left')
				{
					$this->attr['style']['margin-right'] = '1em';
				}
				else if ($val == 'right')
				{
					$this->attr['style']['margin-left'] = '1em';
				}
			}
		}
		else if ($key == 'border')
		{
			$this->attr['style']['border'] = '#ccc ' . intval($val) . 'px solid';
		}
		else
		{
			$this->attr[$key] = $val;
		}
		return;
	}

	/**
	 * Handle single attribute values
	 * EX: [[Image(myimage.png, nolink, right)]]
	 *
	 * @param   array  $matches  Values matching the single attribute pattern
	 * @return  void
	 */
	public function parseSingleAttribute($matches)
	{
		$key = strtolower(trim($matches[1]));

		// Set width if just a pixel size is given
		// e.g., [[File(myfile.jpg, 120px)]]
		$size   = '/[0-9+](%|px|em)+$/';
		if (preg_match($size, $key, $matches))
		{
			if ($matches[0])
			{
				$this->attr['style']['width'] = $key;
				$this->attr['width'] = intval($key);
				return;
			}
		}

		if (is_numeric($key))
		{
			$this->attr['style']['width'] = $key . 'px';
			$this->attr['width'] = $key;
		}

		// Specific call to NOT link an image
		// Links images by default
		if ($key == 'nolink')
		{
			$this->attr['href'] = 'none';
			return;
		}

		if ($key == 'nofigure')
		{
			$this->attr['href'] = 'none';
			$this->attr['nofigure'] = true;
			return;
		}

		// Check for alignment, no key given
		// e.g., [[File(myfile.jpg, left)]]
		if (in_array($key, array('left', 'right', 'top', 'bottom', 'center')))
		{
			if ($key == 'center')
			{
				$this->attr['style']['display'] = 'block';
				$this->attr['style']['margin-right'] = 'auto';
				$this->attr['style']['margin-left'] = 'auto';
			}
			else
			{
				$this->attr['style']['display'] = 'block';
				$this->attr['style']['float'] = $key;
				if ($key == 'left')
				{
					$this->attr['style']['margin-right'] = '1em';
				}
				else if ($key == 'right')
				{
					$this->attr['style']['margin-left'] = '1em';
				}
			}
			return;
		}

		return;
	}

	/**
	 * Generate an absolute path to a file stored on the system
	 * Assumes $file is relative path but, if $file starts with / then assumes absolute
	 *
	 * @param   string  $file  Filename
	 * @param   boolean $alt   Switches between relative and absolute path?
	 * @return  string
	 */
	private function _path($file, $alt=false)
	{
		if (substr($file, 0, 1) == DS)
		{
			$path = PATH_ROOT . $file;
		}
		else
		{
			if ($alt)
			{
				$nid = null;
				$bits = explode('/', $this->config->get('filepath', '/site/wiki'));
				foreach ($bits as $bit)
				{
					if (is_numeric($bit))
					{
						$nid = $bit;
						$id = preg_replace('~^[0]*([1-9][0-9]*)$~', '$1', intval($bit));
						break;
					}
				}
				if ($nid)
				{
					$this->config->set('filepath', str_replace($nid, $id, $this->config->get('filepath')));
				}
			}
			$path  = PATH_APP . DS . trim($this->config->get('filepath', '/site/wiki'), DS);
			$path .= ($this->pageid) ? DS . $this->pageid : '';
			$path .= DS . $file;
		}

		return $path;
	}

	/**
	 * Generate a link to a file
	 * If $file starts with (http|https|mailto|ftp|gopher|feed|news|file), then it's an external URL and returned
	 *
	 * @param   string  $file  Filename
	 * @return  string
	 */
	private function _link($file)
	{
		$urlPtrn  = "[^=\"\'](https?:|mailto:|ftp:|gopher:|feed:|news:|file:)" . "([^ |\\/\"\']*\\/)*([^ |\\t\\n\\/\"\']*[A-Za-z0-9\\/?=&~_])";
		if (preg_match("/^(https?:|mailto:|ftp:|gopher:|news:|file:)/", $file) || preg_match("/$urlPtrn/", $file) || substr($file, 0, 1) == DS)
		{
			return $file;
		}

		$file = trim($file, DS);

		if (Request::getString('format') == 'pdf')
		{
			return $this->_path($file);
		}

		if ($this->pageid)
		{
			if ($this->pageid > 0) {
				$page = \Components\Wiki\Models\Page::oneOrFail($this->pageid);
				$link = $page->link();
			} else {
				// Short circuit for new pages
				$link = '/app/site/wiki/' . $this->pageid . '/' . $file;
				return Route::url($link);
			}
		}
		else
		{
			$link  = '/' . substr($this->option, 4, strlen($this->option)) . '/';
			if ($this->scope)
			{
				$scope = trim($this->scope, '/');

				$link .= $scope . '/';
			}
			$link .= $this->pagename;
		}
		$link = rtrim($link, '/');
		$link .= '/Image:' . $file;

		return Route::url($link);
	}

	/**
	 * Generates HTML to embed an <img>
	 *
	 * @param   string  $file  File to embed
	 * @param   array   $attr  Attributes to apply to the HTML
	 * @return  string
	 */
	private function _embed($file, $attr=array())
	{
		$attr['alt'] = (isset($attr['alt'])) ? htmlentities($attr['alt'], ENT_COMPAT, 'UTF-8') : $attr['desc'];
		if (!$attr['alt'])
		{
			$attr['alt'] = $file;
		}

		$styles = '';
		if (count($attr['style']) > 0)
		{
			$s = array();
			foreach ($attr['style'] as $k => $v)
			{
				$s[] = strtolower($k) . ':' . $v;
			}
			$styles = implode('; ', $s);
		}
		$attr['style'] = '';

		$attribs = array();
		foreach ($attr as $k => $v)
		{
			$k = strtolower($k);
			if ($k != 'href' && $k != 'rel' && $k != 'desc' && $v)
			{
				$attribs[] = $k . '="' . trim($v, '"') . '"';
			}
		}

		$html  = '<span' . (isset($attr['nofigure']) && $attr['nofigure'] ? ' ' : ' class="figure"') . ($styles ? ' style="' . $styles . '"' : '') . '>';

		$img = '<img src="' . $this->_link($file) . '" ' . implode(' ', $attribs) . ' />';

		if ($attr['href'] == 'none')
		{
			$html .= $img;
		}
		else
		{
			$attr['href'] = ($attr['href']) ? $attr['href'] : $this->_link($file);
			$attr['rel']  = (isset($attr['rel'])) ? $attr['rel'] : 'lightbox';

			$html .= '<a rel="' . $attr['rel'] . '" href="' . $attr['href'] . '">' . $img . '</a>';
		}
		if (isset($attr['desc']) && $attr['desc'])
		{
			$html .= '<span class="figcaption">' . $attr['desc'] . '</span>';
		}
		$html .= '</span>';

		return $html;
	}
}
