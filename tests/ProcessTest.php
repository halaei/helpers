<?php

namespace HalaeiTests;

use Halaei\Helpers\Process\Process;
use PHPUnit\Framework\TestCase;

class ProcessTest extends TestCase
{
    public function test_pass_large_input_to_process()
    {
        $input = random_bytes(2000000);
        $p = new Process(['php', '-r', 'echo(1);'], null, null, $input, 2);
        $this->assertEquals("1", $p->mustRun()->stdOut);
    }

    public function test_get_large_output_from_process()
    {
        $p = new Process(['php', '-r', 'echo(random_bytes(2000000));'], null, null, null, 2);
        $this->assertEquals(2000000, strlen($p->mustRun()->stdOut));
    }

    public function test_get_large_error_from_process()
    {
        $p = new Process(['php', '-r', 'fwrite(STDERR, random_bytes(2000000));'], null, null, null, 2);
        $this->assertEquals(2000000, strlen($p->run()->stdErr));
    }

    public function test_process_with_all_large_files()
    {
        $input = random_bytes(2000000);
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

    public function test_read_large_input_chunk_by_chunk()
    {
        $input = random_bytes(2000000);
        $p = new Process(['php', '-r', 'while($r = fread(STDIN, 999)) {fwrite(STDOUT, $r); fwrite(STDERR, $r);}'], null, null, $input, 2);
        $result = $p->mustRun();
        $this->assertEquals($input, $result->stdOut);
        $this->assertEquals($input, $result->stdErr);
    }

    public function test_timeout()
    {
        $t = microtime(true);
        $p = new Process(['sleep', '30'], null, null, null, 3);
        $this->assertTrue($p->run()->timedOut);
        $this->assertLessThan(5, microtime(true) - $t);
    }

    public function test_run_all()
    {
        $input = random_bytes(2000000);
        $proccesses = [
            // Large stdin
            0 => new Process(['php', '-r', 'sleep(1); echo(1);'], null, null, $input, 3),
            // Large stdout
            1 => new Process(['php', '-r', 'echo(random_bytes(2000000));'], null, null, null, 2),
            // Large stderr
            2 => new Process(['php', '-r', 'fwrite(STDERR, random_bytes(2000000));'], null, null, null, 2),
            // All large files
            3 => new Process(['php', '-r', 'fwrite(STDERR, $r = random_bytes(2000000)); echo($r);'], null, null, $input, 2),
            // Read large stdin
            4 => new Process(['php', '-r', 'fwrite(STDERR, $r = stream_get_contents(STDIN)); echo($r);'], null, null, $input, 2),
            // Read large stdin in chunks
            5 => new Process(['php', '-r', 'while($r = fread(STDIN, 999)) {fwrite(STDOUT, $r); fwrite(STDERR, $r);}'], null, null, $input, 2),
            // Timeout
            6 => new Process(['sleep', '30'], null, null, null, 3),
            // Timeout
            7 => new Process(['sleep', '30'], null, null, null, 3),
            // Timeout
            8 => new Process(['sleep', '30'], null, null, null, 3),
            // Timeout
            9 => new Process(['sleep', '30'], null, null, null, 3),
        ];
        $t = microtime(true);
        $results = Process::runAll($proccesses);
        $this->assertLessThan(5, microtime(true) - $t);
        $this->assertEquals("1", $results[0]->stdOut);
        $this->assertFalse($results[0]->timedOut);
        $this->assertSame(0, $results[0]->exitCode);
        $this->assertEquals(2000000, strlen($results[1]->stdOut));
        $this->assertEquals(2000000, strlen($results[2]->stdErr));
        $this->assertEquals(2000000, strlen($results[3]->stdErr));
        $this->assertEquals($results[3]->stdErr, $results[3]->stdOut);
        $this->assertEquals($input, $results[4]->stdOut);
        $this->assertEquals($input, $results[4]->stdErr);
        $this->assertEquals($input, $results[5]->stdOut);
        $this->assertEquals($input, $results[5]->stdErr);
        $this->assertTrue($results[6]->timedOut);
        $this->assertSame(-1, $results[6]->exitCode);
        $this->assertTrue($results[7]->timedOut);
        $this->assertSame(-1, $results[7]->exitCode);
        $this->assertTrue($results[8]->timedOut);
        $this->assertSame(-1, $results[8]->exitCode);
        $this->assertTrue($results[9]->timedOut);
        $this->assertSame(-1, $results[9]->exitCode);
    }
}
