<?php
class ItzDbCommand extends CDbCommand {
	public function prepare() {
		$this->getConnection ()->setSql ( $this->getText () );
		try {
			parent::prepare ();
			$this->getConnection ()->setSql ( "" );
		} catch ( Exception $e ) {
			$this->getConnection ()->setSql ( "" );
			throw $e;
		}
	}
}
