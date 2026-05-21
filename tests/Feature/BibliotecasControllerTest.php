<?php

namespace Tests\Feature;

use App\Models\Biblioteca;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BibliotecasControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_exibe_lista_de_bibliotecas(): void
    {
        $user = User::factory()->create();
        Biblioteca::create([
            'created_by' => $user->id,
            'nome' => 'Biblioteca 1',
            'endereco' => 'Rua A',
            'telefone' => '(11) 1111-1111',
            'email' => 'a@example.com',
        ]);

        $response = $this->get('/bibliotecas');

        $response->assertStatus(200);
        $response->assertViewIs('bibliotecas.index');
        $response->assertSee('Biblioteca 1');
        $response->assertViewHas('bibliotecas');
    }

    public function test_index_filtra_bibliotecas_por_nome(): void
    {
        $user = User::factory()->create();
        Biblioteca::create([ 'created_by' => $user->id, 'nome' => 'Biblioteca Central' ]);
        Biblioteca::create([ 'created_by' => $user->id, 'nome' => 'Biblioteca do Bairro' ]);

        $response = $this->get('/bibliotecas?nome=Central');

        $response->assertStatus(200);
        $response->assertSee('Biblioteca Central');
        $response->assertDontSee('Biblioteca do Bairro');
    }

    public function test_create_page_exibe_formulario_e_usuarios(): void
    {
        $user = User::factory()->create();

        $response = $this->get('/bibliotecas/new');

        $response->assertStatus(200);
        $response->assertViewIs('bibliotecas.new');
        $response->assertViewHas('users');
        $response->assertSee($user->name);
    }

    public function test_edit_page_para_biblioteca_existente_retorna_500_atualmente(): void
    {
        $user = User::factory()->create();
        $biblioteca = Biblioteca::create([
            'created_by' => $user->id,
            'nome' => 'Biblioteca Editar',
            'endereco' => 'Rua Editar',
            'telefone' => '(11) 2222-2222',
            'email' => 'editar@example.com',
        ]);

        $response = $this->get("/bibliotecas/edit/{$biblioteca->id}");

        $response->assertStatus(500);
    }

    public function test_edit_page_para_biblioteca_inexistente_retorna_500(): void
    {
        $response = $this->get('/bibliotecas/edit/9999');

        $response->assertStatus(500);
    }

    public function test_store_cria_biblioteca_com_dados_validos(): void
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
            'endereco' => 'Rua Teste, 123',
        ]);
    }

    public function test_store_retorna_erro_quando_falta_nome(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/bibliotecas/create', [
            'created_by' => $user->id,
            'endereco' => 'Rua Teste, 123',
        ]);

        $response->assertStatus(500);
        $this->assertDatabaseCount('bibliotecas', 0);
    }

    public function test_store_retorna_erro_quando_falta_created_by(): void
    {
        $response = $this->post('/bibliotecas/create', [
            'nome' => 'Biblioteca',
            'endereco' => 'Rua Teste, 123',
        ]);

        $response->assertStatus(500);
        $this->assertDatabaseCount('bibliotecas', 0);
    }

    public function test_update_altera_campos_validados(): void
    {
        $user = User::factory()->create();
        $biblioteca = Biblioteca::create([
            'created_by' => $user->id,
            'nome' => 'Biblioteca',
            'endereco' => 'Rua Original',
            'email' => 'old@email.com',
        ]);

        $response = $this->put("/bibliotecas/update/{$biblioteca->id}", [
            'email' => 'novo@email.com',
        ]);

        $response->assertRedirect(route('bibliotecas.index'));
        $biblioteca->refresh();
        $this->assertEquals('novo@email.com', $biblioteca->email);
        $this->assertEquals('Rua Original', $biblioteca->endereco);
    }

    public function test_update_retorna_404_para_biblioteca_inexistente(): void
    {
        $response = $this->put('/bibliotecas/update/9999', [
            'nome' => 'Biblioteca',
        ]);

        $response->assertStatus(404);
    }

    public function test_destroy_remove_biblioteca_existente(): void
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

    public function test_destroy_retorna_404_para_biblioteca_inexistente(): void
    {
        $response = $this->delete('/bibliotecas/delete/9999');

        $response->assertStatus(404);
    }
}
