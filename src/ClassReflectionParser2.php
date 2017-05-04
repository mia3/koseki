<?php

require_once dirname(__DIR__) . '/vendor/hafriedlander/php-peg/autoloader.php';
use hafriedlander\Peg\Parser;

class Calculator extends Parser\Basic {
    /* Number: /[0-9]+/ */
    protected $match_Number_typestack = array('Number');
    function match_Number ($stack = array()) {
    	$matchrule = "Number"; $result = $this->construct($matchrule, $matchrule, null);
    	if (( $subres = $this->rx( '/[0-9]+/' ) ) !== FALSE) {
    		$result["text"] .= $subres;
    		return $this->finalise($result);
    	}
    	else { return FALSE; }
    }


    /* Plus: '+' | Exprs */
    protected $match_Plus_typestack = array('Plus');
    function match_Plus ($stack = array()) {
    	$matchrule = "Plus"; $result = $this->construct($matchrule, $matchrule, null);
    	$_4 = NULL;
    	do {
    		$res_1 = $result;
    		$pos_1 = $this->pos;
    		if (substr($this->string,$this->pos,1) == '+') {
    			$this->pos += 1;
    			$result["text"] .= '+';
    			$_4 = TRUE; break;
    		}
    		$result = $res_1;
    		$this->pos = $pos_1;
    		$matcher = 'match_'.'Exprs'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres );
    			$_4 = TRUE; break;
    		}
    		$result = $res_1;
    		$this->pos = $pos_1;
    		$_4 = FALSE; break;
    	}
    	while(0);
    	if( $_4 === TRUE ) { return $this->finalise($result); }
    	if( $_4 === FALSE) { return FALSE; }
    }

public function Plus_Plus ( &$result, $sub ) {
        var_dump($result, $sub);
        $result['val'] = $sub['val'] ;
    }

    /* Expr: Number > | operand:Plus */
    protected $match_Expr_typestack = array('Expr');
    function match_Expr ($stack = array()) {
    	$matchrule = "Expr"; $result = $this->construct($matchrule, $matchrule, null);
    	$_12 = NULL;
    	do {
    		$res_6 = $result;
    		$pos_6 = $this->pos;
    		$_9 = NULL;
    		do {
    			$matcher = 'match_'.'Number'; $key = $matcher; $pos = $this->pos;
    			$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    			if ($subres !== FALSE) {
    				$this->store( $result, $subres );
    			}
    			else { $_9 = FALSE; break; }
    			if (( $subres = $this->whitespace(  ) ) !== FALSE) { $result["text"] .= $subres; }
    			$_9 = TRUE; break;
    		}
    		while(0);
    		if( $_9 === TRUE ) { $_12 = TRUE; break; }
    		$result = $res_6;
    		$this->pos = $pos_6;
    		$matcher = 'match_'.'Plus'; $key = $matcher; $pos = $this->pos;
    		$subres = ( $this->packhas( $key, $pos ) ? $this->packread( $key, $pos ) : $this->packwrite( $key, $pos, $this->$matcher(array_merge($stack, array($result))) ) );
    		if ($subres !== FALSE) {
    			$this->store( $result, $subres, "operand" );
    			$_12 = TRUE; break;
    		}
    		$result = $res_6;
    		$this->pos = $pos_6;
    		$_12 = FALSE; break;
    	}
    	while(0);
    	if( $_12 === TRUE ) { return $this->finalise($result); }
    	if( $_12 === FALSE) { return FALSE; }
    }

public function Expr_Sum ( &$result, $sub ) {
        var_dump($result, $sub);
        $result['val'] = $sub['val'] ;
    }


}
$x = new Calculator( '3 + 2' ) ;
$res = $x->match_Expr() ;
if ( $res === FALSE ) {
    print "No Match\n" ;
}
else {
    print_r( $res ) ;
}
