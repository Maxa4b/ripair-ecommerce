<?php

namespace App\Services\RHCatalogo;

use App\Models\Catalog\Product;
use App\Models\Legacy\Repair;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RHCImageService
{
    /**
     * Télécharge et post-traite l'image d'un repair (image_url) et l'associe au produit.
     * - Convertit en WebP (qualité 80) si possible, sinon garde l'original.
     * - Enregistre sous storage/app/public/rhcatalogo/{product_id}.webp
     * - Met à jour product->rhc_generated_image avec l'URL publique /storage/rhcatalogo/...
     */
    public function process(Repair $repair, Product $product): ?string
    {
        $url = trim((string) $repair->image_url);
        if ($url === '') {
            return null;
        }

        $raw = $this->download($url);
        if ($raw === null) {
            return null;
        }

        $path = "rhcatalogo/{$product->id}.webp";
        $publicUrl = null;

        if ($this->saveAsWebp($raw, $path)) {
            $publicUrl = $this->publicUrl($path);
        } else {
            // fallback : enregistrer l'original
            $ext = $this->guessExtensionFromUrl($url) ?? 'jpg';
            $altPath = "rhcatalogo/{$product->id}." . $ext;
            Storage::disk('public')->put($altPath, $raw);
            $publicUrl = $this->publicUrl($altPath);
        }

        if ($publicUrl) {
            $product->update(['rhc_generated_image' => $publicUrl]);
        }

        return $publicUrl;
    }

    private function download(string $url): ?string
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'follow_location' => 1,
            ],
            'https' => [
                'timeout' => 10,
                'follow_location' => 1,
            ],
        ]);

        $raw = @file_get_contents($url, false, $context);
        if ($raw === false || strlen($raw) < 1000) {
            return null; // trop petit ou échec
        }
        return $raw;
    }

    private function saveAsWebp(string $raw, string $path): bool
    {
        if (!function_exists('imagecreatefromstring') || !function_exists('imagewebp')) {
            return false;
        }

        $img = @imagecreatefromstring($raw);
        if (!$img) {
            return false;
        }

        // Fond blanc si transparence
        $width = imagesx($img);
        $height = imagesy($img);
        $canvas = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopy($canvas, $img, 0, 0, 0, 0, $width, $height);

        ob_start();
        $ok = imagewebp($canvas, null, 80);
        $data = ob_get_clean();

        imagedestroy($img);
        imagedestroy($canvas);

        if (!$ok || !$data) {
            return false;
        }

        Storage::disk('public')->put($path, $data);
        return true;
    }

    private function publicUrl(string $path): string
    {
        return rtrim(url('/storage/' . ltrim($path, '/')), '/');
    }

    private function guessExtensionFromUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        if ($ext) {
            $ext = Str::lower($ext);
            $ext = trim($ext, '.');
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                return $ext === 'jpeg' ? 'jpg' : $ext;
            }
        }
        return null;
    }
}
