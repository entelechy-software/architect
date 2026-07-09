<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests;

use Entelechy\Architect\Architect;
use Entelechy\Architect\Persistence\Models\ArchitectUploads;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ArchitectTrackUploadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('architect_uploads', function (Blueprint $table): void {
            $table->id();
            $table->string('path');
            $table->string('disk');
            $table->string('contract_key');
            $table->string('stage');
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamp('soft_deleted_at')->nullable();
            $table->timestamp('purged_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('architect_uploads');

        parent::tearDown();
    }

    public function test_track_upload_persists_a_ledger_row_with_explicit_contract(): void
    {
        $upload = Architect::trackUpload('uploads/report.pdf', disk: 'uploads', contract: 'sensitive-files');

        $this->assertInstanceOf(ArchitectUploads::class, $upload);
        $this->assertSame('uploads/report.pdf', $upload->path);
        $this->assertSame('uploads', $upload->disk);
        $this->assertSame('sensitive-files', $upload->contract_key);
        $this->assertSame(ArchitectUploads::STAGE_ACTIVE, $upload->stage);
        $this->assertNotNull($upload->last_accessed_at);
    }

    public function test_track_upload_falls_back_to_default_contract(): void
    {
        config()->set('architect.file_retention.default_contract', 'standard-files');

        $upload = Architect::trackUpload('uploads/photo.jpg');

        $this->assertSame('standard-files', $upload->contract_key);
        $this->assertSame('public', $upload->disk);
    }
}
