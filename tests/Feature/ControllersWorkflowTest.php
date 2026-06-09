<?php

namespace Tests\Feature;

use App\Models\Biblioteca;
use App\Models\Pessoa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ControllersWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_bibliotecas_can_be_listed_and_created(): void
    {
        $user = User::factory()->create();

        $response = $this->get('/bibliotecas');

        $response->assertOk()
            ->assertViewHas('bibliotecas');

        $response = $this->withSession(['_token' => 'test-token'])->post('/bibliotecas/create', [
            '_token' => 'test-token',
            'created_by' => $user->id,
            'nome' => 'Biblioteca Central',
            'endereco' => 'Rua das Flores, 100',
        ]);

        $response->assertRedirect(route('bibliotecas.index'))
            ->assertSessionHas('message', 'Biblioteca criada com sucesso');

        $this->assertDatabaseHas('bibliotecas', [
            'created_by' => $user->id,
            'nome' => 'Biblioteca Central',
            'endereco' => 'Rua das Flores, 100',
        ]);
    }

    public function test_bibliotecas_can_be_updated_and_deleted(): void
    {
        $user = User::factory()->create();
        $biblioteca = Biblioteca::create([
            'created_by' => $user->id,
            'nome' => 'Biblioteca Antiga',
            'endereco' => 'Rua Antiga',
        ]);

        $response = $this->withSession(['_token' => 'test-token'])->put(route('bibliotecas.update', $biblioteca), [
            '_token' => 'test-token',
            'nome' => 'Biblioteca Atualizada',
            'endereco' => 'Rua Nova',
        ]);

        $response->assertRedirect(route('bibliotecas.index'))
            ->assertSessionHas('message', 'Biblioteca atualizada com sucesso');

        $this->assertDatabaseHas('bibliotecas', [
            'id' => $biblioteca->id,
            'nome' => 'Biblioteca Atualizada',
            'endereco' => 'Rua Nova',
        ]);

        $deleteResponse = $this->withSession(['_token' => 'test-token'])->delete(route('bibliotecas.destroy', $biblioteca), [
            '_token' => 'test-token',
        ]);

        $deleteResponse->assertRedirect(route('bibliotecas.index'))
            ->assertSessionHas('message', 'Biblioteca excluída com sucesso');

        $this->assertDatabaseMissing('bibliotecas', [
            'id' => $biblioteca->id,
        ]);
    }

    public function test_users_can_be_created_updated_and_deleted(): void
    {
        $response = $this->withSession(['_token' => 'test-token'])->post('/users', [
            '_token' => 'test-token',
            'name' => 'Maria',
            'email' => 'maria@example.com',
            'password' => '12345678',
        ]);

        $response->assertRedirect(route('users.index'))
            ->assertSessionHas('message', 'Usuário criado com sucesso');

        $user = User::where('email', 'maria@example.com')->firstOrFail();

        $this->assertDatabaseHas('users', [
            'email' => 'maria@example.com',
        ]);

        $editResponse = $this->get(route('users.edit', $user));
        $editResponse->assertOk();

        $updateResponse = $this->withSession(['_token' => 'test-token'])->put(route('users.update', $user), [
            '_token' => 'test-token',
            'name' => 'Maria Souza',
            'email' => 'maria.souza@example.com',
            'role' => 'admin',
        ]);

        $updateResponse->assertRedirect(route('users.index'))
            ->assertSessionHas('message', 'Usuário atualizado com sucesso');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Maria Souza',
            'email' => 'maria.souza@example.com',
            'role' => 'admin',
        ]);

        $deleteResponse = $this->withSession(['_token' => 'test-token'])->delete(route('users.destroy', $user), [
            '_token' => 'test-token',
        ]);

        $deleteResponse->assertRedirect(route('users.index'))
            ->assertSessionHas('message', 'Usuário excluído com sucesso');

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    public function test_pessoas_can_be_created_and_password_mismatch_returns_error(): void
    {
        $response = $this->withSession(['_token' => 'test-token'])->post('/pessoas', [
            '_token' => 'test-token',
            'name' => 'João',
            'email' => 'joao@example.com',
            'telefone' => '11999999999',
            'matricula' => '12345',
            'password' => 'secret123',
            'confirmPassword' => 'secret123',
        ]);

        $response->assertRedirect(route('pessoas.index'))
            ->assertSessionHas('message', 'Pessoa criada com sucesso!');

        $this->assertDatabaseHas('pessoas', [
            'email' => 'joao@example.com',
            'matricula' => '12345',
        ]);

        $invalidResponse = $this->withSession(['_token' => 'test-token'])
            ->from('/pessoas/create')
            ->post('/pessoas', [
                '_token' => 'test-token',
                'name' => 'Maria',
                'email' => 'maria2@example.com',
                'telefone' => '11888888888',
                'matricula' => '54321',
                'password' => 'abc123',
                'confirmPassword' => 'def456',
            ]);

        $invalidResponse->assertRedirect('/pessoas/create')
            ->assertSessionHas('error', 'As senhas não coincidem!');
    }

    public function test_pessoas_can_be_associated_to_a_biblioteca(): void
    {
        $user = User::factory()->create();
        $biblioteca = Biblioteca::create([
            'created_by' => $user->id,
            'nome' => 'Biblioteca do Conhecimento',
            'endereco' => 'Rua do Saber',
        ]);
        $pessoa = Pessoa::create([
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'password' => bcrypt('secret'),
            'matricula' => '999',
            'telefone' => '11777777777',
        ]);

        $response = $this->get(route('bibliotecas.pessoas.create', $biblioteca));
        $response->assertOk();

        $storeResponse = $this->withSession(['_token' => 'test-token'])->post(route('bibliotecas.pessoas.store', $biblioteca), [
            '_token' => 'test-token',
            'pessoa_id' => $pessoa->id,
        ]);

        $storeResponse->assertRedirect(route('bibliotecas.edit', ['id' => $biblioteca->id]))
            ->assertSessionHas('message', 'Pessoa adicionada à biblioteca com sucesso.');

        $this->assertDatabaseHas('biblioteca_pessoa', [
            'biblioteca_id' => $biblioteca->id,
            'pessoa_id' => $pessoa->id,
        ]);
    }
}
