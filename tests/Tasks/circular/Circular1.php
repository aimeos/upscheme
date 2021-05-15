<?php


namespace Aimeos\Upscheme\Task;

use Aimeos\Upscheme\Schema\Table;


class Circular1 extends Base
{
	public function after() : array
	{
		return ['Circular2'];
	}


	public function up()
	{
	}
}