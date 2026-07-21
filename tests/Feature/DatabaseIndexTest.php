<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_addresses_user_id_is_indexed(): void
    {
        $indexedColumns = collect(Schema::getIndexes('addresses'))
            ->pluck('columns')
            ->flatten();

        $this->assertTrue($indexedColumns->contains('user_id'));
    }
}
