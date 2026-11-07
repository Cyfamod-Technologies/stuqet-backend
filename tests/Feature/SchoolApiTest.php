<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\V2\SchoolController;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SchoolApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'school_test', 'database.connections.school_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->integer('logo')->nullable();
            $table->boolean('is_active');
        });
        DB::table('schools')->insert([
            ['id' => 1, 'name' => 'Lagos University', 'slug' => 'lagos', 'is_active' => true],
            ['id' => 2, 'name' => 'Abuja University', 'slug' => 'abuja', 'is_active' => true],
            ['id' => 3, 'name' => 'Lagos Inactive', 'slug' => 'inactive', 'is_active' => false],
        ]);
    }

    public function test_lookup_filters_inactive_schools_and_supports_search_and_pagination(): void
    {
        $controller = new SchoolController;
        $request = Request::create('/', 'GET', ['search' => 'Lagos', 'per_page' => 1]);
        $body = $controller->index($request)->toResponse($request)->getData(true);
        $this->assertCount(1, $body['data']);
        $this->assertSame(1, $body['data'][0]['id']);
        $this->assertSame('Lagos University', $body['data'][0]['name']);
        $this->assertNull($body['data'][0]['logo_url']);
        $this->assertSame(1, $body['meta']['total']);
        $this->assertSame(1, $body['meta']['per_page']);
    }

    public function test_inactive_school_detail_is_not_exposed(): void
    {
        $this->expectException(ModelNotFoundException::class);
        (new SchoolController)->show(3);
    }
}
