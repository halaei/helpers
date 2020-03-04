<?php

namespace HalaeiTests;

use Halaei\Helpers\Eloquent\EloquentServiceProvider;
use PHPUnit\Framework\TestCase;

class BatchUpdateTest extends TestCase
{
    /**
     * The Illuminate application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    public function setUp(): void
    {
        if (! $this->app) {
            $this->createApplication();
        }
    }

    /**
     * Creates the application.
     *
     * Needs to be implemented by subclasses.
     *
     * @return \Symfony\Component\HttpKernel\HttpKernelInterface
     */
    public function createApplication()
    {
        $this->app = $this->getMockConsole(['addToParent', 'version']);
        $command = \Mockery::mock(\Illuminate\Console\Command::class);
        $command->shouldReceive('setLaravel')->once()->with(\Mockery::type(\Illuminate\Contracts\Foundation\Application::class));
        $this->app->expects($this->once())->method('addToParent')->with($this->equalTo($command))->will($this->returnValue($command));
        $result = $this->app->add($command);

        $this->assertEquals($command, $result);
    }

    protected function getMockConsole(array $methods)
    {
        $app = \Mockery::mock(\Illuminate\Contracts\Foundation\Application::class, ['version' => '5.4.0']);
        $events = \Mockery::mock(\Illuminate\Contracts\Events\Dispatcher::class, ['fire' => null]);
        $events->shouldReceive('dispatch');

        $console = $this->getMockBuilder(\Illuminate\Console\Application::class)->setMethods($methods)->setConstructorArgs([
            $app, $events, 'test-version',
        ])->getMock();

        return $console;
    }

    public function testMacrosCanBeRegistered()
    {
        $this->app->expects($this->once())->method('version')->will($this->returnValue('5.4.0'));
        (new EloquentServiceProvider($this->app))->register();
    }
}
