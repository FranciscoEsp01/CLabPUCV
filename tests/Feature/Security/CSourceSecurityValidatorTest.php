<?php

namespace Tests\Feature\Security;

use App\Services\Security\CSourceSecurityValidator;
use Tests\TestCase;

class CSourceSecurityValidatorTest extends TestCase
{
    protected CSourceSecurityValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new CSourceSecurityValidator();
    }

    public function test_allows_legitimate_c_program(): void
    {
        $code = <<<'C'
#include <stdio.h>
#include <stdlib.h>
#include <math.h>

int main() {
    int a = 5;
    int b = 10;
    printf("Resultado suma: %d\n", a + b);
    return 0;
}
C;
        $result = $this->validator->validate($code);
        $this->assertTrue($result['safe'], 'Valid C code should pass validation: ' . ($result['reason'] ?? ''));
    }

    public function test_blocks_prohibited_system_headers(): void
    {
        $dangerousHeaders = [
            '<sys/socket.h>',
            '<netinet/in.h>',
            '<arpa/inet.h>',
            '<netdb.h>',
            '<unistd.h>',
            '<sys/types.h>',
            '<sys/stat.h>',
            '<fcntl.h>',
            '<sys/mman.h>',
            '<sys/ptrace.h>',
            '<windows.h>',
            '<winsock2.h>',
            '<dlfcn.h>',
        ];

        foreach ($dangerousHeaders as $header) {
            $code = "#include $header\nint main() { return 0; }";
            $result = $this->validator->validate($code);
            $this->assertFalse($result['safe'], "Header $header should have been blocked");
        }
    }

    public function test_blocks_dangerous_execution_and_file_functions(): void
    {
        $dangerousFunctions = [
            'system("ls -la");',
            'popen("cat /etc/passwd", "r");',
            'execve("/bin/sh", NULL, NULL);',
            'fork();',
            'kill(1, 9);',
            'ptrace(0, 0, 0, 0);',
            'fopen("/etc/shadow", "r");',
            'remove("database.sqlite");',
            'unlink(".env");',
            'rename("a", "b");',
            'mkdir("evil", 0777);',
            'socket(AF_INET, SOCK_STREAM, 0);',
        ];

        foreach ($dangerousFunctions as $funcCall) {
            $code = "#include <stdio.h>\nint main() { $funcCall return 0; }";
            $result = $this->validator->validate($code);
            $this->assertFalse($result['safe'], "Call '$funcCall' should have been blocked: " . json_encode($result));
        }
    }

    public function test_blocks_inline_assembly(): void
    {
        $code = <<<'C'
#include <stdio.h>
int main() {
    __asm__("mov $60, %rax\nsyscall");
    return 0;
}
C;
        $result = $this->validator->validate($code);
        $this->assertFalse($result['safe'], 'Inline assembly (__asm__) should be blocked');
    }

    public function test_blocks_malicious_macro_token_pasting(): void
    {
        $code = <<<'C'
#include <stdio.h>
#define CONCAT(a, b) a##b
int main() {
    CONCAT(sys, tem)("whoami");
    return 0;
}
C;
        $result = $this->validator->validate($code);
        $this->assertFalse($result['safe'], 'Token pasting macro tricks should be blocked');
    }

    public function test_blocks_obfuscated_file_access_paths(): void
    {
        $code = <<<'C'
#include <stdio.h>
int main() {
    char p[] = "/etc/passwd";
    printf("%s\n", p);
    return 0;
}
C;
        $result = $this->validator->validate($code);
        $this->assertFalse($result['safe'], 'Sensitive system paths should be blocked');
    }
}
