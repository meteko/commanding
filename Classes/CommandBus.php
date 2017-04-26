<?php

namespace Meteko\Commanding;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;

/**
 * @Flow\Scope("singleton")
 */
class CommandBus implements CommandBusInterface {
	/**
	 * @var ObjectManagerInterface
	 * @Flow\Inject
	 */
	protected $objectManager;

	/**
	 * @var []
	 */
	protected $middlewareChain;

	/**
	 * @var CommandHandlerResolver
	 * @Flow\Inject
	 */
	protected $resolver;

	/**
	 * @var array
	 */
	protected $queue = [];
	/**
	 * @var boolean
	 */
	protected $isHandling = false;

	/**
	 * @param CommandInterface $command
	 * @return void
	 */
	public function handle(CommandInterface $command)
	{
		$this->queue[] = $command;
		if ($this->isHandling) {
			return;
		}
		$this->isHandling = true;
		try {
			while ($command = array_shift($this->queue)) {
				$handler = $this->getHandler($command);
				$handler($command);
			}
		} finally {
			$this->isHandling = false;
		}
	}
	/**
	 * @param CommandInterface $command
	 * @return \Closure
	 */
	protected function getHandler(CommandInterface $command)
	{
		$command = get_class($command);
		return $this->resolver->resolve($command);
	}

}