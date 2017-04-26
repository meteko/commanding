<?php

namespace Meteko\Commanding;

use Meteko\Commanding\Exception\CommandHandlerNotFoundException;

interface CommandHandlerResolverInterface {

	/**
	 * @param string $messageName
	 * @return \Closure
	 * @throws CommandHandlerNotFoundException
	 */
	public function resolve($messageName): \Closure;

}