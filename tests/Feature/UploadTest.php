<?php
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('usuario autenticado puede subir un PDF', function () {
    $this->actingAs($user = User::factory()->create()); // Autenticación
    
    $file = UploadedFile::fake()->create('documento.pdf', 500, 'application/pdf');

    $response = $this->postJson('/api/upload', [
        'file' => $file,
        'microproyecto_uuid' => 'uuid-valido',
        'tipo' => 'documento',
        'label' => 'Manual técnico'
    ]);

    $response->assertStatus(201);
});

test('no permite subir archivos ejecutables (.php)', function () {
    $this->actingAs($user = User::factory()->create());
    $file = UploadedFile::fake()->create('malicioso.php', 10, 'application/x-php');

    $response = $this->postJson('/api/upload', [
        'file' => $file,
        // ... resto de campos
    ]);

    $response->assertStatus(422); // Error de validación
});