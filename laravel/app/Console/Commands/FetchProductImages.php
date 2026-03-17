<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FetchProductImages extends Command
{
    protected $signature = 'product:fetch-images {--limit=100} {--offset=0} {--brand=} {--reset}';
    protected $description = 'Fetch real brand/model specific images for products using image search';

    private string $imageDir;
    private string $logFile;
    private array $modelImageCache = [];

    public function handle()
    {
        $this->imageDir = public_path('uploads/products/');
        $this->logFile  = storage_path('logs/product_images.log');

        if (!is_dir($this->imageDir)) {
            mkdir($this->imageDir, 0775, true);
        }

        if ($this->option('reset')) {
            DB::table('products')->update(['image' => null]);
            $this->info('Reset all product images.');
            return;
        }

        $limit       = (int) $this->option('limit');
        $offset      = (int) $this->option('offset');
        $filterBrand = $this->option('brand');

        $query = DB::table('products')
            ->join('brands', 'products.brand_id', '=', 'brands.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select(
                'products.id',
                'products.name',
                'products.item_code',
                'products.image',
                'brands.name as brand',
                'categories.name as category'
            )
            ->where(function ($q) {
                $q->whereNull('products.image')
                  ->orWhere('products.image', '')
                  ->orWhere('products.image', 'LIKE', 'cat_%');
            });

        if ($filterBrand) {
            $query->where('brands.name', strtoupper($filterBrand));
        }

        $products = $query->offset($offset)->limit($limit)->get();
        $this->info("Processing {$products->count()} products (offset: $offset, limit: $limit)...");

        $success = 0;
        $failed  = 0;

        foreach ($products as $product) {
            $imageName = $this->findAndDownloadImage($product);
            if ($imageName) {
                DB::table('products')->where('id', $product->id)->update(['image' => $imageName]);
                $this->line("  ✓ [{$product->id}] {$product->name} -> $imageName");
                $success++;
            } else {
                $this->line("  ✗ [{$product->id}] {$product->name}");
                $failed++;
            }
            usleep(400000);
        }

        $this->info("\nDone: $success succeeded, $failed failed");
        file_put_contents($this->logFile,
            date('Y-m-d H:i:s') . " offset=$offset limit=$limit success=$success failed=$failed\n",
            FILE_APPEND
        );
    }

    private function findAndDownloadImage(object $product): ?string
    {
        $model    = $this->extractModelNumber($product->name, $product->brand);
        $cacheKey = strtolower($product->brand . '_' . $model);

        if (isset($this->modelImageCache[$cacheKey])) {
            return $this->modelImageCache[$cacheKey];
        }

        $searchQuery = trim($product->brand . ' ' . $model . ' pakistan');
        $imageUrl    = $this->ddgImageSearch($searchQuery);

        if (!$imageUrl) {
            $searchQuery = trim($product->name . ' pakistan');
            $imageUrl    = $this->ddgImageSearch($searchQuery);
        }

        if (!$imageUrl) {
            $this->modelImageCache[$cacheKey] = null;
            return null;
        }

        $filename = $this->downloadImage($imageUrl, $product->id, $product->brand, $model);
        $this->modelImageCache[$cacheKey] = $filename;
        return $filename;
    }

    private function ddgImageSearch(string $query): ?string
    {
        $html = $this->curlGet(
            'https://duckduckgo.com/?q=' . urlencode($query) . '&iax=images&ia=images'
        );
        if (!$html) return null;

        preg_match('/vqd=["\']?([\d-]+)["\']?/i', $html, $m);
        if (empty($m[1])) return null;
        $vqd = $m[1];

        $json = $this->curlGet(
            'https://duckduckgo.com/i.js?l=us-en&o=json&q=' . urlencode($query)
            . '&vqd=' . $vqd . '&f=,,,,,',
            ['Accept: application/json', 'Referer: https://duckduckgo.com/']
        );
        if (!$json) return null;

        $data = json_decode($json, true);
        if (empty($data['results'])) return null;

        $blacklist = ['logo', 'icon', 'banner', 'flag', 'social', 'avatar', 'profile'];
        foreach ($data['results'] as $r) {
            $imgUrl = $r['image'] ?? '';
            if (!$imgUrl) continue;
            $low = strtolower($imgUrl);
            $bad = false;
            foreach ($blacklist as $b) {
                if (str_contains($low, $b)) { $bad = true; break; }
            }
            if (!$bad) return $imgUrl;
        }

        return null;
    }

    private function downloadImage(string $url, int $productId, string $brand, string $model): ?string
    {
        $safeBrand = preg_replace('/[^a-z0-9]/', '_', strtolower($brand));
        $safeModel = preg_replace('/[^a-z0-9]/', '_', strtolower($model));
        $safeModel = preg_replace('/_+/', '_', substr($safeModel, 0, 60));
        $ext       = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg');
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) $ext = 'jpg';

        $filename = "prod_{$safeBrand}_{$safeModel}_{$productId}.{$ext}";
        $filepath = $this->imageDir . $filename;

        if (file_exists($filepath) && filesize($filepath) > 2000) {
            return $filename;
        }

        $data = $this->curlGet($url, ['Accept: image/*,*/*'], 15);
        if ($data && strlen($data) > 2000) {
            file_put_contents($filepath, $data);
            return $filename;
        }

        return null;
    }

    private function curlGet(string $url, array $headers = [], int $timeout = 10): ?string
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER     => array_merge(
                ['Accept-Language: en-US,en;q=0.9', 'Accept-Encoding: gzip, deflate'],
                $headers
            ),
            CURLOPT_ENCODING       => '',
        ]);
        $result = curl_exec($ch);
        $code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ($result !== false && $code === 200) ? $result : null;
    }

    private function extractModelNumber(string $name, string $brand): string
    {
        $name  = strtoupper(trim($name));
        $brand = strtoupper(trim($brand));

        $name = trim(preg_replace('/^' . preg_quote($brand, '/') . '\s+/', '', $name));

        $colors = [
            'METALIC GOLD', 'OPAL GREEN', 'STONE GREY', 'GLOSSY RED', 'ELEGANT GREY',
            'ELEGANT GOLD', 'NO COLOR', 'STAINLESS STEEL',
            'WHITE', 'BLACK', 'SILVER', 'GOLD', 'RED', 'BLUE', 'GREY', 'GREEN', 'BROWN',
            'CHOCOLATE', 'MAROON', 'PINK', 'PURPLE', 'OPAL', 'METALIC', 'TITANIUM',
            'GRAPHITE', 'CREAM', 'ORANGE', 'YELLOW', 'NAVY', 'CYAN', 'MAGENTA',
        ];
        foreach ($colors as $color) {
            $name = preg_replace('/[\s,]+' . preg_quote($color, '/') . '(\s+.*)?$/i', '', $name);
        }

        return trim($name);
    }
}
