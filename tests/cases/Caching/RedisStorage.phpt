<?php declare(strict_types = 1);

namespace Tests\Cases\Caching;

use Contributte\Redis\Caching\RedisStorage;
use Mockery;
use Ninjify\Nunjuck\Toolkit;
use Predis\Client;
use Predis\Command\Command;
use Predis\Connection\ConnectionInterface;
use Tester\Assert;

require_once __DIR__ . '/../../bootstrap.php';

Toolkit::test(function (): void {
	$storage = (object) ['unserialized' => 'unserialized'];

	$conn = Mockery::mock(ConnectionInterface::class)
		->shouldReceive('executeCommand')
		->andReturnUsing(function (Command $command) use ($storage) {
			switch ($command->getId()) {
				case 'SET':
					$storage->{$command->getArguments()[0]} = $command->getArguments()[1];
					return null;
				case 'DEL':
					$storage->{$command->getArguments()[0]} = null;
					return null;
				default:
					return $storage->{$command->getArguments()[0]} ?? null;
			}
		})->getMock();

	$redis = new RedisStorage(new Client($conn));
	$redis->write('foo', 'bar', []);
	Assert::same('bar', $redis->read('foo'));
	$redis->write('foo', 'bat', []);
	Assert::same('bat', $redis->read('foo'));
	$redis->remove('foo');
	Assert::null($redis->read('foo'));
	Assert::null($redis->read('unserialized'));

	$redis->write('false', false, []);
	Assert::false($redis->read('false'));
});
