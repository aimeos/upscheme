<?php


namespace Aimeos\Upscheme\Task;

use Aimeos\Upscheme\Schema\Table;


class Circular2 extends Base
{
	public function after() : array
	{
		return ['Circular1'];
	}


	public function up()
	{
	}
}