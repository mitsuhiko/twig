<?php
/**
 * Twig::API
 * ~~~~~~~~~
 *
 * The High-Level API
 *
 * :copyright: 2008 by Armin Ronacher.
 * :license: BSD.
 */


/**
 * Load the compiler system.  Call this before you access the
 * compiler!
 */
function twig_load_compiler()
{
	if (!defined('TWIG_COMPILER_INCLUDED'))
		require TWIG_BASE . '/compiler.php';
}


/**
 * This class wraps a template instance as returned by the compiler and
 * is usually constructed from the `Twig_Loader`.
 */
class Twig_Template
{
	private $instance;

	public function __construct($instance)
	{
		$this->instance = $instance;
	}

	/**
	 * Render the template with the given context and return it
	 * as string.
	 */
	public function render($context=NULL)
	{
		ob_start();
		$this->display($context);
		return ob_end_clean();
	}

	/**
	 * Works like `render()` but prints the output.
	 */
	public function display($context=NULL)
	{
		if (is_null($context))
			$context = array();
		$this->instance->render($context);
	}
}

/**
 * Baseclass for custom loaders.  Subclasses have to provide a
 * getFilename method.
 */
class Twig_BaseLoader
{
	public $cache;

	public function __construct($cache)
	{
		$this->cache = $cache;
	}

	public function getTemplate($name)
	{
		$cls = $this->requireTemplate($name);
		return new Twig_Template(new $cls);
	}

	public function getCacheFilename($name)
	{
		return $this->cache . '/twig_' . md5($name) . '.cache';
	}

	public function requireTemplate($name)
	{
		$cls = '__TwigTemplate_' . md5($name);
		if (!class_exists($cls)) {
			$fn = $this->getFilename($name);
			if (!file_exists($fn))
				throw new Twig_TemplateNotFound($name);
			$cache_fn = $this->getCacheFilename($name);
			if (!file_exists($cache_fn) ||
			    filemtime($cache_fn) < filemtime($fn)) {
				twig_load_compiler();
				$fp = fopen($cache_fn, 'wb');
				$compiler = new Twig_FileCompiler($fp);
				$this->compileTemplate($name, $compiler, $fn);
				fclose($fp);
			}
			include $cache_fn;
		}
		return $cls;
	}

	public function compileTemplate($name, $compiler=null, $fn=null)
	{
		twig_load_compiler();
		if (is_null($compiler)) {
			$compiler = new Twig_StringCompiler();
			$returnCode = true;
		}
		else
			$returnCode = false;
		if (is_null($fn))
			$fn = $this->getFilename($name);

		$node = twig_parse(file_get_contents($fn, $name), $name);
		$node->compile($compiler);
		if ($returnCode)
			return $compiler->getCode();
	}
}


/**
 * Helper class that loads templates.
 */
class Twig_Loader extends Twig_BaseLoader
{
	public $folder;

	public function __construct($folder, $cache)
	{
		parent::__construct($cache);
		$this->folder = $folder;
	}

	public function getFilename($name)
	{
		$path = array();
		foreach (explode('/', $name) as $part) {
			if ($part[0] != '.')
				array_push($path, $part);
		}
		return $this->folder . '/' . implode('/', $path);
	}
}
