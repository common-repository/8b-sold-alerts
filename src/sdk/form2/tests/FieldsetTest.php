<?php

namespace plainview\sdk_eightb_sold_alerts\form2\tests;

class FieldsetTest extends TestCase
{
	public function fs()
	{
		return $this->form()->fieldset( 'fieldsettest' )
			->label( 'Fieldset test' );
	}
	public function test_legend()
	{
		$fs = $this->fs();
		$this->assertStringContains( '<legend>Fieldset test</legend>', $fs );
	}
}
