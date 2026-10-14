<?php

namespace Tests\Feature;

use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NoteApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_returns_201_and_token(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Budi',
            'email' => 'budi@example.com',
            'password' => 'password123',
        ])->assertStatus(200)->assertJsonStructure(['token', 'user']);
    }

    public function test_register_validation_returns_422(): void
    {
        $this->postJson('/api/register', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_login_success_returns_200(): void
    {
        User::factory()->create(['email' => 'a@example.com', 'password' => 'password123']);

        $this->postJson('/api/login', ['email' => 'a@example.com', 'password' => 'password123'])
            ->assertStatus(200)->assertJsonStructure(['token']);
    }

    public function test_login_wrong_password_returns_401(): void
    {
        User::factory()->create(['email' => 'a@example.com', 'password' => 'password123']);

        $this->postJson('/api/login', ['email' => 'a@example.com', 'password' => 'salah-total'])
            ->assertStatus(401);
    }

    public function test_notes_require_authentication(): void
    {
        $this->getJson('/api/notes')->assertStatus(401);
    }

    public function test_user_can_create_and_list_notes(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/notes', ['title' => 'Belajar Laravel', 'content' => 'Isi'])
            ->assertStatus(201)->assertJsonPath('data.title', 'Belajar Laravel');

        $this->getJson('/api/notes')->assertStatus(200)->assertJsonCount(1, 'data');
    }

    public function test_create_note_validation_returns_422(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/notes', [])->assertStatus(422)->assertJsonValidationErrors(['title']);
    }

    public function test_show_missing_note_returns_404(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/notes/999')->assertStatus(404);
    }

    public function test_user_can_update_and_delete_note(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $note = Note::factory()->create(['user_id' => $user->id]);

        $this->putJson('/api/notes/'.$note->id, ['title' => 'Judul Baru'])
            ->assertStatus(200)->assertJsonPath('data.title', 'Judul Baru');

        $this->deleteJson('/api/notes/'.$note->id)->assertStatus(200);
        $this->assertDatabaseMissing('notes', ['id' => $note->id]);
    }
}
