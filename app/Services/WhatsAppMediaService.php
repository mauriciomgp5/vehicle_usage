<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhatsAppMediaService
{
    /**
     * Salva uma imagem base64 no storage
     */
    public function saveImageFromBase64(array $imageData, ?string $caption = null): ?array
    {
        try {
            if (empty($imageData['base64'])) {
                Log::warning('Base64 da imagem está vazio');
                return null;
            }

            // Verifica se é uma imagem válida
            if (!$this->isValidImage($imageData['mimetype'])) {
                Log::warning('Tipo de arquivo não é uma imagem válida:', [
                    'mimetype' => $imageData['mimetype']
                ]);
                return null;
            }

            // Decodifica o base64
            $decodedImage = base64_decode($imageData['base64']);
            
            if ($decodedImage === false) {
                Log::error('Erro ao decodificar base64');
                return null;
            }

            // Gera um nome único para o arquivo
            $extension = $this->getExtensionFromMimeType($imageData['mimetype']);
            $filename = Str::uuid() . '.' . $extension;
            $path = 'occurrence_photos/' . date('Y/m/d') . '/' . $filename;

            // Salva o arquivo
            Storage::disk('public')->put($path, $decodedImage);

            Log::info('Imagem salva com sucesso:', [
                'path' => $path,
                'size' => strlen($decodedImage),
                'mimetype' => $imageData['mimetype']
            ]);

            return [
                'filename' => $filename,
                'original_filename' => $imageData['filename'] ?? $filename,
                'path' => $path,
                'mime_type' => $imageData['mimetype'],
                'size' => strlen($decodedImage),
                'caption' => $caption,
                'url' => Storage::disk('public')->url($path)
            ];

        } catch (\Exception $e) {
            Log::error('Exceção ao salvar imagem:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Obtém a extensão do arquivo baseada no MIME type
     */
    private function getExtensionFromMimeType(string $mimeType): string
    {
        return match($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/bmp' => 'bmp',
            default => 'jpg'
        };
    }

    /**
     * Valida se o arquivo é uma imagem válida
     */
    public function isValidImage(?string $mimeType): bool
    {
        $allowedTypes = [
            'image/jpeg',
            'image/png', 
            'image/gif',
            'image/webp',
            'image/bmp'
        ];

        return in_array($mimeType, $allowedTypes);
    }

    /**
     * Remove uma foto do storage
     */
    public function deletePhoto(string $path): bool
    {
        try {
            return Storage::disk('public')->delete($path);
        } catch (\Exception $e) {
            Log::error('Erro ao deletar foto:', [
                'path' => $path,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
} 