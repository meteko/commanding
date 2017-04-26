<?php

namespace Meteko\Commanding;

interface CommandBusInterface {

	/**
	 * @param CommandInterface $command
	 * @return void
	 */
	public function handle(CommandInterface $command);
}