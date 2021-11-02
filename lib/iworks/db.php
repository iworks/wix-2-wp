<?php
class iworks_db
{
	public $db;

	public function __construct() {
	}

	public function connect() {
		global $config;

		$hostname = gethostname();

		$dbconfig = $config->database;

		if ( isset( $config->{'database-'.$hostname} ) ) {
			$dbconfig = $config->{'database-'.$hostname };
		}

		$pdo = sprintf(
			'mysql:host=%s;dbname=%s',
			$dbconfig->dbhost,
			$dbconfig->dbname
		);

		$this->db = new PDO( $pdo, $dbconfig->dbuser, $dbconfig->dbpass );
		if ( isset( $dbconfig->exec_on_connect ) ) {
			$this->exec( $dbconfig->exec_on_connect );
		}

	}

	public function exec( $sql ) {
		return $this->db->exec( $sql );
	}

	public function get( $table, $limit = 0, $random = false, $where = '', $order = '' ) {
		$sql = sprintf( 'select * from %s', $table );
		/**
		 * where
		 */
		if ( ! empty( $where ) ) {
			$sql .= ' where '. $where;
		}
		/**
		 * order
		 */
		if ( $order ) {
			$sql .= ' order by '.$order;
		} else {
			/**
			 * random
			 */
			if ( $random ) {
				$sql .= ' order by rand()';
			}
		}
		/**
		 * limit
		 */
		if ( $limit ) {
			$sql .= sprintf( ' limit %d', $limit );
		}
		/**
		 * get results
		 */
		return $this->sql( $sql );
	}

	public function sql( $sql ) {
		$sth = $this->db->prepare( $sql );
		$sth->execute();
		return $sth->fetchAll( PDO::FETCH_ASSOC );
	}
}
