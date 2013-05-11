<?php
/**
 * @package    FrameworkOnFramework
 * @subpackage hal
 * @copyright  Copyright (C) 2010 - 2012 Akeeba Ltd. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */
defined('_JEXEC') or die();

/**
 * Implementation of the Hypertext Application Language document in PHP. It can
 * be used to provide hypermedia in a web service context.
 */
class FOFHalDocument
{
	/**
	 * The collection of links of this document
	 *
	 * @var   FOFHalLinks
	 */
	private $_links = null;

	/**
	 * The data (resource state or collection of resource state objects) of the
	 * document.
	 *
	 * @var   array
	 */
	private $_data = null;

	/**
	 * Embedded documents. This is an array of FOFHalDocument instances.
	 *
	 * @var   array
	 */
	private $_embedded = array();

	/**
	 * When $_data is an array we'll output the list of data under this key
	 * (JSON) or tag (XML)
	 *
	 * @var   string
	 */
	private $_dataKey = '_list';

	/**
	 * Public constructor
	 *
	 * @param   mixed  $data  The data of the document (usually, the resource state)
	 */
	public function __construct($data = null)
	{
		$this->_data = $data;
		$this->_links = new FOFHalLinks();
	}

	/**
	 * Add a link to the document
	 *
	 * @see FOFHalLinks::addLink
	 *
	 * @return  boolean
	 */
	public function addLink($rel, FOFHalLink $link, $overwrite = true)
	{
		return $this->_links->addLink($rel, $link, $overwrite);
	}

	/**
	 * Add links to the document
	 *
	 * @see FOFHalLinks::addLinks
	 *
	 * @return  boolean
	 */
	public function addLinks($rel, array $links, $overwrite = true)
	{
		return $this->_links->addLinks($rel, $links, $overwrite);
	}

	/**
	 * Add data to the document
	 *
	 * @param   stdClass  $data       The data to add
	 * @param   boolean   $overwrite  Should I overwrite existing data?
	 */
	public function addData($data, $overwrite = true)
	{
		if (is_array($data))
		{
			$data = (object)$data;
		}

		if ($overwrite)
		{
			$this->_data = $data;
		}
		else
		{
			if (!is_array($this->_data))
			{
				$this->_data = array($this->_data);
			}

			$this->_data[] = $data;
		}
	}

	/**
	 * Add an embedded document
	 *
	 * @param   string          $rel        The relation of the embedded document to its container document
	 * @param   FOFHalDocument  $document   The document to add
	 * @param   boolean         $overwrite  Should I overwrite existing data with the same relation?
	 *
	 * @return  boolean
	 */
	public function addEmbedded($rel, FOFHalDocument $document, $overwrite = true)
	{
		if (!array_key_exists($rel, $this->_embedded) || !$overwrite)
		{
			$this->_embedded[$rel] = $document;
		}
		elseif (array_key_exists($rel, $this->_embedded) && !$overwrite)
		{
			if (!is_array($this->_embedded[$rel]))
			{
				$this->_embedded[$rel] = array($this->_embedded[$rel]);
			}
			$this->_embedded[$rel][] = $document;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Returns the collection of links of this document
	 */
	public function getLinks($rel)
	{
		return $this->_links->getLinks($rel);
	}

	/**
	 * Returns the collection of embedded documents
	 *
	 * @param   string  $rel  Optional; the relation to return the embedded documents for
	 *
	 * @return  array|FOFHalDocument
	 */
	public function getEmbedded($rel = null)
	{
		if (empty($rel))
		{
			return $this->_embedded;
		}
		elseif (isset($this->_embedded[$rel]))
		{
			return $this->_embedded[$rel];
		}
		else
		{
			return array();
		}
	}

	public function getData()
	{
		return $this->_data;
	}

	public function render($format = 'json')
	{
		$class_name = 'FOFHalRender' . ucfirst($format);

		if (!class_exists($class_name, true))
		{
			throw new Exception("Unsupported HAL Document format '$format'. Render aborted.");
		}

		$renderer = new $class_name($this);

		return $renderer->render(array(
			'data_key'		=> $this->_dataKey
		));
	}
}