<?php

/**
 * @license LGPLv3, https://opensource.org/licenses/LGPL-3.0
 * @copyright Aimeos (aimeos.org), 2021
 */


namespace Aimeos\Upscheme\Task;


/**
 * Interface for all setup tasks
 */
interface Iface
{
	/**
	 * Returns the list of task names which depends on this task
	 *
	 * @return array<string> List of task names
	 */
	public function after() : array;

	/**
	 * Returns the list of task names which this task depends on
	 *
	 * @return array<string> List of task names
	 */
	public function before() : array;

	/**
	 * Executes the tasks to update the database
	 */
	public function up();
}