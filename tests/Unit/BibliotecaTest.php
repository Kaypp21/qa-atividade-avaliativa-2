<?php

namespace Tests\Unit;

use App\Models\Biblioteca;
use App\Models\User;
use App\Models\Pessoa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BibliotecaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Criar biblioteca com dados válidos
     */
    public function test_criar_biblioteca_com_dados_validos(): void
    {
        $user = User::factory()->create();

        $biblioteca = Biblioteca::create([
            'created_by' => $user->id,
            'nome' => 'Biblioteca Central',
            'endereco' => 'Rua das Flores, 123',
            'telefone' => '(11) 3333-3333',
            'email' => 'biblioteca@example.com',
        ]);

        $this->assertNotNull($biblioteca->id);
        $this->assertEquals('Biblioteca Central', $biblioteca->nome);
        $this->assertEquals($user->id, $biblioteca->created_by);
        $this->assertDatabaseHas('bibliotecas', [
            'id' => $biblioteca->id,
            'nome' => 'Biblioteca Central',
        ]);
    }

    /**
     * Test: Campos fillable estão corretos
     */
    public function test_campos_fillable_sao_validos(): void
    {
        $fillable = ['created_by', 'nome', 'endereco', 'telefone', 'email'];
        
        $this->assertEquals($fillable, (new Biblioteca)->getFillable());
    }

    /**
     * Test: Validação - Nome é obrigatório
     */
    public function test_nome_obrigatorio(): void
    {
        $user = User::factory()->create();

        try {
            Biblioteca::create([
                'created_by' => $user->id,
                'endereco' => 'Rua das Flores, 123',
            ]);
            $this->fail('Deveria ter lançado exceção por nome obrigatório');
        } catch (\Throwable $e) {
            $this->assertNotNull($e);
        }
    }

    /**
     * Test: Validação - Created_by é obrigatório
     */
    public function test_created_by_obrigatorio(): void
    {
        try {
            Biblioteca::create([
                'nome' => 'Biblioteca Central',
                'endereco' => 'Rua das Flores, 123',
            ]);
            $this->fail('Deveria ter lançado exceção por created_by obrigatório');
        } catch (\Throwable $e) {
            $this->assertNotNull($e);
        }
    }

    /**
     * Test: Validação - Created_by deve existir na tabela users
     */
    public function test_created_by_deve_existir_em_users(): void
    {
        try {
            Biblioteca::create([
                'created_by' => 9999,
                'nome' => 'Biblioteca Central',
                'endereco' => 'Rua das Flores, 123',
            ]);
            $this->fail('Deveria ter lançado exceção por user inexistente');
        } catch (\Throwable $e) {
            $this->assertNotNull($e);
        }
    }

    /**
     * Test: Campos opcionais podem ser nulos
     */
    public function test_campos_opcionais_podem_ser_nulos(): void
    {
        $user = User::factory()->create();

        $biblioteca = Biblioteca::create([
            'created_by' => $user->id,
            'nome' => 'Biblioteca Mínima',
        ]);

        $this->assertNull($biblioteca->endereco);
        $this->assertNull($biblioteca->telefone);
        $this->assertNull($biblioteca->email);
    }

    /**
     * Test: Atualizar campos de biblioteca
     */
    public function test_atualizar_campos_biblioteca(): void
    {
        $user = User::factory()->create();
        $biblioteca = Biblioteca::create([
            'created_by' => $user->id,
            'nome' => 'Biblioteca Antiga',
        ]);

        $biblioteca->update([
            'nome' => 'Biblioteca Atualizada',
            'email' => 'novo@email.com',
        ]);

        $this->assertEquals('Biblioteca Atualizada', $biblioteca->nome);
        $this->assertEquals('novo@email.com', $biblioteca->email);
    }

    /**
     * Test: Deletar biblioteca
     */
    public function test_deletar_biblioteca(): void
    {
        $user = User::factory()->create();
        $biblioteca = Biblioteca::create([
            'created_by' => $user->id,
            'nome' => 'Biblioteca a Deletar',
        ]);

        $id = $biblioteca->id;
        $biblioteca->delete();

        $this->assertNull(Biblioteca::find($id));
        $this->assertDatabaseMissing('bibliotecas', ['id' => $id]);
    }

    /**
     * Test: Relacionamento com creator (User)
     */
    public function test_relacionamento_com_creator(): void
    {
        $user = User::factory()->create(['name' => 'João Silva']);
        $biblioteca = Biblioteca::create([
            'created_by' => $user->id,
            'nome' => 'Biblioteca de Teste',
        ]);

        $this->assertNotNull($biblioteca->creator);
        $this->assertEquals($user->id, $biblioteca->creator->id);
        $this->assertEquals('João Silva', $biblioteca->creator->name);
    }

    /**
     * Test: Relacionamento many-to-many com users
     */
    public function test_relacionamento_many_to_many_com_users(): void
    {
        $creator = User::factory()->create();
        $admin = User::factory()->create();
        $biblioteca = Biblioteca::create([
            'created_by' => $creator->id,
            'nome' => 'Biblioteca Compartilhada',
        ]);

        $biblioteca->users()->attach($admin, ['role' => 'admin']);

        $this->assertTrue($biblioteca->users()->where('user_id', $admin->id)->exists());
        $this->assertEquals('admin', $biblioteca->users()->find($admin)->pivot->role);
    }

    /**
     * Test: Relacionamento com Pessoas (hasMany)
     */
    public function test_relacionamento_com_pessoas(): void
    {
        $user = User::factory()->create();
        $biblioteca = Biblioteca::create([
            'created_by' => $user->id,
            'nome' => 'Biblioteca com Pessoas',
        ]);

        $this->assertEquals(0, $biblioteca->pessoas()->count());
    }

    /**
     * Test: Timestamps são criados
     */
    public function test_timestamps_sao_criados(): void
    {
        $user = User::factory()->create();
        $biblioteca = Biblioteca::create([
            'created_by' => $user->id,
            'nome' => 'Biblioteca com Timestamp',
        ]);

        $this->assertNotNull($biblioteca->created_at);
        $this->assertNotNull($biblioteca->updated_at);
    }

    /**
     * Test: Buscar biblioteca por ID
     */
    public function test_buscar_biblioteca_por_id(): void
    {
        $user = User::factory()->create();
        $biblioteca = Biblioteca::create([
            'created_by' => $user->id,
            'nome' => 'Biblioteca Buscável',
        ]);

        $encontrada = Biblioteca::find($biblioteca->id);

        $this->assertNotNull($encontrada);
        $this->assertEquals($biblioteca->id, $encontrada->id);
    }

    /**
     * Test: Buscar biblioteca por nome
     */
    public function test_buscar_biblioteca_por_nome(): void
    {
        $user = User::factory()->create();
        $biblioteca = Biblioteca::create([
            'created_by' => $user->id,
            'nome' => 'Biblioteca Única',
        ]);

        $encontrada = Biblioteca::where('nome', 'Biblioteca Única')->first();

        $this->assertNotNull($encontrada);
        $this->assertEquals('Biblioteca Única', $encontrada->nome);
    }

    /**
     * Test: Buscar biblioteca inexistente retorna null
     */
    public function test_buscar_biblioteca_inexistente_retorna_null(): void
    {
        $encontrada = Biblioteca::find(9999);

        $this->assertNull($encontrada);
    }

    /**
     * Test: Validação - Email deve ser válido (quando fornecido)
     */
    public function test_email_pode_estar_vazio(): void
    {
        $user = User::factory()->create();
        $biblioteca = Biblioteca::create([
            'created_by' => $user->id,
            'nome' => 'Biblioteca sem Email',
            'email' => '',
        ]);

        $this->assertEmpty($biblioteca->email);
    }

    /**
     * Test: Múltiplas bibliotecas podem ser criadas
     */
    public function test_multiplas_bibliotecas_podem_ser_criadas(): void
    {
        $user = User::factory()->create();

        Biblioteca::create(['created_by' => $user->id, 'nome' => 'Biblioteca 1']);
        Biblioteca::create(['created_by' => $user->id, 'nome' => 'Biblioteca 2']);
        Biblioteca::create(['created_by' => $user->id, 'nome' => 'Biblioteca 3']);

        $this->assertEquals(3, Biblioteca::count());
    }

    /**
     * Test: Filtragem de bibliotecas pelo nome com LIKE
     */
    public function test_filtragem_bibliotecas_por_nome(): void
    {
        $user = User::factory()->create();
        
        Biblioteca::create(['created_by' => $user->id, 'nome' => 'Biblioteca Central']);
        Biblioteca::create(['created_by' => $user->id, 'nome' => 'Biblioteca do Bairro']);
        Biblioteca::create(['created_by' => $user->id, 'nome' => 'Livraria Independente']);

        $resultado = Biblioteca::where('nome', 'like', '%Biblioteca%')->get();

        $this->assertEquals(2, $resultado->count());
    }

    /**
     * Test: Erro ao criar biblioteca com created_by inválido
     */
    public function test_erro_created_by_invalido(): void
    {
        try {
            Biblioteca::create([
                'created_by' => 'não-é-um-número',
                'nome' => 'Biblioteca Inválida',
            ]);
            $this->fail('Deveria ter lançado exceção');
        } catch (\Throwable $e) {
            $this->assertNotNull($e);
        }
    }

    /**
     * Test: Preservar dados ao atualizar parcialmente
     */
    public function test_atualizar_parcialmente_preserva_dados(): void
    {
        $user = User::factory()->create();
        $biblioteca = Biblioteca::create([
            'created_by' => $user->id,
            'nome' => 'Biblioteca Original',
            'endereco' => 'Rua Original, 123',
            'email' => 'original@email.com',
        ]);

        $biblioteca->update(['nome' => 'Biblioteca Modificada']);

        $biblioteca->refresh();
        $this->assertEquals('Biblioteca Modificada', $biblioteca->nome);
        $this->assertEquals('Rua Original, 123', $biblioteca->endereco);
        $this->assertEquals('original@email.com', $biblioteca->email);
    }
}
