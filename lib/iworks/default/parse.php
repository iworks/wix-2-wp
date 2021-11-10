<?php

require_once dirname( __FILE__ ) . '/common.php';

class iworks_default_parse extends iworks_default_common {

	private $categories = array(
		array(
			'nicename' => 'aktualnosci',
			'content'  => 'Aktualności',
		),
	);
	private $counter    = 0;
	private $split      = 1000;

	public function __construct() {
		parent::__construct();
		$this->items = $this->get_items();
	}

	public function generate( $mode = 'echo' ) {
		if ( 'echo' == $mode ) {
			$this->wxr->head();
			$this->wxr->categories( $this->categories );
			/**
			 * print authors
			 */
			foreach ( $this->items as $one ) {
				if ( isset( $this->users[ $one['author_id'] ] ) ) {
					$this->wxr->author( $this->users[ $one['author_id'] ] );
				}
				$this->wxr->item( $one );
			}
			$this->wxr->foot();
		}
		if ( 'split' == $mode ) {
			$this->wxr->mode = 'return';
			$content         = '';
			foreach ( $this->items as $one ) {

				if ( 0 == $this->counter || 0 == $this->counter % $this->split ) {
					if ( $content ) {
						$content .= $this->wxr->foot();

						$file = sprintf( '/tmp/import.news.%03d.xml', $this->counter / $this->split );
						print $file . PHP_EOL;
						$fw = fopen( $file, 'w' );
						fputs( $fw, $content, strlen( $content ) );
						fclose( $fw );
					}

					$content = $this->wxr->head();
					if ( 0 == $this->counter ) {
						$content .= $this->wxr->categories( 'Nowości', 'nowosci', $this->categories );
					}
				}

				if ( isset( $this->users[ $one['author_id'] ] ) ) {
					$content .= $this->wxr->author( $this->users[ $one['author_id'] ] );
				}
				$content .= $this->wxr->item( $one );

				$this->counter++;
			}
			$content .= $this->wxr->foot();
			$file     = sprintf( '/tmp/import.news.%03d.xml', $this->counter / $this->split );
			print $file . PHP_EOL;
			$fw = fopen( $file, 'w' );
			fputs( $fw, $content, strlen( $content ) );
			fclose( $fw );
		}
	}

	protected function get_items() {
		global $options;
		$data  = array();
		$doc   = new DOMDocument();
		$files = $this->dir_to_array( $options['d'] );
		foreach ( $files as $file ) {
			$item = array(
				'post_id'    => 0,
				'categories' => $this->categories,
			);
			$f    = $options['d'] . DIRECTORY_SEPARATOR . $file;
			$doc->loadHTMLFile( $f, LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_NOBLANKS | LIBXML_NOEMPTYTAG );
			/**
			 * get title
			 */
			foreach ( $doc->getElementsByTagName( 'h1' ) as $element ) {
				if ( preg_match( '/post-title/', $element->getAttribute( 'class' ) ) ) {
					$value             = strip_tags( $this->get_inner_html( $element, 'inner' ) );
					$item['title']     = $value;
					$item['post_name'] = slugify( $value );
				}
			}
			/**
			 * get content
			 */
			foreach ( $doc->getElementsByTagName( 'div' ) as $element ) {
				if ( preg_match( '/post-content__body/', $element->getAttribute( 'class' ) ) ) {
					$content         = $this->get_inner_html( $element );
					$content         = preg_replace( '@<span><br role="presentation"/></span>@', '', $content );
					$content         = preg_replace( '@</p>@', '</p>' . PHP_EOL . PHP_EOL, $content );
					$item['content'] = $content;
				}
			}
			/**
			 * get data from spans
			 */
			foreach ( $doc->getElementsByTagName( 'span' ) as $element ) {
				switch ( $element->getAttribute( 'data-hook' ) ) {
					case 'user-name':
						$item['author_id'] = $this->get_user_id_from_string( $element->nodeValue );
						$item['creator']   = $element->nodeValue;
						break;
					case 'time-ago':
						$value             = date( 'Y-m-d H:i:s', strtotime( $this->convert_date( $element->nodeValue ) ) );
						$item['pubDate']   = $value;
						$item['post_date'] = $value;
						break;
				}
			}
			/**
			 * get tags
			 */
			// tags_input
			foreach ( $doc->getElementsByTagName( 'nav' ) as $element ) {
				if ( 'tags' === $element->getAttribute( 'aria-label' ) ) {
					$item['tags_input'] = array();
					foreach ( $element->getElementsByTagName( 'a' ) as $el ) {
						$item['tags_input'][] = preg_replace( '/_/', ' ', $el->nodeValue );
					}
				}
			}
			/**
			 * end
			 */
			$data[] = $item;
		}
		return $data;
	}

}

