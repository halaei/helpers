<?php

namespace HalaeiTests;

use Halaei\Helpers\Process\Process;
use PHPUnit\Framework\TestCase;

class ProcessTest extends TestCase
{
    private static $randPath;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::$randPath = __DIR__.'/random.bin';
        file_put_contents(self::$randPath, random_bytes(2000000));
    }

    public static function tearDownAfterClass(): void
    {
        unlink(self::$randPath);
        parent::tearDownAfterClass();
    }

    public function test_pass_large_input_to_process()
    {
        $input = random_bytes(2000000);
        $p = new Process(['php', '-r', 'echo(1);'], null, null, $input, 2);
        $this->assertEquals("1", $p->mustRun()->stdOut);
    }

    public function test_pass_large_input_file_to_process()
    {
        $input = fopen(self::$randPath, 'rb');
        $p = new Process(['php', '-r', 'echo(1);'], null, null, $input, 2);
        $this->assertEquals("1", $p->mustRun()->stdOut);
        fclose($input);
    }

    public function test_get_large_output_from_process()
    {
        $p = new Process(['php', '-r', 'echo(random_bytes(2000000));'], null, null, 2);
        $this->assertEquals(2000000, strlen($p->mustRun()->stdOut));
    }

    public function test_get_large_error_from_process()
    {
        $p = new Process(['php', '-r', 'fwrite(STDERR, random_bytes(2000000));'], null, null, 2);
        $this->assertEquals(2000000, strlen($p->run()->stdErr));
    }

    public function test_process_with_all_large_io()
    {
        $input = random_bytes(2000000);
        $p = new Process(['php', '-r', 'fwrite(STDERR, $r = random_bytes(2000000)); echo($r);'], null, null, $input, 2);
        $result = $p->mustRun();
        $this->assertEquals(2000000, strlen($result->stdErr));
        $this->assertEquals($result->stdErr, $result->stdOut);
    }

    public function test_process_with_all_large_files()
    {
        $input = fopen(self::$randPath, 'rb');
        $p = new Process(['php', '-r', 'fwrite(STDERR, $r = random_bytes(2000000)); echo($r);'], null, null, $input, 2);
        $result = $p->mustRun();
        $this->assertEquals(2000000, strlen($result->stdErr));
        $this->assertEquals($result->stdErr, $result->stdOut);
    }

    public function test_read_large_input()
    {
        $input = random_bytes(2000000);
        $p = new Process(['php', '-r', 'fwrite(STDERR, $r = stream_get_contents(STDIN)); echo($r);'], null, null, $input, 2);
        $result = $p->mustRun();
        $this->assertEquals($input, $result->stdOut);
        $this->assertEquals($input, $result->stdErr);
    }

    public function test_read_large_input_file()
    {
        $input = fopen(self::$randPath, 'rb');
        $p = new Process(['php', '-r', 'fwrite(STDERR, $r = stream_get_contents(STDIN)); echo($r);'], null, null, $input, 2);
        $result = $p->mustRun();
        fclose($input);
        $content = file_get_contents(self::$randPath);
        $this->assertEquals($content, $result->stdOut);
        $this->assertEquals($content, $result->stdErr);
    }

    public function test_read_large_input_chunk_by_chunk()
    {
        $input = random_bytes(2000000);
        $p = new Process(['php', '-r', 'while($r = fread(STDIN, 999)) {fwrite(STDOUT, $r); fwrite(STDERR, $r);}'], null, null, $input, 2);
        $result = $p->mustRun();
        $this->assertEquals($input, $result->stdOut);
        $this->assertEquals($input, $result->stdErr);
    }

    public function test_read_large_input_file_chunk_by_chunk()
    {
        $input = fopen(self::$randPath, 'rb');
        $p = new Process(['php', '-r', 'while($r = fread(STDIN, 999)) {fwrite(STDOUT, $r); fwrite(STDERR, $r);}'], null, null, $input, 2);
        $result = $p->mustRun();
        $content = file_get_contents(self::$randPath);
        $this->assertEquals($content, $result->stdOut);
        $this->assertEquals($content, $result->stdErr);
    }

    public function test_timeout()
    {
        $t = microtime(true);
        $p = new Process(['sleep', '30'], null, null, null, 3);
        $this->assertTrue($p->run()->timedOut);
        $this->assertLessThan(5, microtime(true) - $t);
    }

    public function test_reading_zero_after_process_ends()
    {
        $p = new class(['echo', '-n', '0']) extends Process {
            protected function start()
            {
                $started = parent::start();
                sleep(1);
                return $started;
            }
        };
        $this->assertSame('0', $p->run()->stdOut);
    }
}
