<?php

namespace EragLaravelPwa\Tests\Feature;

use EragLaravelPwa\Tests\TestCase;
use Illuminate\Support\Facades\Blade;

class BladeDirectivesTest extends TestCase
{
    /** @test */
    public function it_registers_offline_head_directive(): void
    {
        $compiled = Blade::compileString('@offlineHead');

        $this->assertStringContainsString('OfflineService', $compiled);
        $this->assertStringContainsString('headTag', $compiled);
    }

    /** @test */
    public function it_registers_offline_scripts_directive(): void
    {
        $compiled = Blade::compileString('@offlineScripts');

        $this->assertStringContainsString('OfflineService', $compiled);
        $this->assertStringContainsString('registerScript', $compiled);
    }

    /** @test */
    public function it_registers_offline_status_directive(): void
    {
        $compiled = Blade::compileString('@offlineStatus');

        $this->assertStringContainsString('OfflineService', $compiled);
        $this->assertStringContainsString('statusIndicator', $compiled);
    }

    /** @test */
    public function it_registers_offline_cache_directive(): void
    {
        $compiled = Blade::compileString('@offlineCache("key") Content @endOfflineCache');

        $this->assertStringContainsString('ob_start', $compiled);
        $this->assertStringContainsString('cacheWrapper', $compiled);
    }

    /** @test */
    public function it_registers_offline_sync_directive(): void
    {
        $compiled = Blade::compileString('@offlineSync Form content @endOfflineSync');

        $this->assertStringContainsString('ob_start', $compiled);
        $this->assertStringContainsString('syncWrapper', $compiled);
    }
}
