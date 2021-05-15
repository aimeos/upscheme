<?php


namespace Aimeos\Upscheme\Task;

use Aimeos\Upscheme\Schema\Table;


class Dep2 extends Base
{
	public function before() : array
	{
		return ['Dep1'];
	}


	public function up()
	{
		echo 'dep2';
	}
}