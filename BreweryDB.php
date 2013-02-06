<?php 
/*
 * @copyright 2013
 * @version 2.0.0
 * @author Shaun Farrell - PintLabs L.L.C.
 * @link http://www.brewerydb.com/
 * @package WP_BreweryDB
 * 
 */
class BreweryDB {
	
	var $api_url = "http://api.brewerydb.com/v2/";
	var $api_key;
	var $format = "json";
	var $api_cache = "cache";
	var $cache_time = 86400;
	
	function BreweryDB() {
		add_shortcode( 'brewery', array( &$this, 'brewery' ) );
		add_shortcode( 'beer', array( &$this, 'beer' ) );
		
		$this->api_key = get_option( 'brewerydb_apikey' );
		$this->cache_time = get_option( 'brewerydb_cachetime' );
		
		if ( "" == $this->api_key ) {
			return new WP_Error('error', __( 'No BreweryDB API key provided.' ));
		}
		
		wp_enqueue_style('my-style', get_bloginfo('wpurl') . '/wp-content/plugins/brewery-db/css/style.css');
	}
	
	function brewery( $attrs, $content = null) {
		$output = "";

		if ( !isset( $attrs['id'] ) ) {
			return new WP_Error('error', __( "No breweries id's set" ));
		}

		$ids = explode( ",", $attrs['id'] );
		
		if ( is_array( $ids ) ) {
			$output .= '<div class="breweries">';
			
			if ( "" != $content ) {
				$output .= '<div class="text">' . $content . '</div>';
			}

			foreach ( $ids as $id ) {
				$breweryObj = $this->get_brewery( $id );

				if ( "success" !== $breweryObj->status ) {
					return new WP_Error('error', __( $breweryObj->message ));
				}

				$output .= '<div id="brewery-' . $breweryObj->data->id . '" class="brewery">';

				// Brewery Image
				if (isset($breweryObj->data->images->medium)) {
					$output .= '<div class="logo"><img src="' . $breweryObj->data->images->icon . '" /></div>';
				}

				// Brewery Name
				$output .= '<div class="name"><a href="http://brewerydb.com/brewery/' . $breweryObj->data->id . '/">' . $breweryObj->data->name . '</a></div>';

				// Established
				if (isset($breweryObj->data->established)) {
					$output .= '<div class="established">Established: ' . $breweryObj->data->established . '</div>';
				}

				// Primary Location
				$primary_location = $this->get_primary_location( $breweryObj->data->locations );
				if (!is_null( $primary_location ) ) {
					$output .= '<div class="address">';
 					$output .= '<div class="street-address">' . $primary_location->streetAddress . '</div>';
 					
 					if ( "" != $primary_location->extendedAddress ) {
 						$output .= '<div class="extended-address">' . $primary_location->extendedAddress . '</div>';
 					}

 					$output .= '<span class="locality">' . $primary_location->locality . '</span>, ';
 					$output .= '<span class="region">' . $primary_location->region . '</span> ';
 					$output .= '<span class="postal-code">' . $primary_location->postalCode . '</span>';
 					$output .= '<div class="country-name">' . $primary_location->country->displayName . '</div>';
					$output .= '</div>';
				}

				// Brewery Description
				$output .= '<div class="description">' . $breweryObj->data->description . '</div>';
				$output .= '</div>';
			}

			$output .= '</div>';
		}

		return $output;
	}
	
