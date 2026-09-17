<?php

declare(strict_types=1);

namespace Crenspire\Yii2Inertia\Tests\Unit;

use Crenspire\Yii2Inertia\Vite;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use yii\base\InvalidConfigException;

class ViteTest extends TestCase
{
    private const BUILD_PATH = __DIR__ . '/../fixtures/web/dist';

    public function testBuildTagsIncludeImportedChunksAndCss(): void
    {
        $vite = new Vite(['buildPath' => self::BUILD_PATH, 'baseUrl' => '/dist/']);

        $this->assertSame(
            implode("\n", [
                '<link href="/dist/assets/vendor-def456.js" rel="modulepreload">',
                '<link href="/dist/assets/main-aaa111.css" rel="stylesheet">',
                '<link href="/dist/assets/vendor-bbb222.css" rel="stylesheet">',
                '<script type="module" src="/dist/assets/main-abc123.js"></script>',
            ]),
            $vite->tags('src/main.jsx'),
        );
    }

    public function testCssEntry(): void
    {
        $vite = new Vite(['buildPath' => self::BUILD_PATH, 'baseUrl' => '/dist']);

        $this->assertSame('<link href="/dist/assets/app-ccc333.css" rel="stylesheet">', $vite->tags(['src/app.css']));
        $this->assertSame('/dist/assets/logo-ddd444.svg', $vite->asset('src/logo.svg'));
    }

    public function testUnknownEntryThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new Vite(['buildPath' => self::BUILD_PATH]))->tags('src/missing.js');
    }

    public function testMissingManifestThrows(): void
    {
        $this->expectException(InvalidConfigException::class);
        (new Vite(['buildPath' => __DIR__ . '/does-not-exist']))->tags('src/main.jsx');
    }

    public function testLegacyManifestLocationAndHash(): void
    {
        $dir = sys_get_temp_dir() . '/yii2-inertia-vite-' . uniqid();
        mkdir($dir);
        file_put_contents("$dir/manifest.json", '{"src/main.js":{"file":"assets/main.js","isEntry":true}}');

        try {
            $vite = new Vite(['buildPath' => $dir, 'baseUrl' => '/build']);
            $this->assertSame('<script type="module" src="/build/assets/main.js"></script>', $vite->tags('src/main.js'));
            $this->assertSame(md5_file("$dir/manifest.json"), $vite->manifestHash());
        } finally {
            unlink("$dir/manifest.json");
            rmdir($dir);
        }

        $this->assertNull((new Vite(['buildPath' => $dir]))->manifestHash());
    }

    public function testDevServerTags(): void
    {
        $vite = new Vite(['devServerUrl' => 'http://localhost:5173/', 'reactRefresh' => true]);

        $tags = $vite->tags(['src/main.jsx', 'src/app.css']);

        $this->assertStringContainsString('import RefreshRuntime from "http://localhost:5173/@react-refresh";', $tags);
        $this->assertStringContainsString('<script type="module" src="http://localhost:5173/@vite/client"></script>', $tags);
        $this->assertStringContainsString('<script type="module" src="http://localhost:5173/src/main.jsx"></script>', $tags);
        $this->assertStringContainsString('<link href="http://localhost:5173/src/app.css" rel="stylesheet">', $tags);
        $this->assertLessThan(strpos($tags, '@vite/client'), strpos($tags, '@react-refresh'));
        $this->assertSame('http://localhost:5173/src/logo.svg', $vite->asset('src/logo.svg'));
    }
}
