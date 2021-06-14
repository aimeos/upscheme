<?php


namespace Aimeos\Upscheme\Task;

use Aimeos\Upscheme\Schema\Table;


class Dep1 extends Base
{
	public function before() : array
	{
		return ['Dep2'];
	}


	public function up()
	{
		echo 'dep1';
	}
}