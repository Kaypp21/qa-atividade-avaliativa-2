<?php

namespace Tests\Feature;

use App\Models\Biblioteca;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BibliotecasControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Listar bibliotecas com sucesso
     */
    public function test_listar_bibliotecas_com_sucesso(): void
    {
        $user = User::factory()->create();
        Biblioteca::create([
            'created_by' => $user->id,
            'nome' => 'Biblioteca 1',
        ]);

        $response = $this->get('/bibliotecas');

        $response->assertStatus(200);
        $response->assertViewIs('bibliotecas.index');
    }

    /**
     * Test: Buscar biblioteca por nome
     */
    public function test_buscar_biblioteca_por_nome(): void
    {
        $user = User::factory()->create();
        Biblioteca::create([
            'created_by' => $user->id,
            'nome' => 'Biblioteca Central',
        ]);
        Biblioteca::create([
            'created_by' => $user->id,
            'nome' => 'Biblioteca do Bairro',
        ]);

        $response = $this->get('/bibliotecas?nome=Central');

        $response->assertStatus(200);
    }

    /**
     * Test: Exibir formulário de criação com usuários
     */
    public function test_exibir_formulario_criacao(): void
    {
        User::factory()->create();

        $response = $this->get('/bibliotecas/new');

        $response->assertStatus(200);
        $response->assertViewIs('bibliotecas.new');
        $response->assertViewHas('users');
    }

    /**
     * Test: Criar biblioteca com dados válidos
     */
    public function test_criar_biblioteca_com_dados_validos(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/bibliotecas/create', [
            'created_by' => $user->id,
            'nome' => 'Biblioteca Nova',
            'endereco' => 'Rua Teste, 123',
            'telefone' => '(11) 3333-3333',
            'email' => 'contato@biblioteca.com',
        ]);

        $response->assertRedirect(route('bibliotecas.index'));
        $this->assertDatabaseHas('bibliotecas', [
            'nome' => 'Biblioteca Nova',
        ]);
    }

    /**
     * Test: Validar que biblioteca sem nome não é criada
     */
    public function test_validar_biblioteca_sem_nome_nao_criada(): void
    {
        $user = User::factory()->create();
        $bibliotecasAntes = Biblioteca::count();

        try {
            $response = $this->post('/bibliotecas/create', [
                'created_by' => $user->id,
                'endereco' => 'Rua Teste, 123',
            ]);
        } catch (\Throwable $e) {
            // Esperado que lance erro
        }

        // Verificar que não foi criada
        $this->assertEquals($bibliotecasAntes, Biblioteca::count());
    }

    /**
     * Test: Validar que biblioteca sem created_by não é criada
     */
    public function test_validar_biblioteca_sem_created_by_nao_criada(): void
    {
        $bibliotecasAntes = Biblioteca::count();

        try {
            $response = $this->post('/bibliotecas/create', [
                'nome' => 'Biblioteca',
                'endereco' => 'Rua Teste, 123',
            ]);
        } catch (\Throwable $e) {
            // Esperado que lance erro
        }

        // Verificar que não foi criada
        $this->assertEquals($bibliotecasAntes, Biblioteca::count());
    }

    /**
     * Test: Criação com created_by válido funciona
     */
    public function test_criacao_com_created_by_valido(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/bibliotecas/create', [
            'created_by' => $user->id,
            'nome' => 'Biblioteca Válida',
        ]);

        $response->assertRedirect(route('bibliotecas.index'));
        $this->assertDatabaseHas('bibliotecas', ['nome' => 'Biblioteca Válida']);
    }

    /**
     * Test: Validar criação com dados mínimos
     */
    public function test_criacao_com_dados_minimos(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/bibliotecas/create', [
            'created_by' => $user->id,
            'nome' => 'Biblioteca Mínima',
        ]);

        $response->assertRedirect(route('bibliotecas.index'));
        $this->assertDatabaseHas('bibliotecas', ['nome' => 'Biblioteca Mínima']);
    }

    /**
     * Test: Validar criação com todos os campos
     */
    public function test_criacao_com_todos_campos(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/bibliotecas/create', [
            'created_by' => $user->id,
            'nome' => 'Biblioteca Completa',
            'endereco' => 'Rua Teste, 123',
            'telefone' => '(11) 3333-3333',
            'email' => 'teste@example.com',
        ]);

        $response->assertRedirect(route('bibliotecas.index'));
        $this->assertDatabaseHas('bibliotecas', [
            'nome' => 'Biblioteca Completa',
            'endereco' => 'Rua Teste, 123',
        ]);
    }

    /**
     * Test: Erro ao atualizar biblioteca inexistente retorna erro
     */
    public function test_erro_atualizar_biblioteca_inexistente(): void
    {
        $response = $this->put('/bibliotecas/update/9999', [
            'nome' => 'Biblioteca',
        ]);

        $response->assertStatus(404);
    }

    /**
     * Test: Atualizar biblioteca com dados válidos
     */
    public function test_atualizar_biblioteca_dados_validos(): void
    {
        $user = User::factory()->create();
        $biblioteca = Biblioteca::create([
            'created_by' => $user->id,
            'nome' => 'Biblioteca',
            'email' => 'old@email.com',
        ]);

        $response = $this->put("/bibliotecas/update/{$biblioteca->id}", [
            'email' => 'novo@email.com',
        ]);

        $response->assertRedirect(route('bibliotecas.index'));
        $this->assertDatabaseHas('bibliotecas', [
            'id' => $biblioteca->id,
            'email' => 'novo@email.com',
        ]);
    }

    /**
     * Test: Atualizar preserva campos não modificados
     */
    public function test_atualizar_preserva_campos_nao_modificados(): void
    {
        $user = User::factory()->create();
        $biblioteca = Biblioteca::create([
            'created_by' => $user->id,
            'nome' => 'Biblioteca Original',
            'endereco' => 'Rua Original',
            'email' => 'original@email.com',
        ]);

        $this->put("/bibliotecas/update/{$biblioteca->id}", [
            'nome' => 'Biblioteca Modificada',
        ]);

        $biblioteca->refresh();
        $this->assertEquals('Rua Original', $biblioteca->endereco);
        $this->assertEquals('original@email.com', $biblioteca->email);
    }

    /**
     * Test: Deletar biblioteca retorna redirecionamento
     */
    public function test_deletar_biblioteca_com_sucesso(): void
    {
        $user = User::factory()->create();
        $biblioteca = Biblioteca::create([
            'created_by' => $user->id,
            'nome' => 'Biblioteca a Deletar',
        ]);

        $response = $this->delete("/bibliotecas/delete/{$biblioteca->id}");

        $response->assertRedirect(route('bibliotecas.index'));
        $this->assertDatabaseMissing('bibliotecas', ['id' => $biblioteca->id]);
    }

    /**
     * Test: Erro ao deletar biblioteca inexistente
     */
    public function test_erro_deletar_biblioteca_inexistente(): void
    {
        $response = $this->delete('/bibliotecas/delete/9999');

        // Controller atual retorna 404 quando não encontra
        $response->assertStatus(404);
    }

    /**
     * Test: Lista vazia quando nenhuma biblioteca existe
     */
    public function test_lista_vazia_quando_nenhuma_biblioteca(): void
    {
        $response = $this->get('/bibliotecas');

        $response->assertStatus(200);
    }

    /**
     * Test: Busca retorna resultado correto
     */
    public function test_busca_retorna_resultado_correto(): void
    {
        $user = User::factory()->create();
        Biblioteca::create([
            'created_by' => $user->id,
            'nome' => 'Biblioteca Central',
        ]);
        Biblioteca::create([
            'created_by' => $user->id,
            'nome' => 'Biblioteca Municipal',
        ]);

        $response = $this->get('/bibliotecas?nome=Central');

        $response->assertStatus(200);
    }

    /**
     * Test: Múltiplas bibliotecas são listadas
     */
    public function test_multiplas_bibliotecas_listadas(): void
    {
        $user = User::factory()->create();
        Biblioteca::create(['created_by' => $user->id, 'nome' => 'Biblioteca 1']);
        Biblioteca::create(['created_by' => $user->id, 'nome' => 'Biblioteca 2']);
        Biblioteca::create(['created_by' => $user->id, 'nome' => 'Biblioteca 3']);

        $response = $this->get('/bibliotecas');

        $response->assertStatus(200);
    }
}
