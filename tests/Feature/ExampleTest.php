<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBasicTest()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }


    /** test*/
    function it_crete_a_new_cargo()
    {
        $this->post('/cargos/',
                    ['codigo' => '1',
                    'nombre' => 'Prueba',
                    'descripcion' => 'Desc. Prueba',
                    'vigencia' => '1',
                    ])->assertSee('cargando...');
    }

}