	function beer( $attrs, $content = null ) {
		$output = "";

		if ( !isset( $attrs['id'] ) ) {
			return new WP_Error('error', __( "No beer id's set" ));
		}

		$ids = explode( ",", $attrs['id'] );

		if ( is_array( $ids ) ) {
			$output .= '<div class="beers">';
			
			// Content between tags
			if ( !is_null( $content ) ) { 
				$output .= '<div class="text">' . $content . '</div>';
			}
			
			// Beers
			foreach ( $ids as $id ) {
				$beerObj = $this->get_beer( $id );
				if ( "success" !== $beerObj->status ) {
					return new WP_Error('error', __( $beerObj->message ));
				}

				$output .= '<div id="beer-' . $beerObj->data->id . '" class="beer">';

				// Label
				if ( isset( $beerObj->data->labels->icon ) ) {
					$output .= '<div class="label"><img src="' . $beerObj->data->labels->icon . '" /></div>';
				}

				// Beer Name
				$output .= '<div class="name"><a href="http://brewerydb.com/beer/' . $beerObj->data->id  . '/">' . $beerObj->data->name . '</a></div>';
				
				// Beer Style
				if ( isset ( $beerObj->data->style->name ) ) {
					$output .= '<div class="style">' . $beerObj->data->style->name . '</div>';
				}

				// Breweries 
				$breweries = array();
				foreach ( $beerObj->data->breweries as $brewery ) {
					$brewery_html = '<a href="http://brewerydb.com/brewery/' . $brewery->id . '/">' . $brewery->name . '</a>';
					array_push( $breweries, $brewery_html );
				}
				$brewery_string = implode(", ", $breweries);
				$output .= '<div class="brewery">Brewed by: ' . $brewery_string . '</div>';

				// Description
				$output .= '<div class="description">' . $beerObj->data->description . '</div>';				

				// ABV
				if ( isset( $beerObj->data->abv ) ) { 
					$output .= '<div class="abv">ABV: ' . $beerObj->data->abv . '%</div>';
				}

				// IBU
				if ( isset( $beerObj->data->ibu ) ) { 
					$output .= '<div class="ibu">IBUs: ' . $beerObj->data->ibu  . '</div>';
				}

				// Available
				if ( isset( $beerObj->data->available->name ) ) { 
					$output .= '<div class="available">Availability: ' . $beerObj->data->available->name  . '</div>';
				}

				// Glass
				if ( isset( $beerObj->data->glass->name ) ) { 
					$output .= '<div class="glass">Glassware: ' . $beerObj->data->glass->name  . ' Glass</div>';
				}

				$output .= '</div>';
			}

			$output .= '</div>';

			return $output;
		}
	}
	
	function create_api_url( $endpoint, $args = array(), $id = null ) {
		$http_args = http_build_query( $args );
		$url = $this->api_url . $endpoint . '/';
		if ( !is_null( $id ) ) {
			$url .= $id . '/?' . $http_args;
		} else {
			$url .= '?' . $http_args;
		}
		return $url;
	}
	
	function get_brewery( $brewery_id ) {
		if ( is_null( $brewery_id ) || "" == $brewery_id ) {
			return new WP_Error('error', __( "No brewery id set." ));
		}
		
		$cache_key = "/brewery/" . $brewery_id;

		if ( $breweryObj = get_transient( $cache_key ) ) {
		} else {
			$api_args = array(
				'key' 			=> $this->api_key,
				'withLocations' => 'Y'
			);

			$url = $this->create_api_url( 'brewery', $api_args, $brewery_id );

			$data = wp_remote_get( $url, array('timeout' => 10 ) );
			if ( is_wp_error( $data ) ) {
				return new WP_Error( 'error', __( $data->get_error_message() ) );
   			}

			$jsonBody = wp_remote_retrieve_body( $data );
			$breweryObj = json_decode( $jsonBody );
			
			set_transient( $cache_key, $breweryObj, $this->cache_time );
		}

		return $breweryObj;
	}
	
	function get_beer( $beer_id ) {
		if ( is_null( $beer_id ) || "" == $beer_id ) {
			return new WP_Error('error', __( "No beer id set." ));
		}
		
		$cache_key = "/beer-" . $beer_id;

		if ( $beerObj = get_transient( $cache_key ) ) {
		} else {
			$api_args = array(
				'key' 			=> $this->api_key,
				'withBreweries' => 'Y',
			);
			
			$url = $this->create_api_url( 'beer', $api_args, $beer_id );

			$data = wp_remote_get( $url, array('timeout' => 10 ) );
			if ( is_wp_error( $data ) ) {
				return new WP_Error( 'error', __( $data->get_error_message() ) );
   			}

			$jsonBody = wp_remote_retrieve_body( $data );
			$beerObj = json_decode( $jsonBody );

			set_transient( $cache_key, $beerObj, $this->cache_time );
		}
		
		return $beerObj;
	}

	function get_primary_location( $locations ) {
		foreach ( $locations as $location ) {
			if ( "Y" === $location->isPrimary ) {
				return $location;
			}
		}

		return null;
	}
}
