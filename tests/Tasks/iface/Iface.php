<?php


namespace Aimeos\Upscheme\Task;

use Aimeos\Upscheme\Schema\Table;


class Iface
{
	public function before() : array
	{
		return [];
	}


	public function after() : array
	{
		return [];
	}


	public function up()
	{
	}
}