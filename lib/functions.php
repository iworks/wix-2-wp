<?php

function d( $a, $b = '' ) {
	$ajax = true;
	if ( ! is_object( $a ) && ! is_array( $a ) ) {
		$a = (array) $a;
	}
	if ( $b ) {
		if ( $ajax ) {
			printf( "\n--------- %s ---------\n", $b );
		} else {
			printf( '<h3>%s</h3>', $b );
		}
	}
	if ( ! $ajax ) {
		print '<pre>';
	}
	print_r( $a );
	if ( $ajax ) {
		echo "\n",'-----------------------------------',"\n\n";
	} else {
		print '</pre>';
	}
}

function slugify( $text ) {
	// replace non letter or digits by -
	$text = preg_replace( '~[^\\pL\d\.]+~u', '-', $text );

	// trim
	$text = trim( $text, '-' );

	// transliterate
	$text = iconv( 'utf-8', 'us-ascii//TRANSLIT', $text );

	// lowercase
	$text = strtolower( $text );

	// remove unwanted characters
	$text = preg_replace( '~[^-\w]+~', '', $text );

	return $text;
}
