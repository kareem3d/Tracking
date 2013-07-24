<?php namespace Tracking;

use Closure, Exception;

class Tracker {

	const SAVE = 0;
	const DONT_SAVE = 1;

	const SESSION_KEY = 'Zas23asd';

	/**
	 * Singleton instance.
	 *
	 * @var Tracker
	 */
	protected static $instance;

	/**
	 * Max history save.
	 *
	 * @var int
	 */
	protected $maxSave;

	/**
	 * Order whether to save or not to save.
	 *
	 * @var int
	 */
	protected $order;

	/**
	 * Request types to track
	 *
	 * @var array
	 */
	protected $requestTypes = array('GET');

	/**
	 * Current identifier generating mechanism
	 *
	 * @var Closure
	 */
	protected $mechanism;

    /**
     * Constructor.
     *
     * @return Tracker
     */
	private function __construct()
	{
		// Default max save number.
		$this->maxSave = 7;

		// Dont save if no order was given
		$this->order = self::DONT_SAVE;

		// Default mechanism for identify requests
		$this->mechanism = function()
		{
			return $_SERVER['REQUEST_URI'];
		};
	}

    /**
     * Get singleton instance
     *
     * @return Tracker
     */
	public static function instance()
	{
		if(! static::$instance) static::$instance = new static();

		return static::$instance;
	}

	/**
	 * Set the order of the tracker to save.
	 *
	 * @return void
	 */
	public function save()
	{
		$this->order = self::SAVE;
	}

	/**
	 * Set the order of the tracker to not save.
	 *
	 * @return void
	 */
	public function dontSave()
	{
		$this->order = self::DONT_SAVE;
	}

	/**
	 * Calling this method will tell the tracker to do what 
	 * it has been ordered.
	 *
	 * @return void
	 */
	public function done()
	{
		if(in_array($_SERVER['REQUEST_METHOD'], $this->requestTypes) && $this->order == self::SAVE)
		{
			$this->add(call_user_func_array($this->mechanism, array()));
		}
	}

    /**
     * Set mechanism for generating strings to session.
     *
     * @param  callable $mechanism
     * @return void
     */
	public function setMechanism( Closure $mechanism )
	{
		$this->mechanism = $mechanism;
	}

	/**
	 * Add the given string to the array saved in the session
	 *
	 * @param  string $string
	 * @return void
	 */
	protected function add( $string )
	{
		if(! $string) return;

		$all = $this->getAll();

		// If it was equal to the last request so stop
		if($this->getByOrder(1) == $string) return;

		// Add this string at the beginning of the array
		array_unshift($all, $string);

		// If the array is larger than maximum save then remove last one
		if(count($all) > $this->maxSave) array_pop($all);

		$this->putToSession( $all );
	}

	/**
	 * Force to add the given string to the array saved in the session
	 *
	 * @param  string $string
	 * @return void
	 */
	public function forceAdd( $string )
	{
        $this->add( $string );
	}

	/**
	 * Get all what have been tracked.
	 *
	 * @return array
	 */
	public function getAll()
	{
		return $this->getFromSession();
	}

	/**
	 * Get by the given order.
	 * Order 1 means the previous request, Order = maxSize means the oldest request.
	 *
	 * @param  int $order
	 * @return string
	 */
	public function getByOrder( $order )
	{
		$all = $this->getAll();

		return isset($all[$order - 1]) ? $all[$order - 1] : '';
	}

	/**
	 * Get the one before the given string.
	 *
	 * @param  string $string
	 * @param  array  $except
	 * @return string
	 */
	public function getBefore( $string, array $except = array() )
	{
		return $this->getByOrder($this->getKey($string, $except) + 2);
	}

	/**
	 * Get the one after the given string.
	 *
	 * @param  string $string
	 * @param  array  $except
	 * @return string
	 */
	public function getAfter( $string, array $except = array() )
	{
		return $this->getByOrder($this->getKey($string, $except));
	}

	/**
	 * Clear all tracked pages.
	 *
	 * @return void
	 */
	public function clear()
	{
		$this->putToSession(array());
	}

    /**
     * Get key for the given string.
     *
     * @param  string $string
     * @param  array  $except
     * @return int
     */
	protected function getKey( $string, array $except = array() )
	{
		$newAll = array_diff($this->getAll(), $except);

		return array_search($string, $newAll);
	}

	/**
	 * Put the given argument to the session.
	 *
	 * @throws Exception
	 * @param  mixed $all
	 * @return void
	 */
	protected function putToSession( array $all )
	{
		if(! session_id()) throw new Exception('Session has\'nt been started yet.');

		$_SESSION[self::SESSION_KEY] = $all;
	}

	/**
	 * Get mixed data from session.
	 *
	 * @throws Exception
	 * @return array
	 */
	protected function getFromSession()
	{
		if(! session_id()) throw new Exception('Session has\'nt been started yet.');

		return isset($_SESSION[self::SESSION_KEY]) ? $_SESSION[self::SESSION_KEY] : array();
	}
}