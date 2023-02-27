<?php
/**
 * he WP CLI command for handle commands for booking products.
 *
 * @package Bookings Helper/ WP CLI commands
 * @since   x.x.x
 */

use WP_CLI\ExitException;

if ( ! class_exists( 'WP_CLI_Command' ) ) {
	return;
}

/**
 * Class WC_Bookings_Export_Command
 * @since x.x.x
 */
class WC_Bookings_Helper_Products_Command extends WP_CLI_Command {
	/**
	 * Exports booking products.
	 *
	 * ## OPTIONS
	 * [--all]
	 * : Whether or not export all booking products
	 *
	 * [--dir=<absolute_path_to_dir>]
	 * : The directory path to export the booking products
	 *
	 * ## EXAMPLES
	 * wp booking-helper-products export --all
	 * wp booking-helper-products export --all --dir=/path/to/export
	 *
	 * @since x.x.x
	 *
	 * @param array $args       Subcommand args.
	 * @param array $assoc_args Subcommand assoc args.
	 *
	 * @return void
	 * @throws ExitException
	 */
	public function export( array $args, array $assoc_args ) {
		// Export all booking products.
		if ( empty( $assoc_args['all'] ) ) {
			WP_CLI::error( 'Please provide a --all to export all booking products.' );

			return;
		}

		// Default path is wp-content/uploads.
		$directory_path = empty( $assoc_args['dir'] ) ?
			trailingslashit( WP_CONTENT_DIR ) . 'uploads' :
			$assoc_args['dir'];

		try {
			$name_prefix = sprintf(
				'booking-product-%s',
				date( 'Y-m-d',
					current_time( 'timestamp' )
				)
			);

			$zip_file_path  = "$directory_path/$name_prefix.zip";
			$json_file_name = "$name_prefix.json";

			// Create zip;
			$zip = new ZipArchive();
			$zip->open( $zip_file_path, ZipArchive::CREATE | ZipArchive::OVERWRITE );

			$zip->addFromString(
				$json_file_name,
				// Get json data for all booking products.
				( new WC_Bookings_Helper_Export() )->get_all_booking_products_data()
			);

			$zip->close();

			if ( $zip->open( $zip_file_path ) !== true ) {
				WP_CLI::error( 'Booking products export failed.' );

				return;
			}

			WP_CLI::success( "Booking products exported. Location: $zip_file_path" );
		} catch ( Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}
	}

	/**
	 * Import booking products.
	 *
	 * ## OPTIONS
	 *
	 * [--file=<absolute_path_to_zip_file>]
	 * : The zip file path to import the booking global availability rules
	 *
	 *
	 * ## EXAMPLES
	 * wp booking-helper-products import --all --file=/path/to/absolute_path_to_zip_file
	 *
	 * @since x.x.x
	 *
	 * @param array $args       Subcommand args.
	 * @param array $assoc_args Subcommand assoc args.
	 *
	 * @return void
	 * @throws ExitException
	 */
	public function import( array $args, array $assoc_args ) {
		if ( empty( $assoc_args['file'] ) ) {
			WP_CLI::error( 'Please provide the zip file path to import the booking products.' );

			return;
		}

		$file_path      = $assoc_args['file'];
		$file_name      = basename( $assoc_args['file'], '.zip' );
		$json_file_path = dirname( $file_path ) . '/' . $file_name . '.json';
		$zip            = new ZipArchive();

		if ( $zip->open( $assoc_args['file'] ) !== true ) {
			WP_CLI::error( 'Booking products import failed. Please provide valid file path.' );

			return;
		}

		$zip->extractTo( dirname( $file_path ) );
		$zip->close();

		$products = file_get_contents( $json_file_path );
		unlink( $json_file_path );

		if ( empty( $products ) ) {
			WP_CLI::error( 'Booking products import failed. File does not have data to import.' );

			return;
		}

		$products = json_decode( $products, true );

		try{
			foreach ( $products as $product ) {
				( new WC_Bookings_Helper_Import() )->import_product_from_json( $product );
			}
		} catch ( Exception $e ) {
			WP_CLI::error( 'Booking product import failed. Reason:' . $e->getMessage() );
		}

		WP_CLI::success( 'Booking products imported successfully.' );
	}
}
