<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        // Executa o ciclo de vida padrão do Laravel Setup
        parent::setUp();

        // Define a chave de criptografia dinamicamente na memória para os testes
        config(['app.key' => 'base64:uK3v6ga7Zp8qNmW6Yv7C9XzRwT2bN3mQ4vP5rS6tU8o=']);
    }
}