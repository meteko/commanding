<?php

namespace Meteko\Commanding;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Reflection\ReflectionService;
use Meteko\Commanding\Exception\Exception;
use Meteko\Commanding\Exception\CommandHandlerNotFoundException;

/**
 * @Flow\Scope("singleton")
 */
class CommandHandlerResolver implements CommandHandlerResolverInterface {

	/**
	 * @var ObjectManagerInterface
	 * @Flow\Inject
	 */
	protected $objectManager;

	/**
	 * @var []
	 */
	protected $map;

	public function initializeObject() {
		$this->map = self::detectHandlers($this->objectManager);
	}


	/**
	 * @param string $messageName
	 * @return \Closure
	 * @throws CommandHandlerNotFoundException
	 */
	public function resolve($messageName): \Closure
	{
		if (!isset($this->map[$messageName])) {
			throw new CommandHandlerNotFoundException(sprintf('Missing handler command %s', $messageName), 1472576941);
		}
		list($class, $method) = $this->map[$messageName];

		return function (CommandInterface $command) use ($class, $method) {
			$handler = $this->objectManager->get($class);
			$handler->$method($command);
		};
	}


	/**
	 * @param ObjectManagerInterface $objectManager
	 * @return array
	 * @throws Exception
	 * @Flow\CompileStatic
	 */
	public static function detectHandlers(ObjectManagerInterface $objectManager) {
		$handlers = [];
		/** @var ReflectionService $reflectionService */
		$reflectionService = $objectManager->get(ReflectionService::class);

		foreach ($reflectionService->getAllImplementationClassNamesForInterface(CommandHandlerInterface::class) as $handler) {
			foreach (get_class_methods($handler) as $method) {
				preg_match('/^handle.*$/', $method, $matches);
				if (!isset($matches[0])) {
					continue;
				}
				$method = $matches[0];
				$parameters = array_values($reflectionService->getMethodParameters($handler, $method));
				$commandParameter = reset($parameters);
				$commandType = $commandParameter['class'];
				if (trim($commandType) === '') {
					throw new Exception(sprintf('Invalid handler in %s::%s the method signature is wrong, must accept an CommandInterface', $handler, $method), 1472576554);
				}
				$commandTypeParts = explode('\\', $commandType);
				$expectedMethod = 'handle' . end($commandTypeParts);
				if ($expectedMethod !== $method) {
					throw new Exception(sprintf('Invalid handler in %s::%s the method name is wrong, must be "%s"', $handler, $method, $expectedMethod), 1472576636);
				}
				if (isset($listeners[$commandType])) {
					throw new Exception(sprintf('Invalid handler in %s::%s multiple handler for the same command is not allowed.', $handler, $method), 1472576722);
				}
				$handlers[$commandType] = [$handler, $method];
			}

		}

		return $handlers;
	}
}