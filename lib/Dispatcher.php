<?php
/**
 * @author Rob Apodaca <rob.apodaca@gmail.com>
 * @copyright Copyright (c) 2009, Rob Apodaca
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link http://robap.github.com/php-router/
 */
class Dispatcher
{
	/**
	 * The suffix used to append to the class name
	 * @var string
	 */
	protected $suffix;
	
	/**
	 * The namespace to append to the class name
	 * @var string
	 */
	protected $namespace;
	
	/**
	 * Is class a singletone?
	 * @var string
	 */
	protected $instance;
	
	/**
	 * Upper first word of class name
	 * @var boolean
	 */
	protected $camelise = false;

	/**
	 * The path to look for classes (or controllers)
	 * @var string
	 */
	protected $classPath;

	/**
	 * Class constructor
	 */
	public function __construct()
	{
		$this->setSuffix('');
	}

	/**
	 * Attempts to dispatch the supplied Route object. Returns false if it fails
	 * @param Route $route
	 * @param mixed $context
	 * @throws classFileNotFoundException
	 * @throws badClassNameException
	 * @throws classNameNotFoundException
	 * @throws classMethodNotFoundException
	 * @throws classNotSpecifiedException
	 * @throws methodNotSpecifiedException
	 * @return mixed - result of controller method or FALSE on error
	 */
	public function dispatch( Route $route, $context = null )
	{
		$class	  = trim($route->getMapClass());
		$method	 = trim($route->getMapMethod());
		$arguments  = $route->getMapArguments();

		if( '' === $class )
			throw new classNotSpecifiedException('Class Name not specified');

		if( '' === $method )
			throw new methodNotSpecifiedException('Method Name not specified');

		//Because the class could have been matched as a dynamic element,
		// it would mean that the value in $class is untrusted. Therefore,
		// it may only contain alphanumeric characters. Anything not matching
		// the regexp is considered potentially harmful.
		$class = str_replace('\\', '', $class);
		if(TRUE === $this->camelise)
			$class = ucfirst( $class );
		
		preg_match('/^[a-zA-Z0-9_]+$/', $class, $matches);
		if( count($matches) !== 1 )
			throw new badClassNameException('Disallowed characters in class name ' . $class);

		//Apply the suffix
		$file_name = $this->classPath . $class . $this->suffix;
		$class = $class . str_replace($this->getFileExtension(), '', $this->suffix);
		
		//Check if class have namespace
		if(!empty($this->namespace))
			$class = $this->namespace."\\".$class;
		
		//At this point, we are relatively assured that the file name is safe
		// to check for it's existence and require in.
		if( FALSE === file_exists($file_name) )
			throw new classFileNotFoundException('Class file not found');
		else
			require_once($file_name);

		//Check for the class class
		if( FALSE === class_exists($class) )
			throw new classNameNotFoundException('class not found ' . $class);

		//Check for the method
		if( FALSE === method_exists($class, $method))
			throw new classMethodNotFoundException('method not found ' . $method);

		//All above checks should have confirmed that the class can be instatiated
		// and the method can be called
		return $this->dispatchController($class, $method, $arguments, $context);
	}
	
	/**
	 * Create instance of controller and dispatch to it's method passing
	 * arguments. Override to change behavior.
	 * 
	 * @param string $class
	 * @param string $method
	 * @param array $args
	 * @return mixed - result of controller method
	 */
	protected function dispatchController($class, $method, $args, $context = null)
	{
		// check singletone class
		if(!empty($this->instance))
			$obj = call_user_func($class.'::'.$this->instance, $context);
		else
			$obj = new $class($context);
		return call_user_func(array($obj, $method), $args);
	}

	/**
	 * Sets a suffix to append to the class name being dispatched
	 * @param string $suffix
	 * @return Dispatcher
	 */
	public function setSuffix( $suffix )
	{
		$this->suffix = $suffix . $this->getFileExtension();

		return $this;
	}
	
	/**
	 * set the namespace of controller class
	 * @param string $namespace
	 * @return Dispatcher
	 */
	public function setNamespace( $namespace )
	{
		$this->namespace = $namespace;
	
		return $this;
	}
	
	/**
	 * set the controller have singletone instance
	 * to call class will be ClassName::getInstance() 
	 * @param string $instance
	 * @return Dispatcher
	 */
	public function setInstance( $instance )
	{
		$this->instance = $instance;
	
		return $this;
	}
	
	/**
	 * set the upper first leter of class name
	 * 
	 * @param bool $camel
	 * @return Dispatcher
	 */
	public function setCameCase( $camel )
	{
		$this->camelise = $camel;
	
		return $this;
	}

	/**
	 * Set the path where dispatch class (controllers) reside
	 * @param string $path
	 * @return Dispatcher
	 */
	public function setClassPath( $path )
	{
		$this->classPath = preg_replace('/\/$/', '', $path) . '/';

		return $this;
	}
	
	/**
	 * Return a 
	 * @return string
	 */
	public function getFileExtension()
	{
		return '.php';
	}
}



